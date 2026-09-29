<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Stock;
use App\Filament\Resources\PartnerResource\Pages;
use App\Filament\Resources\PartnerResource\RelationManagers\ConsignmentReturnsRelationManager;
use App\Filament\Resources\PartnerResource\RelationManagers\ConsignmentStockRelationManager;
use App\Models\Partner;
use App\Models\ConsignmentReturn;
use App\Models\ProductBatch;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-s-home-modern';

    protected static ?string $cluster = Stock::class;

    protected static ?string $navigationLabel = 'Apotek';

    protected static ?string $breadCrumb = 'Stok';

    protected static ?string $pluralLabel = 'Apotek';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Kemitraan')
                    ->description('Admin hanya mengelola identitas apotek dan tanggal dimulainya kerja sama.')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Forms\Components\TextInput::make('nama_apotek')
                            ->label('Nama Apotek')
                            ->prefixIcon('heroicon-o-building-storefront')
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'Nama apotek wajib diisi.',
                            ]),

                        DatePicker::make('tanggal_kerja_sama')
                            ->label('Tanggal Kerja Sama')
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->default(today())
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->required()
                            ->validationMessages([
                                'required' => 'Tanggal kerja sama wajib dipilih.',
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi dari Profil Mitra')
                    ->description('Nomor telepon dan alamat dikelola langsung oleh Mitra melalui halaman profil mereka.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\Placeholder::make('partner_phone_info')
                            ->label('Nomor Telepon')
                            ->content(fn (?Partner $record): string => filled($record?->no_telp)
                                ? $record->no_telp
                                : 'Belum dilengkapi oleh Mitra'),

                        Forms\Components\Placeholder::make('partner_status_info')
                            ->label('Status Kemitraan')
                            ->content(fn (?Partner $record): string => $record?->is_active === false
                                ? 'Tidak Aktif'
                                : 'Aktif'),

                        Forms\Components\Placeholder::make('partner_address_info')
                            ->label('Alamat Lengkap')
                            ->content(fn (?Partner $record): string => filled($record?->alamat)
                                ? $record->alamat
                                : 'Belum dilengkapi oleh Mitra')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_apotek')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('no_telp')
                    ->label('Nomor Telepon')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->placeholder('Belum dilengkapi')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->searchable()
                    ->placeholder('Belum dilengkapi')
                    ->wrap()
                    ->limit(55)
                    ->tooltip(fn (Partner $record): ?string => filled($record->alamat) ? $record->alamat : null),
                Tables\Columns\TextColumn::make('tanggal_kerja_sama')
                    ->label('Kerja Sama Sejak')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('jumlah_kedaluwarsa')
                    ->label('Kedaluwarsa')
                    ->state(function (Partner $record): int {
                        return $record->consignmentStocks()
                            ->where('stok_titipan', '>', 0)
                            ->whereHas('productBatch', function ($q) {
                                $q->whereNotNull('tanggal_kedaluwarsa')
                                    ->whereDate('tanggal_kedaluwarsa', '<=', now()->addDays(30));
                            })
                            ->count();
                    })
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->alignCenter()
                    ->default(0),
                Tables\Columns\TextColumn::make('pembayaran_menunggu_validasi')
                    ->label('Validasi Pembayaran')
                    ->state(fn (Partner $record): int => ConsignmentReturn::where('partner_id', $record->id)
                        ->where('status', 'menunggu_validasi')->count())
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->url(fn (Partner $record): string => static::getUrl('edit', [
                        'record' => $record,
                        'activeRelationManager' => '1',
                    ])),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('riwayat_penarikan')
                        ->label('Riwayat Penarikan')
                        ->icon('heroicon-o-receipt-refund')
                        ->url(fn (Partner $record): string => static::getUrl('edit', [
                            'record' => $record,
                            'activeRelationManager' => '1',
                        ])),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ConsignmentStockRelationManager::class,
            ConsignmentReturnsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $jumlahKritis = ProductBatch::whereHas('consignmentStocks', function ($query) {
            $query->where('stok_titipan', '>', 0);
        })
            ->whereNotNull('tanggal_kedaluwarsa')
            ->whereDate('tanggal_kedaluwarsa', '<=', now()->addDays(30))
            ->count();

        return $jumlahKritis > 0 ? (string) $jumlahKritis : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }
}
