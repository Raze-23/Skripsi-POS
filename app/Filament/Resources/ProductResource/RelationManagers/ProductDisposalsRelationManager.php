<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ProductDisposalsRelationManager extends RelationManager
{
    protected static string $relationship = 'productDisposals';
    protected static ?string $title = 'Riwayat Stok';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alasan')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('alasan')
                    ->label('Alasan')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Kedaluwarsa (Expired)' => 'danger',
                        'Kemasan Rusak' => 'warning',
                        'Hilang / Selisih Stok' => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah Dibuang')
                    ->badge()
                    ->color('danger')
                    ->suffix(' pcs')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Catatan Tambahan')
                    ->wrap(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('buang_stok')
                    ->label('Buang Stok')
                    ->icon('heroicon-o-trash')
                    ->modalSubmitActionLabel('Buang')
                    ->modalCancelActionLabel('Kembali')
                    ->color('danger')
                    ->modalHeading(fn() => 'Form Pembuangan: ' . $this->getOwnerRecord()->nama)
                    ->modalDescription(fn() => 'Sisa Stok di Gudang saat ini: ' . $this->getOwnerRecord()->stok_toko . ' pcs. Stok akan dikurangi secara permanen.')
                    ->form([
                        Forms\Components\TextInput::make('jumlah')
                            ->label('Jumlah Dibuang')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\Select::make('alasan')
                            ->label('Alasan')
                            ->options([
                                'Kedaluwarsa' => 'Kedaluwarsa',
                                'Barang Rusak' => 'Barang Rusak',
                                'Hilang' => 'Hilang',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (array $data, Tables\Actions\Action $action) {
                        $product = $this->getOwnerRecord();
                        if ($data['jumlah'] > $product->stok_toko) {
                            Notification::make()
                                ->danger()
                                ->title('Stok Tidak Cukup!')
                                ->body("Anda mencoba membuang {$data['jumlah']} pcs, tetapi stok di toko hanya ada {$product->stok_toko} pcs.")
                                ->send();
                            $action->halt();
                        }
                        DB::transaction(function () use ($product, $data) {
                            $product->decrement('stok_toko', $data['jumlah']);
                            $product->productDisposals()->create([
                                'jumlah' => $data['jumlah'],
                                'alasan' => $data['alasan'],
                            ]);
                        });
                        Notification::make()
                            ->success()
                            ->title('Stok Berhasil Diperbarui!')
                            ->body('Sisa stok gudang telah diperbarui.')
                            ->send();
                    })
                    ->hidden(fn(): bool => $this->getOwnerRecord()->stok_toko <= 0),
            ])
            ->actions([]);
    }
}
