<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsignmentReturnResource\Pages;
use App\Models\ConsignmentReturn;
use App\Models\ConsignmentStock;
use App\Models\ProductDisposal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsignmentReturnResource extends Resource
{
    protected static ?string $model = ConsignmentReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Konfirmasi Penarikan';

    protected static ?string $pluralLabel = 'Retur Titipan';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'mitra';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['partner', 'productBatch.product', 'sales']);

        $user = Auth::user();

        if ($user?->role === 'mitra') {
            $query->where('partner_id', $user->partner_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Diajukan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('partner.nama_apotek')
                    ->label('Apotek Mitra')
                    ->searchable()
                    ->icon('heroicon-o-home-modern')
                    ->visible(fn () => Auth::user()?->role === 'admin'),

                Tables\Columns\TextColumn::make('productBatch.product.nama')
                    ->label('Produk Ditarik')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (ConsignmentReturn $record): string => $record->productBatch?->batch_code ?? '-'),

                Tables\Columns\TextColumn::make('sales.nama')
                    ->label('Sales')
                    ->icon('heroicon-o-identification')
                    ->default('-'),

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

                Tables\Columns\TextColumn::make('terjual')
                    ->label('Laku')
                    ->suffix(' pcs')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('qty_layak')
                    ->label('Layak')
                    ->suffix(' pcs')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('qty_rusak')
                    ->label('Rusak')
                    ->suffix(' pcs')
                    ->alignCenter()
                    ->badge()
                    ->color('danger'),
            ])
            ->filters([
                Tables\Filters\Filter::make('hari_ini')
                    ->label('Retur Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', Carbon::today())),

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
                                ->default(now()->format('m'))
                                ->native(false),

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
                                ->default(now()->year)
                                ->native(false),
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

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                        'selesai' => 'Selesai',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('isi_rincian')
                    ->label('Isi Rincian')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (ConsignmentReturn $record) =>
                        $record->status === 'menunggu_konfirmasi' && Auth::user()?->role === 'mitra'
                    )
                    ->modalHeading(fn (ConsignmentReturn $record) => "Konfirmasi Rincian: {$record->productBatch->product->nama}")
                    ->modalDescription(function (ConsignmentReturn $record) {
                        $stok = ConsignmentStock::where('partner_id', $record->partner_id)
                            ->where('product_batch_id', $record->product_batch_id)
                            ->first();
                        $jumlah = $stok?->stok_titipan ?? 0;
                        return "Total rincian di bawah wajib berjumlah tepat {$jumlah} pcs (sesuai stok titipan saat ini).";
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
                                    ->required()
                                    ->rule('min:0')
                                    ->default(0)
                                    ->helperText('Uang masuk.'),

                                Forms\Components\TextInput::make('qty_layak')
                                    ->label('Sisa Layak Jual')
                                    ->prefixIcon('heroicon-o-arrow-path')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->required()
                                    ->rule('min:0')
                                    ->default(0)
                                    ->helperText('Kembali ke rak toko.'),

                                Forms\Components\TextInput::make('qty_rusak')
                                    ->label('Barang Rusak')
                                    ->prefixIcon('heroicon-o-archive-box-x-mark')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->required()
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

                Tables\Actions\Action::make('koreksi')
                    ->label('Koreksi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->visible(fn (ConsignmentReturn $record) => $record->status === 'selesai' && Auth::user()?->role === 'mitra')
                    ->modalHeading(fn (ConsignmentReturn $record) => "Koreksi Rincian: {$record->productBatch->product->nama}")
                    ->modalDescription(function (ConsignmentReturn $record) {
                        $totalAwal = $record->terjual + $record->qty_layak + $record->qty_rusak;
                        return "Total rincian di bawah wajib tetap berjumlah {$totalAwal} pcs (sesuai tarikan awal).";
                    })
                    ->modalSubmitActionLabel('Simpan Koreksi')
                    ->fillForm(fn (ConsignmentReturn $record): array => [
                        'terjual'   => $record->terjual,
                        'qty_layak' => $record->qty_layak,
                        'qty_rusak' => $record->qty_rusak,
                    ])
                    ->form([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('terjual')
                                    ->label('Terjual (Laku)')
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->required()
                                    ->rule('min:0'),

                                Forms\Components\TextInput::make('qty_layak')
                                    ->label('Sisa Layak Jual')
                                    ->prefixIcon('heroicon-o-arrow-path')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->required()
                                    ->rule('min:0'),

                                Forms\Components\TextInput::make('qty_rusak')
                                    ->label('Barang Rusak')
                                    ->prefixIcon('heroicon-o-archive-box-x-mark')
                                    ->suffix('pcs')
                                    ->numeric()
                                    ->required()
                                    ->rule('min:0'),
                            ]),
                    ])
                    ->action(function (ConsignmentReturn $record, array $data, Tables\Actions\Action $action) {
                        $terjualBaru = (int) ($data['terjual'] ?? 0);
                        $layakBaru   = (int) ($data['qty_layak'] ?? 0);
                        $rusakBaru   = (int) ($data['qty_rusak'] ?? 0);
                        
                        $totalBaru = $terjualBaru + $layakBaru + $rusakBaru;
                        $totalAwal = $record->terjual + $record->qty_layak + $record->qty_rusak;

                        if ($totalBaru !== $totalAwal) {
                            Notification::make()
                                ->danger()
                                ->title('Koreksi Ditolak!')
                                ->body("Total rincian ({$totalBaru} pcs) harus sama persis dengan tarikan awal ({$totalAwal} pcs).")
                                ->send();
                            $action->halt(); 
                        }

                        DB::transaction(function () use ($record, $terjualBaru, $layakBaru, $rusakBaru) {
                            $selisihLayak = $layakBaru - $record->qty_layak;
                            if ($selisihLayak !== 0) {
                                $record->productBatch->increment('stok_toko', $selisihLayak);
                            }

                            if ($rusakBaru > 0) {
                                ProductDisposal::updateOrCreate(
                                    ['consignment_return_id' => $record->id],
                                    [
                                        'product_batch_id' => $record->product_batch_id,
                                        'jumlah'           => $rusakBaru,
                                        'alasan'           => 'Barang Rusak',
                                        'sumber'           => 'Apotek',
                                    ]
                                );
                            } else {
                                ProductDisposal::where('consignment_return_id', $record->id)->delete();
                            }

                            $omzet = $terjualBaru * ($record->productBatch->product->harga_jual ?? 0);
                            $record->update([
                                'terjual'         => $terjualBaru,
                                'qty_layak'       => $layakBaru,
                                'qty_rusak'       => $rusakBaru,
                                'omzet_terbentuk' => $omzet,
                            ]);
                        });

                        Notification::make()
                            ->success()
                            ->title('Koreksi Berhasil!')
                            ->body('Rincian penarikan telah disesuaikan dan stok telah dihitung ulang.')
                            ->icon('heroicon-o-check-circle')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageConsignmentReturns::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        $query = ConsignmentReturn::where('status', 'menunggu_konfirmasi');

        if ($user?->role === 'mitra') {
            $query->where('partner_id', $user->partner_id);
        }

        $count = $query->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
