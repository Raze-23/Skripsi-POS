<?php

namespace App\Filament\Mitra\Resources;

use App\Filament\Mitra\Resources\ConsignmentReturnResource\Pages;
use App\Models\ConsignmentReturn;
use App\Models\ConsignmentStock;
use App\Models\ProductDisposal;
use App\Models\PaymentAccount;
use App\Services\ConsignmentReturnPaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ConsignmentReturnResource extends Resource
{
    protected static ?string $model = ConsignmentReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Konfirmasi Penarikan';

    protected static ?string $pluralLabel = 'Retur Titipan';

    protected static ?int $navigationSort = 1;

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
                        'menunggu_validasi' => 'info',
                        'selesai' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                        'menunggu_validasi' => 'Menunggu Validasi Pembayaran',
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

                Tables\Columns\TextColumn::make('diskon_persen')
                    ->label('Diskon')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.').'%')
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('harga_satuan')
                    ->label('Harga Mitra')
                    ->money('IDR', locale: 'id')
                    ->description('per pcs'),

                Tables\Columns\TextColumn::make('omzet_terbentuk')
                    ->label('Tagihan')
                    ->money('IDR', locale: 'id')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Pembayaran')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'transfer_bank' => 'Transfer Bank', 'tunai_sales' => 'Tunai ke Sales', default => '-',
                    }),
                Tables\Columns\TextColumn::make('catatan_penolakan')
                    ->label('Catatan Admin')->wrap()->placeholder('-'),
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
                                    '04' => 'April',   '05' => 'Mei',      '06' => 'Juni',
                                    '07' => 'Juli',    '08' => 'Agustus',  '09' => 'September',
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
                        'menunggu_validasi' => 'Menunggu Validasi Pembayaran',
                        'selesai' => 'Selesai',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat_pembayaran')
                    ->label('Lihat Pembayaran')->icon('heroicon-o-eye')->color('info')
                    ->visible(fn (ConsignmentReturn $record) => $record->status !== 'menunggu_konfirmasi' && $record->terjual > 0)
                    ->modalHeading('Rincian Pembayaran')
                    ->modalWidth('4xl')
                    ->modalContent(fn (ConsignmentReturn $record) => view('filament.consignment-payment-review', ['record' => $record]))
                    ->modalCancelActionLabel('Tutup')
                    ->modalSubmitAction(false),
                Tables\Actions\Action::make('isi_rincian')
                    ->label('Isi Rincian')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (ConsignmentReturn $record) => $record->status === 'menunggu_konfirmasi' && Auth::user()?->role === 'mitra'
                    )
                    ->modalHeading(fn (ConsignmentReturn $record) => "Konfirmasi Rincian: {$record->productBatch->product->nama}")
                    ->modalWidth('5xl')
                    ->modalDescription(function (ConsignmentReturn $record) {
                        $stok = ConsignmentStock::where('partner_id', $record->partner_id)
                            ->where('product_batch_id', $record->product_batch_id)
                            ->first();
                        $jumlah = $stok?->stok_titipan ?? 0;

                        return "Batch {$record->productBatch->batch_code}. Stok titipan {$jumlah} pcs. Jumlah rincian harus sesuai dengan stok titipan.";
                    })
                    ->modalSubmitActionLabel('Kirim Rincian & Pembayaran')
                    ->modalCancelActionLabel('Kembali')
                    ->form(static::returnDetailsForm())
                    ->action(function (ConsignmentReturn $record, array $data) {
                        app(ConsignmentReturnPaymentService::class)->submit($record, Auth::user(), $data);
                        Notification::make()->success()->title('Rincian Terkirim')
                            ->body((int) $data['terjual'] > 0 ? 'Pembayaran menunggu validasi Admin.' : 'Retur tanpa penjualan telah selesai.')
                            ->send();
                    }),
                Tables\Actions\Action::make('koreksi')
                    ->label('Koreksi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->visible(fn (ConsignmentReturn $record) => false)
                    ->modalHeading(fn (ConsignmentReturn $record) => "Koreksi Rincian: {$record->productBatch->product->nama}")
                    ->modalDescription(function (ConsignmentReturn $record) {
                        $totalAwal = $record->terjual + $record->qty_layak + $record->qty_rusak;

                        return "Total rincian di bawah wajib tetap berjumlah {$totalAwal} pcs (sesuai tarikan awal).";
                    })
                    ->modalSubmitActionLabel('Simpan Koreksi')
                    ->fillForm(fn (ConsignmentReturn $record): array => [
                        'terjual' => $record->terjual,
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
                        $layakBaru = (int) ($data['qty_layak'] ?? 0);
                        $rusakBaru = (int) ($data['qty_rusak'] ?? 0);

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
                                        'jumlah' => $rusakBaru,
                                        'alasan' => 'Barang Rusak',
                                        'sumber' => 'Apotek',
                                    ]
                                );
                            } else {
                                ProductDisposal::where('consignment_return_id', $record->id)->delete();
                            }

                            $omzet = $record->calculateRevenue($terjualBaru);
                            $record->update([
                                'terjual' => $terjualBaru,
                                'qty_layak' => $layakBaru,
                                'qty_rusak' => $rusakBaru,
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

    private static function returnDetailsForm(): array
    {
        $isTransfer = fn (Get $get): bool => $get('metode_pembayaran') === 'transfer_bank';

        return [
            Forms\Components\Section::make('Rincian Barang')
                ->description('Pisahkan seluruh stok titipan menjadi terjual, layak jual, dan rusak.')
                ->icon('heroicon-o-cube')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'md' => 3])->schema([
                        Forms\Components\TextInput::make('terjual')
                            ->label('Terjual')
                            ->suffix('pcs')
                            ->numeric()->required()->minValue(0)->default(0)
                            ->live(onBlur: true)
                            ->helperText('Jumlah yang harus dibayar.'),
                        Forms\Components\TextInput::make('qty_layak')
                            ->label('Sisa Layak Jual')
                            ->suffix('pcs')
                            ->numeric()->required()->minValue(0)->default(0)
                            ->live(onBlur: true)
                            ->helperText('Dikembalikan ke gudang.'),
                        Forms\Components\TextInput::make('qty_rusak')
                            ->label('Barang Rusak')
                            ->suffix('pcs')
                            ->numeric()->required()->minValue(0)->default(0)
                            ->live(onBlur: true)
                            ->helperText('Dicatat sebagai barang rusak.'),
                    ]),
                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                        Forms\Components\Placeholder::make('jumlah_dialokasikan')
                            ->label('Total Rincian')
                            ->content(function (Get $get, ConsignmentReturn $record): HtmlString {
                                $allocated = (int) $get('terjual') + (int) $get('qty_layak') + (int) $get('qty_rusak');
                                $stock = (int) ConsignmentStock::where('partner_id', $record->partner_id)
                                    ->where('product_batch_id', $record->product_batch_id)->value('stok_titipan');
                                $color = $allocated === $stock ? '#047857' : '#b45309';
                                return new HtmlString('<div style="padding:14px 16px;border:1px solid #dce5e2;border-radius:12px;background:#f8faf9">'
                                    .'<strong style="font-size:20px;color:'.$color.'">'.$allocated.' / '.$stock.' pcs</strong>'
                                    .'<div style="color:#64748b;margin-top:3px;font-size:12px">Total harus sama dengan stok titipan.</div></div>');
                            }),
                        Forms\Components\Placeholder::make('total_tagihan')
                            ->label('Total Tagihan')
                            ->content(function (Get $get, ConsignmentReturn $record): HtmlString {
                                $sold = (int) $get('terjual');
                                $price = $record->resolvedUnitPrice();
                                $amount = $record->calculateRevenue($sold);
                                return new HtmlString('<div style="padding:14px 16px;border:1px solid #bbddd0;border-radius:12px;background:#ecfdf5">'
                                    .'<strong style="font-size:20px;color:#065f46">Rp '.number_format($amount, 0, ',', '.').'</strong>'
                                    .'<div style="color:#475569;margin-top:3px;font-size:12px">'.$sold.' pcs × Rp '.number_format($price, 0, ',', '.').' per pcs</div></div>');
                            }),
                    ]),
                ]),

            Forms\Components\Section::make('Pembayaran')
                ->description('Pilih cara pembayaran untuk barang yang terjual.')
                ->icon('heroicon-o-banknotes')
                ->visible(fn (Get $get): bool => (int) $get('terjual') > 0)
                ->schema([
                    Forms\Components\Select::make('metode_pembayaran')
                        ->label('Metode Pembayaran')
                        ->options(fn () => PaymentAccount::where('is_active', true)->exists()
                            ? ['tunai_sales' => 'Titip Tunai ke Sales', 'transfer_bank' => 'Transfer Bank']
                            : ['tunai_sales' => 'Titip Tunai ke Sales'])
                        ->required(fn (Get $get): bool => (int) $get('terjual') > 0)
                        ->live()->native(false),
                    Forms\Components\Placeholder::make('info_tunai')
                        ->label('Penyerahan Tunai')
                        ->content(fn (Get $get, ConsignmentReturn $record): string => 'Titip Rp '.number_format($record->calculateRevenue((int) $get('terjual')), 0, ',', '.')
                            .' kepada Sales '.($record->sales?->nama ?? 'penarik').'. Admin akan memvalidasi setoran sebelum retur selesai.')
                        ->visible(fn (Get $get): bool => $get('metode_pembayaran') === 'tunai_sales'),
                ]),

            Forms\Components\Section::make('Detail Transfer')
                ->description('Pilih rekening tujuan dan unggah bukti pembayaran.')
                ->icon('heroicon-o-arrow-up-tray')
                ->visible($isTransfer)
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Forms\Components\Select::make('payment_account_id')
                        ->label('Rekening Tujuan')
                        ->options(fn () => PaymentAccount::where('is_active', true)->get()
                            ->mapWithKeys(fn (PaymentAccount $account) => [
                                $account->id => $account->bank.' ('.$account->account_name.')',
                            ]))
                        ->default(fn (): ?int => PaymentAccount::where('is_active', true)->count() === 1
                            ? PaymentAccount::where('is_active', true)->value('id')
                            : null)
                        ->required($isTransfer)
                        ->live()
                        ->native(false)
                        ->hidden(fn (): bool => PaymentAccount::where('is_active', true)->count() === 1)
                        ->dehydratedWhenHidden()
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make('detail_rekening_tujuan')
                        ->label('Nomor Rekening Tujuan')
                        ->visible(fn (Get $get): bool => filled($get('payment_account_id')))
                        ->content(function (Get $get): HtmlString {
                            $account = PaymentAccount::where('is_active', true)->find($get('payment_account_id'));
                            if (! $account) {
                                return new HtmlString('Pilih rekening tujuan yang aktif.');
                            }

                            return new HtmlString('<div style="padding:14px 16px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc">'
                                .'<div style="color:#475569;font-size:13px;font-weight:600">'.e($account->bank).'</div>'
                                .'<div style="margin-top:4px;color:#172c27;font-size:18px;font-weight:700;overflow-wrap:anywhere;user-select:text">'.e($account->account_number).'</div>'
                                .'<div style="margin-top:4px;color:#475569;font-size:13px">Atas nama '.e($account->account_name).'</div>'
                                .'</div>');
                        })
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('nama_pengirim')
                        ->label('Nama Pemilik Rekening Pengirim')
                        ->required($isTransfer)->maxLength(255),
                    Forms\Components\TextInput::make('bank_pengirim')
                        ->label('Bank Pengirim')
                        ->required($isTransfer)->maxLength(255),
                    Forms\Components\DateTimePicker::make('dibayar_pada')
                        ->label('Waktu Transfer')
                        ->required($isTransfer)->maxDate(now()),
                    Forms\Components\TextInput::make('referensi_transfer')
                        ->label('Nomor Referensi (opsional)')
                        ->maxLength(255),
                    Forms\Components\FileUpload::make('bukti_pembayaran')
                        ->label('Foto Bukti Transfer')
                        ->disk('local')
                        ->directory(fn (ConsignmentReturn $record): string => 'bukti-pembayaran-retur/'.$record->id)
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->required($isTransfer)
                        ->helperText('JPG, PNG, atau WebP. Maksimal 5 MB. Bukti hanya dapat dilihat oleh Anda dan Admin.')
                        ->columnSpanFull(),
                ]),
        ];
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
        $count = ConsignmentReturn::where('status', 'menunggu_konfirmasi')
            ->where('partner_id', $user?->partner_id)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
