<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
                        'Kedaluwarsa (Expired)', 'Kedaluwarsa' => 'danger',
                        'Kemasan Rusak', 'Barang Rusak' => 'warning',
                        'Hilang / Selisih ', 'Hilang' => 'gray',
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
            ->filters([
                Tables\Filters\Filter::make('bulan_tahun')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('bulan')
                                ->label('Bulan')
                                ->options([
                                    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                                    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                                    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                                    '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                                ])
                                ->default(now()->format('m')),
                            Forms\Components\Select::make('tahun')
                                ->label('Tahun')
                                ->options(function () {
                                    $years = [];
                                    $currentYear = now()->year;
                                    for ($i = $currentYear - 3; $i <= $currentYear + 1; $i++) {
                                        $years[$i] = $i;
                                    }
                                    return $years;
                                })
                                ->default(now()->year),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['bulan'],
                                fn (Builder $query, $bulan): Builder => $query->whereMonth('created_at', $bulan)
                            )
                            ->when(
                                $data['tahun'],
                                fn (Builder $query, $tahun): Builder => $query->whereYear('created_at', $tahun)
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('buang_stok')
                    ->label('Buang Stok')
                    ->icon('heroicon-o-trash')
                    ->modalSubmitActionLabel('Buang')
                    ->modalCancelActionLabel('Kembali')
                    ->color('danger')
                    ->modalHeading(fn() => 'Form Pembuangan: ' . $this->getOwnerRecord()->nama)
                    ->modalDescription(fn() => 'Sisa Stok di Toko saat ini: ' . $this->getOwnerRecord()->stok_toko . ' pcs. Stok akan dikurangi secara permanen.')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
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
                                'keterangan' => $data['keterangan'] ?? null,
                            ]);
                        });
                        Notification::make()
                            ->success()
                            ->title('Stok Berhasil Diperbarui!')
                            ->body('Sisa stok di toko telah dikurangi.')
                            ->send();
                    })
                    ->hidden(fn(): bool => $this->getOwnerRecord()->stok_toko <= 0),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->color('warning')
                    ->modalHeading('Koreksi Riwayat Stok')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('jumlah')
                                ->label('Jumlah Sebenarnya')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->helperText('Otomatis sesuaikan stok rak.'),
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
                    ])
                    ->action(function (Model $record, array $data, Tables\Actions\EditAction $action) {
                        $product = $this->getOwnerRecord();
                        $oldJumlah = $record->jumlah;
                        $newJumlah = $data['jumlah'];
                        $diff = $newJumlah - $oldJumlah;
                        if ($diff > 0 && $product->stok_toko < $diff) {
                            Notification::make()
                                ->danger()
                                ->title('Stok Tidak Cukup!')
                                ->body("Koreksi gagal. Anda mencoba menambah buangan {$diff} pcs, tetapi sisa stok hanya {$product->stok_toko} pcs.")
                                ->send();
                            $action->halt();
                        }

                        DB::transaction(function () use ($product, $record, $data, $diff, $newJumlah) {
                            if ($diff > 0) {
                                $product->decrement('stok_toko', $diff);
                            } elseif ($diff < 0) {
                                $product->increment('stok_toko', abs($diff));
                            }

                            $record->update([
                                'jumlah' => $newJumlah,
                                'alasan' => $data['alasan'],
                            ]);
                        });

                        Notification::make()
                            ->success()
                            ->title('Koreksi Berhasil!')
                            ->body('Riwayat pembuangan dan stok di toko akan di perbarui')
                            ->send();
                    }),
            ]);
    }
}
