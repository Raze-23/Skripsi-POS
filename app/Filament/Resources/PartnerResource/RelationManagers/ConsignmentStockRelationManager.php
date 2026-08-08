<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use App\Models\ConsignmentReturn;
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

class ConsignmentStockRelationManager extends RelationManager
{
    protected static string $relationship = 'consignmentStocks';
    protected static ?string $title = 'Produk titipan';
    protected static ?string $breadcrumb = 'Menitipkan Produk';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['productBatch.product']))
            ->recordTitleAttribute('productBatch.product.nama')
            ->columns([
                Tables\Columns\TextColumn::make('productBatch.batch_code')
                    ->label('Kode Batch')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('productBatch.product.nama')
                    ->label('Nama Produk Herbal')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('stok_titipan')
                    ->label('Stok Saat Ini')
                    ->badge()
                    ->color('success')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('titip_stok')
                    ->label('Kirim Stok Titipan')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->modalHeading('Kirim Stok ke Mitra')
                    ->modalDescription('Kirim stok untuk mitra apotek.')
                    ->modalSubmitActionLabel('Kirim Produk')
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('product_batch_id')
                                ->label('Pilih Batch Produk')
                                ->prefixIcon('heroicon-o-tag')
                                ->options(fn () => ProductBatch::where('stok_toko', '>', 0)
                                    ->with('product')
                                    ->get()
                                    ->mapWithKeys(fn ($b) => [$b->id => $b->product->nama . ' — ' . $b->batch_code . ' (Stok: ' . $b->stok_toko . ')'])
                                )
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required(),

                            Forms\Components\TextInput::make('jumlah')
                                ->label('Jumlah Dikirim')
                                ->prefixIcon('heroicon-o-cube')
                                ->suffix('pcs')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->default(1)
                                ->validationMessages([
                                    'required' => 'Jumlah kirim wajib diisi.',
                                    'min' => 'Jumlah kirim minimal 1 pcs.',
                                ])
                                ->helperText('Otomatis memotong stok toko dan dipindah ke etalase mitra.')
                                ->rule(static function (Get $get) {
                                    return static function (string $attribute, $value, Closure $fail) use ($get) {
                                        $batchId = $get('product_batch_id');
                                        $batch = $batchId ? ProductBatch::find($batchId) : null;

                                        if ($batch && (int) $value > $batch->stok_toko) {
                                            $fail("Stok toko tidak cukup! Sisa hanya {$batch->stok_toko} pcs.");
                                        }
                                    };
                                }),
                        ]),
                    ])
                    ->action(function (array $data) {
                        $batch = ProductBatch::find($data['product_batch_id']);
                        $jumlah = (int) ($data['jumlah'] ?? 0);

                        DB::transaction(function () use ($batch, $jumlah) {
                            $batch->decrement('stok_toko', $jumlah);
                            $consignment = $this->getOwnerRecord()->consignmentStocks()
                                ->firstOrCreate(
                                    ['product_batch_id' => $batch->id],
                                    ['stok_titipan' => 0]
                                );
                            $consignment->increment('stok_titipan', $jumlah);
                        });

                        Notification::make()
                            ->success()
                            ->title('Stok Titipan Terkirim!')
                            ->body("Berhasil mengirim {$jumlah} pcs {$batch->product->nama} (Batch: {$batch->batch_code}).")
                            ->icon('heroicon-o-check-badge')
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('tarik_barang')
                    ->label('Tarik Barang')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('danger')
                    ->modalHeading(fn (Model $record) => "Penarikan: {$record->productBatch->product->nama} ({$record->productBatch->batch_code})")
                    ->modalDescription(fn (Model $record) => "Total rincian di bawah wajib berjumlah tepat {$record->stok_titipan} pcs (Sesuai sisa titipan).")
                    ->modalSubmitActionLabel('Selesaikan Penarikan')
                    ->modalCancelActionLabel('Tutup')
                    ->form([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('terjual')
                                    ->label('Terjual (Laku)')
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('Uang masuk.'),

                                Forms\Components\TextInput::make('qty_layak')
                                    ->label('Sisa Layak Jual')
                                    ->prefixIcon('heroicon-o-arrow-path')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('Kembali ke rak toko.'),

                                Forms\Components\TextInput::make('qty_rusak')
                                    ->label('Barang Rusak')
                                    ->prefixIcon('heroicon-o-archive-box-x-mark')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('Dibuang & catat rugi.'),
                            ]),
                    ])
                    ->action(function (Model $record, array $data, Tables\Actions\Action $action) {
                        $terjual = (int) ($data['terjual'] ?? 0);
                        $layak   = (int) ($data['qty_layak'] ?? 0);
                        $rusak   = (int) ($data['qty_rusak'] ?? 0);
                        
                        $total = $terjual + $layak + $rusak;

                        if ($total !== $record->stok_titipan) {
                            Notification::make()
                                ->danger()
                                ->title('Total Rincian Tidak Sesuai!')
                                ->body("Total (Terjual + Layak + Rusak) harus tepat {$record->stok_titipan} pcs. Saat ini hitungan Anda adalah {$total} pcs.")
                                ->send();

                            $action->halt(); 
                        }

                        DB::transaction(function () use ($record, $terjual, $layak, $rusak) {
                            if ($layak > 0) {
                                $record->productBatch->increment('stok_toko', $layak);
                            }

                            ConsignmentReturn::create([
                                'partner_id' => $this->getOwnerRecord()->id,
                                'product_batch_id' => $record->product_batch_id,
                                'terjual'    => $terjual,
                                'qty_layak'  => $layak,
                                'qty_rusak'  => $rusak,
                                'omzet_terbentuk' => 0,
                            ]);

                            $record->delete();
                        });

                        Notification::make()
                            ->success()
                            ->title('Barang Berhasil Ditarik!')
                            ->body("Data penarikan {$record->productBatch->product->nama} berhasil dicatat ke sistem.")
                            ->icon('heroicon-o-clipboard-document-check')
                            ->send();
                    }),
            ]);
    }
}