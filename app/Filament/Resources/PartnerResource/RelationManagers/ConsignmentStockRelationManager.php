<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use App\Models\ConsignmentReturn;
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
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('product_batch_id')
                                ->label('Pilih Batch Produk')
                                ->options(fn () => ProductBatch::where('stok_toko', '>', 0)
                                    ->with('product')
                                    ->get()
                                    ->mapWithKeys(fn ($b) => [$b->id => $b->product->nama . ' — ' . $b->batch_code . ' (Stok: ' . $b->stok_toko . ')'])
                                )
                                ->searchable()
                                ->required(),
                            Forms\Components\TextInput::make('jumlah')
                                ->label('Jumlah Dikirim')
                                ->numeric()
                                ->minValue(1)
                                ->required(),
                        ]),
                    ])
                    ->action(function (array $data, Tables\Actions\Action $action) {
                        $batch = ProductBatch::with('product')->find($data['product_batch_id']);
                        if ($batch->stok_toko < $data['jumlah']) {
                            Notification::make()
                                ->danger()
                                ->title('Stok Utama Tidak Cukup')
                                ->body("Sisa {$batch->product->nama} (Batch: {$batch->batch_code}) di toko hanya {$batch->stok_toko} pcs.")
                                ->send();
                            $action->halt();
                        }
                        DB::transaction(function () use ($batch, $data) {
                            $batch->decrement('stok_toko', $data['jumlah']);
                            $consignment = $this->getOwnerRecord()->consignmentStocks()
                                ->firstOrCreate(
                                    ['product_batch_id' => $batch->id],
                                    ['stok_titipan' => 0]
                                );
                            $consignment->increment('stok_titipan', $data['jumlah']);
                        });
                        Notification::make()
                            ->success()
                            ->title('Berhasil Mengirim Stok!')
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('tarik_barang')
                    ->label('Tarik Barang')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('danger')
                    ->modalHeading(fn (Model $record) => "Penarikan: {$record->productBatch->product->nama} ({$record->productBatch->batch_code})")
                    ->modalDescription(fn (Model $record) => "Total stok ditarik: {$record->stok_titipan} pcs. Input wajib sesuai dengan total stok.")
                    ->modalSubmitActionLabel('Tarik')
                    ->modalCancelActionLabel('Tutup')
                    ->form([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('terjual')
                                    ->label('Terjual')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),
                                Forms\Components\TextInput::make('qty_layak')
                                    ->label('Sisa Layak Jual')
                                    ->helperText('Kembali stok toko')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),
                                Forms\Components\TextInput::make('qty_rusak')
                                    ->label('Barang Rusak')
                                    ->helperText('Dibuang')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),
                            ]),
                    ])
                    ->action(function (Model $record, array $data, Tables\Actions\Action $action) {
                        $totalInput = $data['terjual'] + $data['qty_layak'] + $data['qty_rusak'];
                        $stokMitraSaatIni = $record->stok_titipan;
                        if ($totalInput !== $stokMitraSaatIni) {
                            Notification::make()
                                ->danger()
                                ->title('Kalkulasi Tidak Valid!')
                                ->body("Total input ({$totalInput} pcs) tidak sama dengan sisa stok di apotek ({$stokMitraSaatIni} pcs).")
                                ->send();
                            $action->halt();
                        }
                        DB::transaction(function () use ($record, $data) {
                            if ($data['qty_layak'] > 0) {
                                $record->productBatch->increment('stok_toko', $data['qty_layak']);
                            }
                            ConsignmentReturn::create([
                                'partner_id' => $this->getOwnerRecord()->id,
                                'product_batch_id' => $record->product_batch_id,
                                'terjual'    => $data['terjual'],
                                'qty_layak'  => $data['qty_layak'],
                                'qty_rusak'  => $data['qty_rusak'],
                            ]);
                            $record->delete();
                        });
                        Notification::make()
                            ->success()
                            ->title('Barang Berhasil Ditarik!')
                            ->body('Barang tekah dihapus dari daftar dan riwayat berhasil disimpan.')
                            ->send();
                    }),
            ]);
    }
}
