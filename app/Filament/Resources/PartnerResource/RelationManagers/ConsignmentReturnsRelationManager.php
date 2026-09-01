<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use App\Models\ConsignmentReturn;
use App\Models\ConsignmentStock;
use App\Models\ProductDisposal;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsignmentReturnsRelationManager extends RelationManager
{
    protected static string $relationship = 'consignmentReturns';
    protected static ?string $title = 'Riwayat Penarikan';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        $cekTotalKoreksi = function (Get $get, Model $record, string $field, $value): ?string {
            $totalAwal = $record->terjual + $record->qty_layak + $record->qty_rusak;

            $t = $field === 'terjual' ? (int) $value : (int) $get('terjual');
            $l = $field === 'qty_layak' ? (int) $value : (int) $get('qty_layak');
            $r = $field === 'qty_rusak' ? (int) $value : (int) $get('qty_rusak');

            $totalBaru = $t + $l + $r;

            if ($totalBaru > $totalAwal) {
                return "Kelebihan! Total ({$totalBaru} pcs) melampaui stok titipan awal ({$totalAwal} pcs).";
            }

            if ($totalBaru < $totalAwal) {
                return "Kurang! Total ({$totalBaru} pcs) belum pas dengan stok titipan awal ({$totalAwal} pcs).";
            }

            return null;
        };

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['productBatch.product', 'sales']))
            ->recordTitleAttribute('productBatch.product.nama')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Penarikan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('sales.nama')
                    ->label('Nama Sales')
                    ->icon('heroicon-o-identification')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('productBatch.product.nama')
                    ->label('Nama Produk')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Model $record): string => $record->productBatch?->batch_code ?? '-'),
                Tables\Columns\TextColumn::make('terjual')
                    ->label('Terjual')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->suffix(' pcs')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('qty_layak')
                    ->label('Layak')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-arrow-path')
                    ->suffix(' pcs')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('qty_rusak')
                    ->label('Rusak')
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->suffix(' pcs')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('omzet_terbentuk')
                    ->label('Omzet')
                    ->money('IDR', locale: 'id')
                    ->state(function (ConsignmentReturn $record): float {
                        return $record->terjual * ($record->productBatch->product->harga_jual ?? 0);
                    })
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->weight('bold')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'menunggu_konfirmasi' => 'warning',
                        'selesai' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                        'selesai' => 'Selesai',
                        default => $state,
                    }),
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
                    })
                    ->indicateUsing(function (array $data): ?\Filament\Tables\Filters\Indicator {
                        if (empty($data['bulan']) || empty($data['tahun'])) {
                            return null;
                        }

                        $daftarBulan = [
                            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                            '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                        ];

                        $namaBulan = $daftarBulan[$data['bulan']] ?? $data['bulan'];

                        return \Filament\Tables\Filters\Indicator::make('Periode: ' . $namaBulan . ' ' . $data['tahun']);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('isi_rincian')
                    ->label('Isi Rincian')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (ConsignmentReturn $record) => $record->status === 'menunggu_konfirmasi' && Auth::user()?->role === 'mitra')
                    ->modalHeading(fn (ConsignmentReturn $record) => "Konfirmasi Rincian: {$record->productBatch->product->nama}")
                    ->modalDescription(function (ConsignmentReturn $record) {
                        $stok = ConsignmentStock::where('partner_id', $record->partner_id)
                            ->where('product_batch_id', $record->product_batch_id)
                            ->first();
                        $jumlah = $stok?->stok_titipan ?? 0;
                        return "Total rincian di bawah wajib berjumlah tepat {$jumlah} pcs (sesuai stok titipan).";
                    })
                    ->modalSubmitActionLabel('Konfirmasi & Selesaikan')
                    ->form([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('terjual')
                                    ->label('Terjual (Laku)')
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->rule('required')
                                    ->markAsRequired()
                                    ->rule('min:0')
                                    ->default(0)
                                    ->helperText('Uang masuk.'),
                                Forms\Components\TextInput::make('qty_layak')
                                    ->label('Sisa Layak Jual')
                                    ->prefixIcon('heroicon-o-arrow-path')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->rule('required')
                                    ->markAsRequired()
                                    ->rule('min:0')
                                    ->default(0)
                                    ->helperText('Kembali ke rak toko.'),
                                Forms\Components\TextInput::make('qty_rusak')
                                    ->label('Barang Rusak')
                                    ->prefixIcon('heroicon-o-archive-box-x-mark')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->rule('required')
                                    ->markAsRequired()
                                    ->rule('min:0')
                                    ->default(0)
                                    ->helperText('Dibuang & catat rugi.'),
                            ]),
                    ])
                    ->action(function (ConsignmentReturn $record, array $data, Tables\Actions\Action $action) {
                        $terjual = (int) ($data['terjual'] ?? 0);
                        $layak   = (int) ($data['qty_layak'] ?? 0);
                        $rusak   = (int) ($data['qty_rusak'] ?? 0);
                        $total   = $terjual + $layak + $rusak;

                        $stok = ConsignmentStock::where('partner_id', $record->partner_id)
                            ->where('product_batch_id', $record->product_batch_id)
                            ->first();

                        if (!$stok || $total !== $stok->stok_titipan) {
                            Notification::make()
                                ->danger()
                                ->title('Jumlah Tidak Pas!')
                                ->body("Total rincian ({$total} pcs) harus persis dengan stok titipan (" . ($stok?->stok_titipan ?? 0) . " pcs).")
                                ->send();
                            $action->halt();
                        }

                        DB::transaction(function () use ($record, $terjual, $layak, $rusak, $stok) {
                            $omzet = $terjual * ($record->productBatch->product->harga_jual ?? 0);

                            $record->update([
                                'terjual'         => $terjual,
                                'qty_layak'       => $layak,
                                'qty_rusak'       => $rusak,
                                'omzet_terbentuk' => $omzet,
                                'status'          => 'selesai',
                            ]);

                            if ($layak > 0) {
                                $record->productBatch->increment('stok_toko', $layak);
                            }

                            if ($rusak > 0) {
                                ProductDisposal::create([
                                    'product_batch_id'      => $record->product_batch_id,
                                    'jumlah'                => $rusak,
                                    'alasan'                => 'Barang Rusak',
                                    'sumber'                => 'Apotek',
                                    'consignment_return_id' => $record->id,
                                ]);
                            }

                            $stok->delete();
                        });

                        Notification::make()
                            ->success()
                            ->title('Konfirmasi Berhasil!')
                            ->body("Rincian penarikan {$record->productBatch->product->nama} telah dikonfirmasi (Laku: {$terjual}, Layak: {$layak}, Rusak: {$rusak}).")
                            ->icon('heroicon-o-check-circle')
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->label('Koreksi')
                    ->color('gray')
                    ->visible(fn (ConsignmentReturn $record) => $record->status === 'selesai' && Auth::user()?->role === 'mitra')
                    ->modalHeading(fn (Model $record) => "Koreksi Riwayat: {$record->productBatch->product->nama} ({$record->productBatch->batch_code})")
                    ->modalDescription(function (ConsignmentReturn $record) {
                        $totalAwal = $record->terjual + $record->qty_layak + $record->qty_rusak;

                        return "Total rincian di bawah wajib tetap berjumlah {$totalAwal} pcs (sesuai tarikan awal).";
                    })
                    ->form([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('terjual')
                                    ->label('Terjual (Laku)')
                                    ->numeric()
                                    ->nullable()
                                    ->rule('min:0')
                                    ->default(0)
                                    ->extraInputAttributes(['x-on:blur' => "\$el.value === '' ? (\$el.value = '0', \$el.dispatchEvent(new Event('input'))) : null"])
                                    ->rule(function (Get $get, Model $record) use ($cekTotalKoreksi) {
                                        return function (string $attribute, $value, Closure $fail) use ($get, $record, $cekTotalKoreksi) {
                                            if (blank($value)) $value = 0;
                                            if ($pesan = $cekTotalKoreksi($get, $record, 'terjual', $value)) {
                                                $t = (int) $get('terjual');
                                                $l = (int) $get('qty_layak');
                                                $r = (int) $get('qty_rusak');
                                                if ($t === 0 && $l === 0 && $r === 0) {
                                                    $fail($pesan);
                                                } else {
                                                    $lastFilled = $r > 0 ? 'qty_rusak' : ($l > 0 ? 'qty_layak' : 'terjual');
                                                    if ($lastFilled === 'terjual') $fail($pesan);
                                                }
                                            }
                                        };
                                    }),

                                Forms\Components\TextInput::make('qty_layak')
                                    ->label('Sisa Layak Jual')
                                    ->numeric()
                                    ->nullable()
                                    ->rule('min:0')
                                    ->default(0)
                                    ->extraInputAttributes(['x-on:blur' => "\$el.value === '' ? (\$el.value = '0', \$el.dispatchEvent(new Event('input'))) : null"])
                                    ->rule(function (Get $get, Model $record) use ($cekTotalKoreksi) {
                                        return function (string $attribute, $value, Closure $fail) use ($get, $record, $cekTotalKoreksi) {
                                            if (blank($value)) $value = 0;
                                            if ($pesan = $cekTotalKoreksi($get, $record, 'qty_layak', $value)) {
                                                $t = (int) $get('terjual');
                                                $l = (int) $get('qty_layak');
                                                $r = (int) $get('qty_rusak');
                                                if ($t === 0 && $l === 0 && $r === 0) {
                                                    $fail($pesan);
                                                } else {
                                                    $lastFilled = $r > 0 ? 'qty_rusak' : ($l > 0 ? 'qty_layak' : 'terjual');
                                                    if ($lastFilled === 'qty_layak') $fail($pesan);
                                                }
                                            }
                                        };
                                    }),

                                Forms\Components\TextInput::make('qty_rusak')
                                    ->label('Barang Rusak')
                                    ->numeric()
                                    ->nullable()
                                    ->rule('min:0')
                                    ->default(0)
                                    ->extraInputAttributes(['x-on:blur' => "\$el.value === '' ? (\$el.value = '0', \$el.dispatchEvent(new Event('input'))) : null"])
                                    ->rule(function (Get $get, Model $record) use ($cekTotalKoreksi) {
                                        return function (string $attribute, $value, Closure $fail) use ($get, $record, $cekTotalKoreksi) {
                                            if (blank($value)) $value = 0;
                                            if ($pesan = $cekTotalKoreksi($get, $record, 'qty_rusak', $value)) {
                                                $t = (int) $get('terjual');
                                                $l = (int) $get('qty_layak');
                                                $r = (int) $get('qty_rusak');
                                                if ($t === 0 && $l === 0 && $r === 0) {
                                                    $fail($pesan);
                                                } else {
                                                    $lastFilled = $r > 0 ? 'qty_rusak' : ($l > 0 ? 'qty_layak' : 'terjual');
                                                    if ($lastFilled === 'qty_rusak') $fail($pesan);
                                                }
                                            }
                                        };
                                    }),
                            ]),
                    ])
                    ->action(function (ConsignmentReturn $record, array $data) {
                        $terjual = (int) ($data['terjual'] ?? 0);
                        $layak   = (int) ($data['qty_layak'] ?? 0);
                        $rusak   = (int) ($data['qty_rusak'] ?? 0);
                        $totalBaru = $terjual + $layak + $rusak;
                        $totalAwal = $record->terjual + $record->qty_layak + $record->qty_rusak;

                        if ($totalBaru !== $totalAwal) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Mengoreksi Riwayat')
                                ->body("Total rincian ({$totalBaru} pcs) harus sama persis dengan tarikan awal ({$totalAwal} pcs).")
                                ->icon('heroicon-o-exclamation-triangle')
                                ->send();

                            return;
                        }

                        DB::transaction(function () use ($record, $terjual, $layak, $rusak) {
                            $selisihLayak = $layak - $record->qty_layak;
                            if ($selisihLayak !== 0) {
                                $record->productBatch->increment('stok_toko', $selisihLayak);
                            }
                            $record->update([
                                'terjual'   => $terjual,
                                'qty_layak' => $layak,
                                'qty_rusak' => $rusak,
                            ]);
                            
                            if ($rusak > 0) {
                                $record->productDisposals()->updateOrCreate([], [
                                    'product_batch_id' => $record->product_batch_id,
                                    'jumlah' => $rusak,
                                    'alasan' => 'Barang Rusak',
                                    'sumber' => 'Apotek',
                                ]);
                            } else {
                                $record->productDisposals()->delete();
                            }
                        });

                        Notification::make()
                            ->success()
                            ->title('Koreksi Riwayat Berhasil!')
                            ->body("Rincian penarikan {$record->productBatch->product->nama} diperbarui (Laku: {$terjual}, Layak Jual: {$layak}, Rusak: {$rusak}).")
                            ->icon('heroicon-o-check-circle')
                            ->send();
                    }),
            ]);
    }
}