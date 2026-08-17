<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use App\Models\ConsignmentDelivery;
use App\Models\ConsignmentReturn;
use App\Models\ProductBatch;
use App\Models\ProductDisposal;
use App\Models\Sales; 
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
                    ->modalDescription('Kirim stok untuk mitra apotek dan catat sales yang mengantarnya.')
                    ->modalSubmitActionLabel('Kirim Produk')
                    ->form([
                        Forms\Components\Select::make('sales_id')
                            ->label('Nama Sales Pengirim')
                            ->prefixIcon('heroicon-o-identification')
                            ->options(fn () => Sales::where('is_active', true)->pluck('nama', 'id'))
                            ->searchable()
                            ->preload()
                            ->rule('required')
                            ->markAsRequired()
                            ->native(false)
                            ->validationMessages([
                                'required' => 'Identitas Sales wajib dipilih.',
                            ]),

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
                                ->rule('required')
                                ->markAsRequired()
                                ->native(false)
                                ->validationMessages([
                                    'required' => 'Batch produk wajib dipilih.',
                                ]),

                            Forms\Components\TextInput::make('jumlah')
                                ->label('Jumlah Dikirim')
                                ->prefixIcon('heroicon-o-cube')
                                ->suffix('pcs')
                                ->numeric()
                                ->rule('required')
                                ->markAsRequired()
                                ->rule('min:1')
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
                        $salesId = $data['sales_id'];

                        DB::transaction(function () use ($batch, $jumlah, $salesId) {
                            $batch->decrement('stok_toko', $jumlah);
            
                            $consignment = $this->getOwnerRecord()->consignmentStocks()
                                ->firstOrCreate(
                                    ['product_batch_id' => $batch->id],
                                    ['stok_titipan' => 0]
                                );
                            $consignment->increment('stok_titipan', $jumlah);

                            ConsignmentDelivery::create([
                                'partner_id' => $this->getOwnerRecord()->id,
                                'product_batch_id' => $batch->id,
                                'sales_id' => $salesId,
                                'jumlah' => $jumlah,
                            ]);
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
                        Forms\Components\Select::make('sales_id')
                            ->label('Nama Sales Penarik')
                            ->prefixIcon('heroicon-o-identification')
                            ->options(fn () => Sales::where('is_active', true)->pluck('nama', 'id'))
                            ->searchable()
                            ->preload()
                            ->rule('required')
                            ->markAsRequired()
                            ->native(false)
                            ->validationMessages([
                                'required' => 'Identitas Sales wajib dipilih.',
                            ])
                            ->columnSpanFull(),

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
                                    ->validationMessages([
                                        'required' => 'Wajib diisi.',
                                        'min' => 'Minimal 0.',
                                    ])
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
                                    ->validationMessages([
                                        'required' => 'Wajib diisi.',
                                        'min' => 'Minimal 0.',
                                    ])
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
                                    ->validationMessages([
                                        'required' => 'Wajib diisi.',
                                        'min' => 'Minimal 0.',
                                    ])
                                    ->helperText('Dibuang & catat rugi.'),
                            ]),
                    ])
                    ->action(function (Model $record, array $data, Tables\Actions\Action $action) {
                        $terjual = (int) ($data['terjual'] ?? 0);
                        $layak   = (int) ($data['qty_layak'] ?? 0);
                        $rusak   = (int) ($data['qty_rusak'] ?? 0);
                        $salesId = $data['sales_id'];
                        
                        $total = $terjual + $layak + $rusak;

                        if ($total !== $record->stok_titipan) {
                            Notification::make()
                                ->danger()
                                ->title('Jumlah Tidak Pas!')
                                ->body("Total rincian ({$total} pcs) harus persis dengan sisa stok ({$record->stok_titipan} pcs).")
                                ->send();

                            $action->halt(); 
                        }

                        DB::transaction(function () use ($record, $terjual, $layak, $rusak, $salesId) {
                            if ($layak > 0) {
                                $record->productBatch->increment('stok_toko', $layak);
                            }

                            $return = ConsignmentReturn::create([
                                'partner_id' => $this->getOwnerRecord()->id,
                                'product_batch_id' => $record->product_batch_id,
                                'sales_id' => $salesId,
                                'terjual'    => $terjual,
                                'qty_layak'  => $layak,
                                'qty_rusak'  => $rusak,
                                'omzet_terbentuk' => 0,
                            ]);


                            if ($rusak > 0) {
                                ProductDisposal::create([
                                    'product_batch_id'      => $record->product_batch_id,
                                    'jumlah'                => $rusak,
                                    'alasan'                => 'Barang Rusak',
                                    'sumber'                => 'Apotek',
                                    'consignment_return_id' => $return->id,
                                ]);
                            }

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