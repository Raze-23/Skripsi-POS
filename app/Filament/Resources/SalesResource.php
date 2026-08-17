<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Stock;
use App\Filament\Resources\SalesResource\Pages;
use App\Models\Sales;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SalesResource extends Resource
{
    protected static ?string $model = Sales::class;

    protected static ?string $navigationIcon = 'heroicon-s-identification';

    protected static ?string $cluster = Stock::class;

    protected static ?string $navigationLabel = 'Daftar Sales';

    protected static ?string $pluralLabel = 'Sales';

    protected static ?string $breadcrumb = 'Sales';

    protected static ?int $navigationSort = 3; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Identitas Sales')
                    ->description('Masukkan detail kontak dan status kepegawaian sales lapangan.')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->prefixIcon('heroicon-o-user')
                            ->rule('required')
                            ->markAsRequired()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'Nama lengkap sales wajib diisi.',
                            ]),

                        Forms\Components\TextInput::make('no_telp')
                            ->label('Nomor Telepon')
                            ->prefixIcon('heroicon-o-phone')
                            ->tel()
                            ->rule('required')
                            ->markAsRequired()
                            ->rule('min:10')
                            ->maxLength(15)
                            ->rule('regex:/^(\+62|62|08)[0-9]+$/')
                            ->validationMessages([
                                'required' => 'Nomor Telepon wajib diisi.',
                                'min' => 'Nomor telepon tidak valid, minimal 10 digit.',
                                'regex' => 'Format tidak valid. Harus berupa angka dan diawali dengan 08, 62, atau +62.',
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->helperText('Matikan toggle ini jika sales sudah resign atau berhenti bertugas.')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Sales')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('no_telp')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->default('-')
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status Pegawai')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Resign',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record, Tables\Actions\DeleteAction $action) {
                            if ($record->consignmentDeliveries()->exists() || $record->consignmentReturns()->exists()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Gagal Menghapus')
                                    ->body('Data sales tidak bisa dihapus karena memiliki rekam jejak pengiriman atau penarikan barang.')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->send();
                                
                                $action->halt();
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->danger()
                                ->title('Berhasil Dihapus!')
                                ->body('Identitas sales telah dihapus secara permanen.')
                                ->icon('heroicon-o-trash')
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSales::route('/create'),
            'edit' => Pages\EditSales::route('/{record}/edit'),
        ];
    }
}