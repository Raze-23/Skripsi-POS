<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductRequestResource\Pages;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductRequest;
use App\Models\Sales;
use App\Services\ConsignmentDeliveryService;
use Closure;
use DomainException;
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
use Illuminate\Support\HtmlString;

class ProductRequestResource extends Resource
{
    protected static ?string $model = ProductRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $pluralLabel = 'Daftar Request Produk';

    protected static ?string $modelLabel = 'Request Produk';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return Auth::user()?->role === 'admin' ? 'Daftar Request' : 'Request Produk';
    }

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'owner', 'mitra']);
    }

    public static function canCreate(): bool
    {
        return in_array(Auth::user()?->role, ['owner', 'mitra']);
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if ($user?->role === 'admin') {
            return false;
        }

        return in_array($user?->role, ['owner', 'mitra'])
            && $record->user_id === $user?->id
            && $record->status === ProductRequest::STATUS_PENDING;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if ($user?->role === 'admin') {
            return false;
        }

        return in_array($user?->role, ['owner', 'mitra'])
            && $record->user_id === $user?->id
            && $record->status === ProductRequest::STATUS_PENDING;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Request')
                    ->description('Pilih produk dan jumlah yang ingin diminta.')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produk')
                            ->prefixIcon('heroicon-o-cube')
                            ->placeholder('Pilih produk...')
                            ->options(Product::pluck('nama', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->disabled(fn (?Model $record) => $record !== null && $record->status !== ProductRequest::STATUS_PENDING)
                            ->validationMessages([
                                'required' => 'Produk wajib dipilih.',
                            ]),

                        Forms\Components\TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->suffix('pcs')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->disabled(fn (?Model $record) => $record !== null && $record->status !== ProductRequest::STATUS_PENDING)
                            ->helperText('Jumlah produk yang direquest.')
                            ->validationMessages([
                                'required' => 'Jumlah wajib diisi.',
                                'min' => 'Jumlah minimal 1 pcs.',
                            ]),

                        Forms\Components\Placeholder::make('diskon_admin')
                            ->label('Diskon Mitra')
                            ->content(fn (?ProductRequest $record): string => $record?->tipe_request === 'restok_apotek' && filled($record->harga_satuan)
                                ? number_format((float) $record->diskon_persen, 2, ',', '.').'%'
                                : ($record?->tipe_request === 'restok_apotek' ? 'Menunggu Admin' : '-')),

                        Forms\Components\Placeholder::make('harga_mitra')
                            ->label('Harga Jual Mitra')
                            ->content(fn (?ProductRequest $record): string => $record?->tipe_request === 'restok_apotek' && filled($record->harga_satuan)
                                ? 'Rp '.number_format($record->harga_satuan, 0, ',', '.').' / pcs'
                                : '-'),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => Auth::user()?->id),

                        Forms\Components\Hidden::make('tipe_request')
                            ->default(fn () => match (Auth::user()?->role) {
                                'owner' => 'produksi_owner',
                                'mitra' => 'restok_apotek',
                                default => 'produksi_owner',
                            }),

                        Forms\Components\Hidden::make('partner_id')
                            ->default(fn () => Auth::user()?->role === 'mitra' ? Auth::user()?->partner_id : null),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $query->with(['partner', 'user.partner', 'product']);

                if ($user?->role === 'owner') {
                    $query->where('user_id', $user->id);
                } elseif ($user?->role === 'mitra') {
                    $query->where('partner_id', $user->partner_id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->icon('heroicon-o-calendar')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('product.nama')
                    ->label('Produk')
                    ->icon('heroicon-o-cube')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->icon('heroicon-o-user')
                    ->placeholder('-')
                    ->toggleable()
                    ->visible(fn () => Auth::user()?->role === 'admin'),

                Tables\Columns\TextColumn::make('tipe_request')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'produksi_owner' => 'Request Produksi',
                        'restok_apotek' => 'Request Apotek',
                        default => $state,
                    })
                    ->icon(fn (string $state) => match ($state) {
                        'produksi_owner' => 'heroicon-o-cog-6-tooth',
                        'restok_apotek' => 'heroicon-o-building-storefront',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'produksi_owner' => 'info',
                        'restok_apotek' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->icon('heroicon-o-hashtag')
                    ->suffix(' pcs')
                    ->weight('semibold')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('asal_request')
                    ->label('Asal Request')
                    ->state(fn (ProductRequest $record): string => $record->requestSourceName())
                    ->icon('heroicon-o-building-storefront')
                    ->badge()
                    ->color(fn (ProductRequest $record): string => $record->tipe_request === 'restok_apotek' ? 'warning' : 'info')
                    ->toggleable()
                    ->visible(fn () => Auth::user()?->role === 'admin')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $sourceQuery) use ($search) {
                            $sourceQuery->whereHas('partner', fn (Builder $partnerQuery): Builder => $partnerQuery
                                ->where('nama_apotek', 'like', "%{$search}%"));

                            if (str_contains('owner / gudang', strtolower($search))) {
                                $sourceQuery->orWhere('tipe_request', 'produksi_owner');
                            }
                        });
                    }),

                Tables\Columns\TextColumn::make('diskon_persen')
                    ->label('Diskon Request')
                    ->state(fn (ProductRequest $record): ?float => $record->tipe_request === 'restok_apotek'
                        ? (float) $record->diskon_persen
                        : null)
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.').'%')
                    ->placeholder('-')
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray')
                    ->visible(fn () => Auth::user()?->role === 'admin'),

                Tables\Columns\TextColumn::make('harga_satuan')
                    ->label('Harga Mitra')
                    ->state(fn (ProductRequest $record): ?int => $record->tipe_request === 'restok_apotek'
                        ? $record->harga_satuan
                        : null)
                    ->money('IDR', locale: 'id')
                    ->placeholder(fn (ProductRequest $record): string => match (true) {
                        $record->tipe_request !== 'restok_apotek' => '-',
                        $record->status === ProductRequest::STATUS_DITOLAK => 'Request ditolak',
                        default => 'Menunggu Admin',
                    })
                    ->description(fn (ProductRequest $record): ?string => $record->tipe_request === 'restok_apotek' ? 'per pcs' : null)
                    ->weight('semibold')
                    ->visible(fn () => Auth::user()?->role === 'admin'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ProductRequest::statusOptions()[$state] ?? ucfirst($state))
                    ->icon(fn (string $state) => match ($state) {
                        ProductRequest::STATUS_PENDING => 'heroicon-o-clock',
                        ProductRequest::STATUS_DIPROSES => 'heroicon-o-arrow-path',
                        ProductRequest::STATUS_SELESAI => 'heroicon-o-check-circle',
                        ProductRequest::STATUS_DITOLAK => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state) => match ($state) {
                        ProductRequest::STATUS_PENDING => 'warning',
                        ProductRequest::STATUS_DIPROSES => 'info',
                        ProductRequest::STATUS_SELESAI => 'success',
                        ProductRequest::STATUS_DITOLAK => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->striped()
            ->filters([
                Tables\Filters\Filter::make('hari_ini')
                    ->label('Request Hari Ini')
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
                    ->options(ProductRequest::statusOptions())
                    ->native(false),

                Tables\Filters\SelectFilter::make('tipe_request')
                    ->label('Tipe')
                    ->options([
                        'produksi_owner' => 'Request Owner',
                        'restok_apotek' => 'Request Apotek',
                    ])
                    ->native(false)
                    ->visible(fn () => Auth::user()?->role === 'admin'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Koreksi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn (ProductRequest $record) => static::canEdit($record)),

                Tables\Actions\DeleteAction::make()
                    ->label('Batal')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (ProductRequest $record) => static::canDelete($record))
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalHeading('Batalkan Request Produk')
                    ->modalDescription(fn (ProductRequest $record) => "Apakah Anda yakin ingin membatalkan request untuk {$record->jumlah} pcs \"{$record->product?->nama}\"? Tindakan ini tidak dapat dibatalkan.")
                    ->modalSubmitActionLabel('Ya, Batalkan Request')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Request Dibatalkan')
                            ->body('Permintaan produk telah berhasil dibatalkan dan dihapus.')
                    ),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('proses')
                        ->label('Tandai Diproses')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalIcon('heroicon-o-arrow-path')
                        ->modalHeading('Tandai Sebagai Diproses')
                        ->modalDescription(fn (ProductRequest $record) => "Request {$record->jumlah} pcs \"{$record->product?->nama}\" akan disetujui untuk diproses. Keputusan diskon ditentukan saat produk dikirim.")
                        ->modalSubmitActionLabel('Ya, Tandai Diproses')
                        ->visible(fn (ProductRequest $record) => Auth::user()?->role === 'admin' && $record->status === ProductRequest::STATUS_PENDING)
                        ->action(function (ProductRequest $record) {
                            $record->update([
                                'status' => ProductRequest::STATUS_DIPROSES,
                            ]);

                            Notification::make()
                                ->title('Request Sedang Diproses')
                                ->body("{$record->jumlah} pcs \"{$record->product?->nama}\" telah disetujui dan menunggu proses berikutnya.")
                                ->icon('heroicon-o-arrow-path')
                                ->iconColor('info')
                                ->color('info')
                                ->duration(4500)
                                ->send();
                        }),

                    Tables\Actions\Action::make('tolak')
                        ->label('Tolak Request')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalIcon('heroicon-o-x-circle')
                        ->modalHeading('Tolak Request Produk')
                        ->modalDescription(fn (ProductRequest $record) => "Request {$record->jumlah} pcs \"{$record->product?->nama}\" dari {$record->requestSourceName()} akan ditolak dan tidak akan diproses lebih lanjut.")
                        ->modalSubmitActionLabel('Ya, Tolak Request')
                        ->visible(fn (ProductRequest $record) => Auth::user()?->role === 'admin' && $record->status === ProductRequest::STATUS_PENDING)
                        ->action(function (ProductRequest $record) {
                            $record->update([
                                'status' => ProductRequest::STATUS_DITOLAK,
                            ]);

                            Notification::make()
                                ->title('Request Ditolak')
                                ->body("{$record->jumlah} pcs \"{$record->product?->nama}\" dari {$record->requestSourceName()} telah ditolak.")
                                ->icon('heroicon-o-x-circle')
                                ->iconColor('danger')
                                ->danger()
                                ->duration(4500)
                                ->send();
                        }),

                    Tables\Actions\Action::make('kirim_produk')
                        ->label('Kirim Produk ke Mitra')
                        ->icon('heroicon-o-truck')
                        ->color('success')
                        ->modalIcon('heroicon-o-truck')
                        ->modalHeading(fn (ProductRequest $record) => "Kirim {$record->product?->nama} ke Mitra")
                        ->modalDescription('Pilih sales dan batch produk, lalu konfirmasi pengiriman.')
                        ->modalSubmitActionLabel('Konfirmasi & Kirim Produk')
                        ->modalCancelActionLabel('Batal')
                        ->modalWidth('2xl')
                        ->stickyModalHeader()
                        ->stickyModalFooter()
                        ->fillForm(fn (ProductRequest $record): array => [
                            'jumlah' => $record->jumlah,
                        ])
                        ->form([
                            Forms\Components\Section::make('Ringkasan Pengiriman')
                                ->icon('heroicon-o-clipboard-document-check')
                                ->schema([
                                    Forms\Components\Placeholder::make('ringkasan_pengiriman')
                                        ->hiddenLabel()
                                        ->content(function (ProductRequest $record): HtmlString {
                                            $hargaNormal = (int) $record->product?->harga_jual;
                                            $diskon = min(100, max(0, (float) $record->diskon_persen));
                                            $hargaFinal = ConsignmentDeliveryService::discountedPrice(
                                                $hargaNormal,
                                                $diskon,
                                            );
                                            $total = $hargaFinal * (int) $record->jumlah;

                                            return new HtmlString(sprintf(
                                                '<div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">'
                                                .'<div class="grid grid-cols-2 divide-y divide-gray-200 sm:grid-cols-4 sm:divide-x sm:divide-y-0 dark:divide-white/10">'
                                                .'<div class="p-3"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">Produk</p><p class="mt-1 font-semibold text-gray-950 dark:text-white">%s</p></div>'
                                                .'<div class="p-3"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tujuan</p><p class="mt-1 font-semibold text-gray-950 dark:text-white">%s</p></div>'
                                                .'<div class="p-3"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">Jumlah</p><p class="mt-1 font-semibold text-gray-950 dark:text-white">%s pcs</p></div>'
                                                .'<div class="p-3"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">Harga Normal</p><p class="mt-1 font-semibold text-gray-950 dark:text-white">Rp %s</p></div>'
                                                .'</div>'
                                                .'<div class="grid grid-cols-1 divide-y divide-gray-200 border-t border-gray-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0 dark:divide-white/10 dark:border-white/10">'
                                                .'<div class="p-3"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">Harga Final / pcs (diskon %s%%)</p><p class="mt-1 font-bold text-success-600 dark:text-success-400">Rp %s</p></div>'
                                                .'<div class="p-3"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Tagihan</p><p class="mt-1 font-bold text-gray-950 dark:text-white">Rp %s</p></div>'
                                                .'</div>'
                                                .'</div>',
                                                e($record->product?->nama ?? '-'),
                                                e($record->requestSourceName()),
                                                number_format((int) $record->jumlah, 0, ',', '.'),
                                                number_format($hargaNormal, 0, ',', '.'),
                                                number_format($diskon, 2, ',', '.'),
                                                number_format($hargaFinal, 0, ',', '.'),
                                                number_format($total, 0, ',', '.'),
                                            ));
                                        }),
                                ]),

                            Forms\Components\Section::make('Penugasan Pengiriman')
                                ->icon('heroicon-o-truck')
                                ->schema([
                                    Forms\Components\Select::make('sales_id')
                                        ->label('Sales Pengirim')
                                        ->prefixIcon('heroicon-o-identification')
                                        ->placeholder('Pilih sales pengirim')
                                        ->options(fn () => Sales::query()->where('is_active', true)->pluck('nama', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->required()
                                        ->validationMessages(['required' => 'Sales pengirim wajib dipilih.']),

                                    Forms\Components\Select::make('product_batch_id')
                                        ->label('Batch Produk')
                                        ->prefixIcon('heroicon-o-tag')
                                        ->placeholder('Pilih batch yang tersedia')
                                        ->helperText('Kedaluwarsa terdekat tampil di atas.')
                                        ->options(fn (ProductRequest $record) => ProductBatch::query()
                                            ->where('product_id', $record->product_id)
                                            ->where('stok_toko', '>', 0)
                                            ->whereDate('tanggal_kedaluwarsa', '>=', now())
                                            ->orderBy('tanggal_kedaluwarsa')
                                            ->get()
                                            ->mapWithKeys(fn (ProductBatch $batch) => [
                                                $batch->id => sprintf(
                                                    '%s | Stok: %s pcs | ED: %s',
                                                    $batch->batch_code,
                                                    number_format($batch->stok_toko, 0, ',', '.'),
                                                    $batch->tanggal_kedaluwarsa?->format('d/m/Y') ?? '-',
                                                ),
                                            ]))
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->native(false)
                                        ->required()
                                        ->validationMessages(['required' => 'Batch produk wajib dipilih.']),
                                ])
                                ->columns(2),

                            Forms\Components\Hidden::make('jumlah')
                                ->default(fn (ProductRequest $record) => $record->jumlah)
                                ->rule(function (Get $get, ProductRequest $record) {
                                    return function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                        if ((int) $value !== (int) $record->jumlah) {
                                            $fail("Jumlah harus sama dengan request, yaitu {$record->jumlah} pcs.");
                                        }

                                        $batch = ProductBatch::find($get('product_batch_id'));
                                        if ($batch && (int) $value > (int) $batch->stok_toko) {
                                            $fail("Stok batch tidak cukup. Tersedia {$batch->stok_toko} pcs.");
                                        }
                                    };
                                }),
                        ])
                        ->visible(fn (ProductRequest $record): bool => Auth::user()?->role === 'admin'
                            && $record->tipe_request === 'restok_apotek'
                            && $record->status === ProductRequest::STATUS_DIPROSES)
                        ->action(function (ProductRequest $record, array $data, Tables\Actions\Action $action) {
                            try {
                                $delivery = app(ConsignmentDeliveryService::class)->deliver(
                                    partnerId: (int) $record->partner_id,
                                    productBatchId: (int) $data['product_batch_id'],
                                    salesId: (int) $data['sales_id'],
                                    quantity: (int) $data['jumlah'],
                                    discountPercent: $record->diskon_persen,
                                    productRequestId: $record->id,
                                );
                            } catch (DomainException $exception) {
                                Notification::make()
                                    ->danger()
                                    ->title('Produk Gagal Dikirim')
                                    ->body($exception->getMessage())
                                    ->send();
                                $action->halt();

                                return;
                            }

                            Notification::make()
                                ->success()
                                ->title('Produk Berhasil Dikirim')
                                ->body("{$delivery->jumlah} pcs {$record->product?->nama} dikirim ke {$record->requestSourceName()} dengan harga Rp ".number_format($delivery->harga_satuan, 0, ',', '.').' per pcs.')
                                ->icon('heroicon-o-check-badge')
                                ->send();
                        }),

                    Tables\Actions\Action::make('selesai')
                        ->label('Tandai Selesai')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalIcon('heroicon-o-check-circle')
                        ->modalHeading('Tandai Sebagai Selesai')
                        ->modalDescription(fn (ProductRequest $record) => "Request {$record->jumlah} pcs \"{$record->product?->nama}\" akan ditandai sebagai Selesai.")
                        ->modalSubmitActionLabel('Ya, Tandai Selesai')
                        ->visible(fn (ProductRequest $record) => Auth::user()?->role === 'admin'
                            && $record->tipe_request === 'produksi_owner'
                            && $record->status === ProductRequest::STATUS_DIPROSES)
                        ->action(function (ProductRequest $record) {
                            $record->update(['status' => ProductRequest::STATUS_SELESAI]);

                            Notification::make()
                                ->title('Request Selesai Diproses')
                                ->body("{$record->jumlah} pcs \"{$record->product?->nama}\" telah selesai dan siap diambil/dikirim.")
                                ->icon('heroicon-o-check-circle')
                                ->iconColor('success')
                                ->success()
                                ->duration(4500)
                                ->send();
                        }),
                ])
                    ->label('Tindakan Admin')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->color('gray')
                    ->visible(fn () => Auth::user()?->role === 'admin'),
            ])
            ->emptyStateHeading('Belum Ada Request Produk')
            ->emptyStateDescription('Request produk yang diajukan akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductRequests::route('/'),
            'create' => Pages\CreateProductRequest::route('/create'),
            'edit' => Pages\EditProductRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (Auth::user()?->role !== 'admin') {
            return null;
        }

        $count = ProductRequest::where('status', ProductRequest::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
