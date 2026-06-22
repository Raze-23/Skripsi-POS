<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Stock;
use App\Filament\Resources\ProductResource\Actions\ExportCsvAllAction;
use App\Filament\Resources\ProductResource\Actions\ExportCsvSelectedBulkAction;
use App\Filament\Resources\ProductResource\Actions\ExportPdfAction;
use App\Filament\Resources\ProductResource\Actions\GenerateQRCodeAllAction;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\ProductDisposalsRelationManager;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
                            Forms\Components\Placeholder::make('filepond_css')
                                ->hiddenLabel()
                                ->content(new HtmlString('<style>
                                .filepond--root { min-height: 155px !important; }
                                    .filepond--drop-label { min-height: 155px !important; display: flex !important; align-items: center !important; justify-content: center !important; }
                                </style>')),
                            Forms\Components\Grid::make(5)
                                ->schema([
                                    Forms\Components\Group::make([
                                        Forms\Components\TextInput::make('nama')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('sku')
                                            ->label('SKU (Kode Barang)')
                                            ->hiddenOn('create')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\DatePicker::make('tanggal_kedaluwarsa')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y'),
                                    ])->columnSpan(2),
                                    Forms\Components\FileUpload::make('foto')
                                        ->image()
                                        ->directory('products')
                                        ->optimize('webp')
                                        ->resize(50)
                                        ->columnSpan(3)
                                ])
                        ]),
                    Forms\Components\Wizard\Step::make('Harga & Stok')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\TextInput::make('harga_beli')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                ->label('Modal'),
                            Forms\Components\TextInput::make('harga_jual')
                                ->numeric()
                                ->required()
                                ->prefix('Rp'),
                            Forms\Components\TextInput::make('stok_toko')
                                ->label('Stok')
                                ->numeric()
                                ->required()
                                ->default(0)
                        ])->columns(3),
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto')->circular()
                    ->default(asset('images/notfound.png')),

                Tables\Columns\TextColumn::make('sku')
                    ->label('QR Code & SKU')
                    ->formatStateUsing(function (string $state) {
                        $qr = QrCode::size(50)->generate($state);
                        return new HtmlString('<div style="display:flex; flex-direction:column; align-items:center; gap:5px; padding-top: 5px;">' . $qr . '<span style="font-size: 11px; font-family: monospace;">' . $state . '</span></div>');
                    })
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama')->searchable(),

                Tables\Columns\TextColumn::make('stok_toko')
                    ->label('Stok')
                    ->badge()
                    ->color(fn(int $state): string => $state < 10 ? 'danger' : 'success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_kedaluwarsa')
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
            ])
            ->filters([
                //
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
        $jumlahKritis = \App\Models\Product::where('stok_toko', '>', 0)
            ->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '<=', now()->addDays(30))
            ->count();

        return $jumlahKritis > 0 ? (string) $jumlahKritis : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
