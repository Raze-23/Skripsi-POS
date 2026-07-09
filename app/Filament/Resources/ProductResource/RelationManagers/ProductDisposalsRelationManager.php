<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductBatch;
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
                    ->form([
                        Forms\Components\Select::make('product_batch_id')
                            ->label('Pilih Batch')
                            ->options(fn () => $this->getOwnerRecord()->productBatches()
                                ->where('stok_toko', '>', 0)
                                ->get()
                                ->mapWithKeys(fn ($b) => [$b->id => $b->batch_code . ' (Stok: ' . $b->stok_toko . ')'])
                            )
                            ->required()
                            ->searchable(),
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
                        $batch = ProductBatch::find($data['product_batch_id']);
                        if ($data['jumlah'] > $batch->stok_toko) {
                            Notification::make()
                                ->danger()
                                ->title('Stok Tidak Cukup!')
                                ->body("Anda mencoba membuang {$data['jumlah']} pcs, tetapi stok batch {$batch->batch_code} hanya ada {$batch->stok_toko} pcs.")
                                ->send();
                            $action->halt();
                        }
                        DB::transaction(function () use ($batch, $data) {
                            $batch->decrement('stok_toko', $data['jumlah']);
                            $batch->productDisposals()->create([
                                'jumlah' => $data['jumlah'],
                                'alasan' => $data['alasan'],
                            ]);
                        });
                        Notification::make()
                            ->success()
                            ->title('Stok Berhasil Diperbarui!')
                            ->body('Sisa stok di toko telah dikurangi.')
                            ->send();
                    })
                    ->hidden(fn(): bool => $this->getOwnerRecord()->productBatches()->sum('stok_toko') <= 0),
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
                        $batch = $record->productBatch;
                        $oldJumlah = $record->jumlah;
                        $newJumlah = $data['jumlah'];
                        $diff = $newJumlah - $oldJumlah;
                        if ($diff > 0 && $batch->stok_toko < $diff) {
                            Notification::make()
                                ->danger()
                                ->title('Stok Tidak Cukup!')
                                ->body("Koreksi gagal. Anda mencoba menambah buangan {$diff} pcs, tetapi sisa stok batch hanya {$batch->stok_toko} pcs.")
                                ->send();
                            $action->halt();
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
                            ->body('Riwayat pembuangan dan stok di toko akan di perbarui')
                            ->send();
                    }),
            ]);
    }
}
