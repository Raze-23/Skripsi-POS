<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Stock;
use App\Filament\Resources\ProductResource\Actions\ExportCsvAllAction;
use App\Filament\Resources\ProductResource\Actions\ExportCsvSelectedBulkAction;
use App\Filament\Resources\ProductResource\Actions\ExportPdfAction;
use App\Filament\Resources\ProductResource\Actions\GenerateQRCodeAllAction;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\ProductBatchesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\ProductDisposalsRelationManager;
use App\Models\Product;
use App\Models\ProductBatch;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

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
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('estimasi_masak')
                                            ->label('Estimasi Waktu Pembuatan')
                                            ->numeric()
                                            ->required()
                                            ->suffix('Menit')
                                            ->default(0)
                                            ->minValue(0),
                                    ])->columnSpan(2),
                                    Forms\Components\FileUpload::make('foto')
                                        ->image()
                                        ->directory('products')
                                        ->optimize('webp')
                                        ->resize(50)
                                        ->columnSpan(3)
                                ])
                        ]),
                    Forms\Components\Wizard\Step::make('Harga')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\TextInput::make('harga_beli')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                ->label('Modal')
                                ->rule(static function (Get $get) {
                                    return static function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $hargaJual = $get('harga_jual');
                                        if ($hargaJual !== null && $value >= $hargaJual) {
                                            $fail('Harga Modal tidak boleh lebih dari Harga Jual!');
                                        }
                                    };
                                }),
                            Forms\Components\TextInput::make('harga_jual')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                ->rule(static function (Get $get) {
                                    return static function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $hargaBeli = $get('harga_beli');
                                        if ($hargaBeli !== null && $value <= $hargaBeli) {
                                            $fail('Harga Jual harus lebih besar dari Harga Modal!');
                                        }
                                    };
                                }),
                        ])->columns(2),
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto')->circular()
                    ->default(asset('images/notfound.png')),
                Tables\Columns\TextColumn::make('nama')->searchable(),
                Tables\Columns\TextColumn::make('estimasi_masak')
                    ->label('Waktu Produksi')
                    ->suffix(' Menit')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('product_batches_sum_stok_toko')
                    ->label('Total Stok')
                    ->badge()
                    ->color(fn($state): string => ($state ?? 0) < 10 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_batches_min_tanggal_kedaluwarsa')
                    ->label('Kedaluwarsa Terdekat')
                    ->date('d M Y')
                    ->badge()
                    ->color(function ($state): string {
                        if (!$state) return 'gray';
                        $daysLeft = now()->startOfDay()->diffInDays(Carbon::parse($state)->startOfDay(), false);
                        return match (true) {
                            $daysLeft < 7  => 'danger',
                            $daysLeft < 30 => 'warning',
                            $daysLeft < 60 => 'info',
                            default        => 'success',
                        };
                    })
                    ->icon(function ($state): string {
                        if (!$state) return 'heroicon-o-minus';
                        $daysLeft = now()->startOfDay()->diffInDays(Carbon::parse($state)->startOfDay(), false);
                        return match (true) {
                            $daysLeft < 7  => 'heroicon-o-x-circle',
                            $daysLeft < 30 => 'heroicon-o-exclamation-triangle',
                            $daysLeft < 60 => 'heroicon-o-clock',
                            default        => 'heroicon-o-shield-check',
                        };
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_batches_count')
                    ->label('Jumlah Batch')
                    ->counts('productBatches')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
            ])
            ->modifyQueryUsing(fn ($query) => $query
                ->withSum('productBatches', 'stok_toko')
                ->withMin('productBatches', 'tanggal_kedaluwarsa')
            )
            ->filters([
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
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
                    Tables\Actions\DeleteBulkAction::make(),
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
