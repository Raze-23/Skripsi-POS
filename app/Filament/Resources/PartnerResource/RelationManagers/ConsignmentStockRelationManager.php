<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use App\Models\ConsignmentReturn;
use App\Models\ProductBatch;
use App\Models\Sales;
use App\Services\ConsignmentDeliveryService;
use Closure;
use DomainException;
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

                Tables\Columns\TextColumn::make('diskon_persen')
                    ->label('Diskon')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.').'%')
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('harga_satuan')
                    ->label('Harga Mitra')
                    ->money('IDR', locale: 'id')
                    ->description('per pcs')
                    ->weight('semibold'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('titip_stok')
                    ->label('Kirim Stok Titipan')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->visible(fn () => $this->getOwnerRecord()->users()->exists())
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
                                    ->whereDate('tanggal_kedaluwarsa', '>=', now())
                                    ->with('product')
                                    ->get()
                                    ->mapWithKeys(fn ($b) => [$b->id => $b->product->nama.' — '.$b->batch_code.' (Stok: '.$b->stok_toko.')'])
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

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('diskon_persen')
                                ->label('Diskon Harga Mitra (Opsional)')
                                ->prefixIcon('heroicon-o-receipt-percent')
                                ->suffix('%')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01)
                                ->live(debounce: 250)
                                ->nullable()
                                ->helperText('Kosongkan jika produk dikirim dengan harga normal.')
                                ->validationMessages([
                                    'min' => 'Diskon tidak boleh kurang dari 0%.',
                                    'max' => 'Diskon tidak boleh lebih dari 100%.',
                                ]),

                            Forms\Components\Placeholder::make('harga_setelah_diskon')
                                ->label('Harga Jual Mitra')
                                ->content(function (Get $get): string {
                                    $batch = ProductBatch::with('product')->find($get('product_batch_id'));
                                    if (! $batch) {
                                        return 'Pilih batch produk terlebih dahulu';
                                    }

                                    $discount = min(100, max(0, (float) ($get('diskon_persen') ?? 0)));

                                    return 'Rp '.number_format(
                                        ConsignmentDeliveryService::discountedPrice((int) $batch->product->harga_jual, $discount),
                                        0,
                                        ',',
                                        '.',
                                    ).' / pcs';
                                }),
                        ]),
                    ])
                    ->action(function (array $data, Tables\Actions\Action $action) {
                        $batch = ProductBatch::with('product')->find($data['product_batch_id']);
                        $jumlah = (int) ($data['jumlah'] ?? 0);
                        $discount = $data['diskon_persen'] ?? 0;

                        try {
                            $delivery = app(ConsignmentDeliveryService::class)->deliver(
                                partnerId: $this->getOwnerRecord()->id,
                                productBatchId: (int) $data['product_batch_id'],
                                salesId: (int) $data['sales_id'],
                                quantity: $jumlah,
                                discountPercent: $discount,
                            );
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Stok Gagal Dikirim')
                                ->body($exception->getMessage())
                                ->send();
                            $action->halt();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Stok Titipan Terkirim!')
                            ->body("Berhasil mengirim {$jumlah} pcs {$batch->product->nama} dengan harga Rp ".number_format($delivery->harga_satuan, 0, ',', '.')." per pcs (Batch: {$batch->batch_code}).")
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
                    ->mountUsing(function (Model $record, ?Form $form, Tables\Actions\Action $action) {
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
                                'partner_id' => $this->getOwnerRecord()->id,
                                'product_batch_id' => $record->product_batch_id,
                                'sales_id' => $data['sales_id'],
                                'terjual' => 0,
                                'qty_layak' => 0,
                                'qty_rusak' => 0,
                                'diskon_persen' => $record->diskon_persen,
                                'harga_satuan' => $record->harga_satuan,
                                'omzet_terbentuk' => 0,
                                'status' => 'menunggu_konfirmasi',
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
