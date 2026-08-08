<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductBatch;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductDisposalsRelationManager extends RelationManager
{
    protected static string $relationship = 'productDisposals';
    protected static ?string $title = 'Buang Produk';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['productBatch']))
            ->recordTitleAttribute('alasan')
            ->defaultSort('product_disposals.created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('productBatch.batch_code')
                    ->label('Batch')
                    ->fontFamily('mono')
                    ->color('gray'),
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
                                fn (Builder $query, $bulan): Builder => $query->whereMonth('product_disposals.created_at', $bulan)
                            )
                            ->when(
                                $data['tahun'],
                                fn (Builder $query, $tahun): Builder => $query->whereYear('product_disposals.created_at', $tahun)
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
            ->headerActions([
                Tables\Actions\Action::make('buang_stok')
                    ->label('Buang Stok')
                    ->icon('heroicon-o-trash')
                    ->modalSubmitActionLabel('Selesaikan Pembuangan')
                    ->modalCancelActionLabel('Kembali')
                    ->color('danger')
                    ->modalHeading(fn() => 'Form Pembuangan: ' . $this->getOwnerRecord()->nama)
                    ->modalDescription('Catat produk yang rusak, hilang, atau kedaluwarsa. Stok di toko akan otomatis terpotong.')
                    ->form([
                        Forms\Components\Select::make('product_batch_id')
                            ->label('Pilih Batch Produk')
                            ->prefixIcon('heroicon-o-tag')
                            ->options(fn () => $this->getOwnerRecord()->productBatches()
                                ->where('stok_toko', '>', 0)
                                ->get()
                                ->mapWithKeys(fn ($b) => [$b->id => $b->batch_code . ' (Sisa Stok: ' . $b->stok_toko . ')'])
                            )
                            ->live()
                            ->required()
                            ->native(false)
                            ->validationMessages([
                                'required' => 'Batch produk wajib dipilih.',
                            ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('jumlah')
                                ->label('Jumlah Dibuang')
                                ->prefixIcon('heroicon-o-archive-box-x-mark')
                                ->suffix('pcs')
                                ->numeric()
                                ->default(0)
                                ->rule('required') 
                                ->markAsRequired() 
                                ->rule('min:1')
                                ->validationMessages([
                                    'required' => 'Jumlah pembuangan wajib diisi.',
                                    'min' => 'Jumlah minimal adalah 1 pcs.',
                                ])
                                ->rule(static function (Get $get) {
                                    return static function (string $attribute, $value, Closure $fail) use ($get) {
                                        $batchId = $get('product_batch_id');
                                        if ($batchId && $value) {
                                            $batch = ProductBatch::find($batchId);
                                            if ($batch && (int) $value > $batch->stok_toko) {
                                                $fail("Stok tidak cukup! Sisa batch ini hanya {$batch->stok_toko} pcs.");
                                            }
                                        }
                                    };
                                }),
                            Forms\Components\Select::make('alasan')
                                ->label('Alasan Pembuangan')
                                ->prefixIcon('heroicon-o-exclamation-circle')
                                ->options([
                                    'Kedaluwarsa' => 'Kedaluwarsa',
                                    'Barang Rusak' => 'Barang Rusak',
                                ])
                                ->required() 
                                ->native(false)
                                ->validationMessages([
                                    'required' => 'Alasan pembuangan wajib dipilih.',
                                ]),
                        ])
                    ])
                    ->action(function (array $data) {
                        $batch = ProductBatch::find($data['product_batch_id']);
                        $jumlah = (int) $data['jumlah'];

                        if ($jumlah > $batch->stok_toko) {
                            throw ValidationException::withMessages([
                                'mountedTableActionData.jumlah' => "Kelebihan kuantitas! Sisa batch ini hanya {$batch->stok_toko} pcs.",
                            ]);
                        }

                        DB::transaction(function () use ($batch, $jumlah, $data) {
                            $batch->decrement('stok_toko', $jumlah);
                            $batch->productDisposals()->create([
                                'jumlah' => $jumlah,
                                'alasan' => $data['alasan'],
                            ]);
                        });

                        Notification::make()
                            ->success()
                            ->title('Pembuangan Berhasil Dicatat!')
                            ->body('Stok produk di rak toko telah dipotong secara otomatis.')
                            ->icon('heroicon-o-trash')
                            ->send();
                    })
                    ->hidden(fn(): bool => $this->getOwnerRecord()->productBatches()->sum('stok_toko') <= 0),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Koreksi')
                    ->color('success')
                    ->modalHeading('Koreksi Riwayat Stok')
                    ->modalDescription('Ubah data pembuangan jika terjadi salah input. Stok akan otomatis disesuaikan ulang.')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('jumlah')
                                ->label('Jumlah Sebenarnya')
                                ->prefixIcon('heroicon-o-pencil-square')
                                ->suffix('pcs')
                                ->numeric()
                                ->rule('required')
                                ->markAsRequired() 
                                ->rule('min:1')
                                ->helperText('Masukkan total fisik produk yang benar-benar dibuang.')
                                ->validationMessages([
                                    'required' => 'Jumlah sebenarnya wajib diisi.',
                                    'min' => 'Jumlah minimal adalah 1 pcs.',
                                ])
                                ->rule(static function (Get $get, Model $record) {
                                    return static function (string $attribute, $value, Closure $fail) use ($record) {
                                        if (!$value) return; 
                                        
                                        $batch = $record->productBatch;
                                        $oldJumlah = $record->jumlah;
                                        $newJumlah = (int) $value;
                                        $diff = $newJumlah - $oldJumlah;

                                        if ($diff > 0 && $batch->stok_toko < $diff) {
                                            $fail("Gagal! Anda menambah buangan {$diff} pcs, tapi sisa stok batch hanya {$batch->stok_toko} pcs.");
                                        }
                                    };
                                }),
                            Forms\Components\Select::make('alasan')
                                ->label('Alasan Pembuangan')
                                ->prefixIcon('heroicon-o-exclamation-circle')
                                ->options([
                                    'Kedaluwarsa' => 'Kedaluwarsa',
                                    'Barang Rusak' => 'Barang Rusak',
                                    'Hilang' => 'Hilang',
                                ])
                                ->required()
                                ->native(false)
                                ->validationMessages([
                                    'required' => 'Alasan pembuangan wajib dipilih.',
                                ]),
                        ])
                    ])
                    ->action(function (Model $record, array $data) {
                        $batch = $record->productBatch;
                        $oldJumlah = $record->jumlah;
                        $newJumlah = (int) $data['jumlah'];
                        $diff = $newJumlah - $oldJumlah;

                        if ($diff > 0 && $batch->stok_toko < $diff) {
                            throw ValidationException::withMessages([
                                'mountedTableActionData.jumlah' => "Koreksi gagal. Sisa stok toko ({$batch->stok_toko} pcs) tidak cukup untuk menutupi tambahan {$diff} pcs.",
                            ]);
                        }

                        DB::transaction(function () use ($batch, $record, $data, $diff, $newJumlah) {
                            if ($diff > 0) {
                                $batch->decrement('stok_toko', $diff);
                            } elseif ($diff < 0) {
                                $batch->increment('stok_toko', abs($diff));
                            }

                            $record->update([
                                'jumlah' => $newJumlah,
                                'alasan' => $data['alasan'],
                            ]);
                        });

                        Notification::make()
                            ->success()
                            ->title('Koreksi Berhasil!')
                            ->body('Riwayat pembuangan dan sisa stok toko telah dikalibrasi.')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Batal Buang')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->modalHeading('Batalkan Pembuangan')
                    ->modalDescription('Apakah Anda yakin ingin membatalkan riwayat ini? Stok yang sebelumnya dibuang akan dikembalikan sepenuhnya ke rak toko.')
                    ->modalSubmitActionLabel('Ya, Kembalikan Stok')
                    ->before(function (Model $record) {
                        DB::transaction(function () use ($record) {
                            $record->productBatch->increment('stok_toko', $record->jumlah);
                        });
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Pembuangan Dibatalkan')
                            ->body('Riwayat dihapus dan stok telah berhasil dikembalikan ke toko.')
                            ->icon('heroicon-o-check-circle')
                    ),
            ]);
    }
}