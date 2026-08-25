<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Stock;
use App\Filament\Resources\PartnerResource\Pages;
use App\Filament\Resources\PartnerResource\RelationManagers\ConsignmentReturnsRelationManager;
use App\Filament\Resources\PartnerResource\RelationManagers\ConsignmentStockRelationManager;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make('Informasi Apotek')
                            ->schema([
                                Forms\Components\TextInput::make('nama_apotek')
                                    ->label('Nama Apotek')
                                    ->rule('required') 
                                    ->markAsRequired() 
                                    ->maxLength(255)
                                    ->validationMessages([
                                        'required' => 'Nama apotek wajib diisi.',
                                    ]),
                                Forms\Components\Textarea::make('alamat')
                                    ->label('Alamat Lengkap')
                                    ->rule('required') 
                                    ->markAsRequired()
                                    ->rows(3)
                                    ->validationMessages([
                                        'required' => 'Alamat lengkap apotek wajib diisi.',
                                    ]),
                            ])
                            ->columnSpan(2),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Kontak & Status')
                                    ->schema([
                                        Forms\Components\TextInput::make('no_telp')
                                            ->label('Nomor Telepon')
                                            ->tel()
                                            ->rule('min:10')
                                            ->maxLength(15)
                                            ->rule('regex:/^(\+62|62|08)[0-9]+$/') 
                                            ->validationMessages([
                                                'min' => 'Nomor telepon tidak valid, minimal 10 digit.',
                                                'regex' => 'Format tidak valid. Harus berupa angka dan diawali dengan 08, 62, atau +62.',
                                            ]),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Status Kemitraan')
                                            ->helperText('Nonaktifkan jika kerjasama berakhir')
                                            ->default(true)
                                            ->onColor('success')
                                            ->offColor('danger')
                                            ->visibleOn('edit'),
                                    ]),
                                DatePicker::make('tanggal_kerja_sama')
                                    ->label('Kerja Sama Sejak')
                                    ->default(today())
                                    ->native(false)
                                    ->displayFormat('d F Y')
                            ])
                            ->columnSpan(1),
                    ]),
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
                    ->color('gray'),
                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->searchable(),
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
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
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
        $jumlahKritis = \App\Models\ProductBatch::whereHas('consignmentStocks', function ($query) {
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
}