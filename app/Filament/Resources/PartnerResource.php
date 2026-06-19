<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Stock;
use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
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
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('alamat')
                                    ->label('Alamat Lengkap')
                                    ->required()
                                    ->rows(3),
                            ])
                            ->columnSpan(2),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Kontak & Status')
                                    ->schema([
                                        Forms\Components\TextInput::make('no_telp')
                                            ->label('Nomor WhatsApp')
                                            ->tel()
                                            ->required()
                                            ->maxLength('12'),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Status Kemitraan')
                                            ->helperText('Nonaktifkan jika kerjasama berakhir')
                                            ->default(true)
                                            ->onColor('success')
                                            ->offColor('danger')
                                            ->visibleOn('edit'),
                                    ]),
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Terdaftar Sejak')
                                    ->content(fn($record): string => $record ? $record->created_at->diffForHumans() : '-')
                                    ->visibleOn('edit'),
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
                    ->label('Nomo Telpon')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('alamat')
                    ->label('alamat'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
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
}
