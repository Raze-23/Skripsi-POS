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
use Illuminate\Support\Facades\Auth;
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
                    ->modalDescription(fn (Model $record) => "Mengajukan penarikan {$record->stok_titipan} pcs. Rincian (terjual/layak/rusak) akan diisi oleh pihak Mitra.")
                    ->modalSubmitActionLabel('Ajukan Penarikan')
                    ->modalCancelActionLabel('Batal')
                    ->visible(function () {
                        return Auth::user()?->role !== 'mitra';
                    })
                    ->mountUsing(function (Model $record, ?Forms\Form $form, Tables\Actions\Action $action) {
                        $sudahDiajukan = ConsignmentReturn::where('product_batch_id', $record->product_batch_id)
                            ->where('partner_id', $this->getOwnerRecord()->id)
                            ->where('status', 'menunggu_konfirmasi')
                            ->exists();

                        if ($sudahDiajukan) {
                            Notification::make()
                                ->warning()
                                ->title('Sudah Pernah Dikonfirmasi')
                                ->body("Penarikan {$record->productBatch->product->nama} (Batch: {$record->productBatch->batch_code}) sudah diajukan sebelumnya dan masih menunggu konfirmasi dari Mitra.")
                                ->icon('heroicon-o-exclamation-triangle')
                                ->send();

                            $action->halt();
                        }

                        $form?->fill();
                    })
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
                            ]),
                    ])
                    ->action(function (Model $record, array $data) {
                        $berhasil = false;
                        DB::transaction(function () use ($record, $data, &$berhasil) {
                            $sudahDiajukan = ConsignmentReturn::where('product_batch_id', $record->product_batch_id)
                                ->where('partner_id', $this->getOwnerRecord()->id)
                                ->where('status', 'menunggu_konfirmasi')
                                ->lockForUpdate()
                                ->exists();

                            if ($sudahDiajukan) {
                                return;
                            }

                            ConsignmentReturn::create([
                                'partner_id'       => $this->getOwnerRecord()->id,
                                'product_batch_id'  => $record->product_batch_id,
                                'sales_id'          => $data['sales_id'],
                                'terjual'           => 0,
                                'qty_layak'         => 0,
                                'qty_rusak'         => 0,
                                'omzet_terbentuk'   => 0,
                                'status'            => 'menunggu_konfirmasi',
                            ]);

                            $berhasil = true;
                        });

                        if (! $berhasil) {
                            Notification::make()
                                ->warning()
                                ->title('Sudah Pernah Dikonfirmasi')
                                ->body("Penarikan untuk {$record->productBatch->product->nama} sudah diajukan sebelumnya dan masih menunggu konfirmasi dari Mitra.")
                                ->icon('heroicon-o-exclamation-triangle')
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Penarikan Diajukan!')
                            ->body("Menunggu konfirmasi rincian dari Mitra untuk {$record->productBatch->product->nama} ({$record->stok_titipan} pcs).")
                            ->icon('heroicon-o-clock')
                            ->send();
                    }),
            ]);
    }
}