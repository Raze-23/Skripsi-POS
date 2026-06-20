<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use App\Models\ConsignmentReturn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ConsignmentReturnsRelationManager extends RelationManager
{
    protected static string $relationship = 'consignmentReturns';
    protected static ?string $title = 'Riwayat penarikan';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.nama')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Penarikan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.nama')
                    ->label('Nama Produk')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('terjual')
                    ->label('Terjual')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('qty_layak')
                    ->label('Layak')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('qty_rusak')
                    ->label('Rusak')
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('omzet_terbentuk')
                    ->label('Omzet')
                    ->money('IDR', locale: 'id')
                    ->state(function (ConsignmentReturn $record): float {
                        return $record->terjual * ($record->product->harga_jual ?? 0);
                    })
                    ->color('primary')
                    ->weight('bold'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Koreksi')
                    ->color('gray')
                    ->modalHeading(fn(Model $record) => "Koreksi Riwayat: {$record->product->nama}")
                    ->form([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('terjual')
                                    ->label('Terjual (Laku)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),

                                Forms\Components\TextInput::make('qty_layak')
                                    ->label('Sisa Layak Jual')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),

                                Forms\Components\TextInput::make('qty_rusak')
                                    ->label('Barang Rusak')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                            ]),
                    ])
                    ->action(function (ConsignmentReturn $record, array $data, Tables\Actions\EditAction $action) {
                        $totalAwal = $record->terjual + $record->qty_layak + $record->qty_rusak;
                        $totalBaru = $data['terjual'] + $data['qty_layak'] + $data['qty_rusak'];
                        if ($totalBaru !== $totalAwal) {
                            Notification::make()
                                ->danger()
                                ->title('Koreksi Gagal!')
                                ->body("Total kuantitas barang baru ({$totalBaru} pcs) tidak sama dengan total awal penarikan ({$totalAwal} pcs). Anda hanya boleh mengubah distribusi kriteria produk.")
                                ->send();
                            $action->halt();
                        }
                        DB::transaction(function () use ($record, $data) {
                            $selisihLayak = $data['qty_layak'] - $record->qty_layak;
                            if ($selisihLayak !== 0) {
                                $record->product->increment('stok_toko', $selisihLayak);
                            }
                            $record->update([
                                'terjual'   => $data['terjual'],
                                'qty_layak' => $data['qty_layak'],
                                'qty_rusak' => $data['qty_rusak'],
                            ]);
                        });
                        Notification::make()
                            ->success()
                            ->title('Riwayat Berhasil Dikoreksi!')
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }
}
