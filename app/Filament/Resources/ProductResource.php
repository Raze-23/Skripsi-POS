<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Stock;
use App\Filament\Resources\ProductResource\Actions\ExportCsvAllAction;
use App\Filament\Resources\ProductResource\Actions\ExportCsvSelectedBulkAction;
use App\Filament\Resources\ProductResource\Actions\ExportPdfAction;
use App\Filament\Resources\ProductResource\Actions\GenerateQRCodeAllAction;
use App\Filament\Resources\ProductResource\Actions\SafeDeleteBulkAction;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\ProductBatchesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\ProductDisposalsRelationManager;
use App\Models\Product;
use App\Models\ProductBatch;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $cluster = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-s-building-storefront';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $navigationLabel = 'Toko';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Informasi Dasar')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Grid::make(5)
                                ->schema([
                                    Forms\Components\Group::make([
                                        Forms\Components\TextInput::make('nama')
                                            ->rule('required')
                                            ->markAsRequired()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->validationMessages([
                                                'required' => 'Nama produk wajib diisi.',
                                                'unique' => 'Nama produk ini sudah terdaftar!',
                                            ]),
                                        Forms\Components\TextInput::make('estimasi_masak')
                                        ->label('Estimasi Waktu Pembuatan')
                                        ->numeric()
                                        ->rule('required')
                                        ->markAsRequired()
                                        ->suffix('Menit')
                                        ->minValue(1)
                                        ->validationMessages([
                                            'required' => 'Estimasi waktu pembuatan wajib diisi.',
                                            'min' => 'Estimasi waktu tidak boleh 0!',
                                        ]),
                                        Forms\Components\Toggle::make('is_discontinued')
                                            ->label('Telah Berhenti Produksi')
                                            ->helperText('Tandai jika produk ini sudah tidak diproduksi lagi.')
                                            ->default(false)
                                            ->hiddenOn('create'),
                                    ])
                                        ->columnSpan(2),
                                    Forms\Components\FileUpload::make('foto')
                                        ->image()
                                        ->imageEditor()
                                        ->imageResizeMode('cover')
                                        ->imageResizeTargetWidth('800')
                                        ->imageResizeTargetHeight('800')
                                        ->disk('public')
                                        ->directory('products')
                                        ->columnSpan(3),
                                ]),
                        ]),
                    Forms\Components\Wizard\Step::make('Harga')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\TextInput::make('harga_beli')
                                ->numeric()
                                ->rule('required')
                                ->markAsRequired()
                                ->prefix('Rp')
                                ->label('Modal')
                                ->validationMessages([
                                    'required' => 'Harga Modal wajib diisi.',
                                ])
                                ->rule(static function (Get $get) {
                                    return static function (string $attribute, $value, Closure $fail) use ($get) {
                                        $hargaJual = $get('harga_jual');
                                        if ($hargaJual !== null && $value >= $hargaJual) {
                                            $fail('Harga Modal tidak boleh lebih dari Harga Jual!');
                                        }
                                    };
                                }),
                            Forms\Components\TextInput::make('harga_jual')
                                ->numeric()
                                ->rule('required')
                                ->markAsRequired()
                                ->prefix('Rp')
                                ->validationMessages([
                                    'required' => 'Harga Jual wajib diisi.',
                                ])
                                ->rule(static function (Get $get) {
                                    return static function (string $attribute, $value, Closure $fail) use ($get) {
                                        $hargaBeli = $get('harga_beli');
                                        if ($hargaBeli !== null && $value <= $hargaBeli) {
                                            $fail('Harga Jual harus lebih besar dari Harga Modal!');
                                        }
                                    };
                                }),
                        ])
                            ->columns(2),
                ])
                    ->skippable(false)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto')
                    ->circular()
                    ->disk('public')
                    ->default(asset('images/notfound.png'))
                    ->extraImgAttributes(fn ($record) => [
                        'style' => $record->is_discontinued ? 'filter: grayscale(100%); opacity: 0.6;' : '',
                    ]),
                Tables\Columns\TextColumn::make('nama')
                    ->searchable()
                    ->description(fn ($record) => $record->is_discontinued ? 'Telah Berhenti Produksi' : null)
                    ->color(fn ($record) => $record->is_discontinued ? 'danger' : 'default')
                    ->weight(fn ($record) => $record->is_discontinued ? 'bold' : 'default'),
                Tables\Columns\TextColumn::make('estimasi_masak')
                    ->label('Waktu Produksi')
                    ->suffix(' Menit')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('product_batches_sum_stok_toko')
                    ->label('Total Stok')
                    ->badge()
                    ->color(fn ($state): string => ($state ?? 0) < 10 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_batches_min_tanggal_kedaluwarsa')
                    ->label('Kedaluwarsa Terdekat')
                    ->date('d M Y')
                    ->badge()
                    ->color(function ($state): string {
                        if (! $state) {
                            return 'gray';
                        }
                        $daysLeft = now()->startOfDay()->diffInDays(Carbon::parse($state)->startOfDay(), false);
                        return match (true) {
                            $daysLeft < 7 => 'danger',
                            $daysLeft < 30 => 'warning',
                            $daysLeft < 60 => 'info',
                            default => 'success',
                        };
                    })
                    ->icon(function ($state): string {
                        if (! $state) {
                            return 'heroicon-o-minus';
                        }
                        $daysLeft = now()->startOfDay()->diffInDays(Carbon::parse($state)->startOfDay(), false);
                        return match (true) {
                            $daysLeft < 7 => 'heroicon-o-x-circle',
                            $daysLeft < 30 => 'heroicon-o-exclamation-triangle',
                            $daysLeft < 60 => 'heroicon-o-clock',
                            default => 'heroicon-o-shield-check',
                        };
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_batches_count')
                    ->label('Jumlah Batch')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
            ])
            ->modifyQueryUsing(
                fn ($query) => $query
                    ->withCount([
                        'productBatches' => fn ($q) => $q->where('stok_toko', '>', 0),
                    ])
                    ->withSum([
                        'productBatches' => fn ($q) => $q->where('stok_toko', '>', 0),
                    ], 'stok_toko')
                    ->withMin([
                        'productBatches' => fn ($q) => $q->where('stok_toko', '>', 0),
                    ], 'tanggal_kedaluwarsa')
            )
            ->filters([])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record, Tables\Actions\DeleteAction $action) {
                            if ($record->productBatches()->exists()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Gagal Menghapus')
                                    ->body('Produk ini tidak dapat dihapus karena masih memiliki riwayat Batch Produk yang terikat.')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->send();

                                $action->halt();
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->danger()
                                ->title('Produk Berhasil Dihapus!')
                                ->body('Data produk telah dihapus secara permanen dari sistem toko.')
                                ->icon('heroicon-o-trash')
                        ),
                ]),
            ])
            ->headerActions([
                ExportCsvAllAction::make(),
                ExportPdfAction::make(),
                GenerateQRCodeAllAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportCsvSelectedBulkAction::make(),
                    SafeDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductBatchesRelationManager::class,
            ProductDisposalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $jumlahKritis = ProductBatch::where('stok_toko', '>', 0)
            ->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '<=', now()->addDays(7))
            ->count();

        return $jumlahKritis > 0 ? (string) $jumlahKritis : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
