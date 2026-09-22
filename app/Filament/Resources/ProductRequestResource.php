<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductRequestResource\Pages;
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

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Daftar Request';

    protected static ?string $pluralModelLabel = 'Daftar Request Produk';

    protected static ?string $modelLabel = 'Request Produk';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return false; // Admin tidak buat request
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Admin tidak edit request
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['partner', 'user.partner', 'product']);
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
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tipe_request')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'produksi_owner' => 'Usulan Produksi',
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
                    ->label('Asal')
                    ->state(fn (ProductRequest $record): string => $record->requestSourceName())
                    ->icon('heroicon-o-building-storefront')
                    ->badge()
                    ->color(fn (ProductRequest $record): string => $record->tipe_request === 'restok_apotek' ? 'warning' : 'info')
                    ->toggleable()
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
                    ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('harga_satuan')
                    ->label('Harga Mitra')
                    ->state(fn (ProductRequest $record): ?int => $record->tipe_request === 'restok_apotek'
                        ? $record->harga_satuan
                        : null)
                    ->money('IDR', locale: 'id')
                    ->placeholder(fn (ProductRequest $record): string => match (true) {
                        $record->tipe_request !== 'restok_apotek' => '-',
                        $record->status === ProductRequest::STATUS_DITOLAK => 'Ditolak',
                        default => 'Menunggu Admin',
                    })
                    ->description(fn (ProductRequest $record): ?string => $record->tipe_request === 'restok_apotek' ? 'per pcs' : null)
                    ->weight('semibold'),

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
                    ->label('Hari Ini')
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
                        'produksi_owner' => 'Usulan Owner',
                        'restok_apotek' => 'Request Apotek',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('proses')
                        ->label('Tandai Diproses')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn (ProductRequest $record) => $record->status === ProductRequest::STATUS_PENDING)
                        ->action(function (ProductRequest $record) {
                            $record->update(['status' => ProductRequest::STATUS_DIPROSES]);
                            Notification::make()->title('Sedang Diproses')->success()->send();
                        }),

                    Tables\Actions\Action::make('tolak')
                        ->label('Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (ProductRequest $record) => $record->status === ProductRequest::STATUS_PENDING)
                        ->action(function (ProductRequest $record) {
                            $record->update(['status' => ProductRequest::STATUS_DITOLAK]);
                            Notification::make()->title('Ditolak')->danger()->send();
                        }),

                    Tables\Actions\Action::make('kirim_produk')
                        ->label('Kirim Produk ke Mitra')
                        ->icon('heroicon-o-truck')
                        ->color('success')
                        ->modalIcon('heroicon-o-truck')
                        ->modalHeading(fn (ProductRequest $record): string => "Kirim {$record->product?->nama} ke Mitra")
                        ->modalDescription('Pilih sales dan batch produk, lalu konfirmasi pengiriman.')
                        ->modalSubmitActionLabel('Konfirmasi & Kirim Produk')
                        ->modalWidth('2xl')
                        ->fillForm(fn (ProductRequest $record): array => [
                            'jumlah' => $record->jumlah,
                        ])
                        ->form([
                            Forms\Components\Placeholder::make('ringkasan_pengiriman')
                                ->label('Ringkasan')
                                ->content(function (ProductRequest $record): HtmlString {
                                    $normalPrice = (int) $record->product?->harga_jual;
                                    $discount = min(100, max(0, (float) $record->diskon_persen));
                                    $unitPrice = ConsignmentDeliveryService::discountedPrice($normalPrice, $discount);

                                    return new HtmlString(sprintf(
                                        '<strong>%s pcs %s</strong><br><span>%s · Diskon %s%% · Rp %s/pcs</span>',
                                        number_format((int) $record->jumlah, 0, ',', '.'),
                                        e($record->product?->nama ?? '-'),
                                        e($record->requestSourceName()),
                                        number_format($discount, 2, ',', '.'),
                                        number_format($unitPrice, 0, ',', '.'),
                                    ));
                                }),

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
                                    ->mapWithKeys(fn (ProductBatch $batch): array => [
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

                            Forms\Components\Hidden::make('jumlah')
                                ->default(fn (ProductRequest $record): int => (int) $record->jumlah)
                                ->rule(function (Get $get, ProductRequest $record): Closure {
                                    return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
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
                        ->visible(fn (ProductRequest $record): bool => $record->tipe_request === 'restok_apotek'
                            && $record->status === ProductRequest::STATUS_DIPROSES)
                        ->action(function (ProductRequest $record, array $data, Tables\Actions\Action $action): void {
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
                                ->body("{$delivery->jumlah} pcs {$record->product?->nama} dikirim ke {$record->requestSourceName()}.")
                                ->send();
                        }),

                    Tables\Actions\Action::make('selesai')
                        ->label('Tandai Selesai')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (ProductRequest $record) => $record->tipe_request === 'produksi_owner' && $record->status === ProductRequest::STATUS_DIPROSES)
                        ->action(function (ProductRequest $record) {
                            $record->update(['status' => ProductRequest::STATUS_SELESAI]);
                            Notification::make()->title('Selesai Diproses')->success()->send();
                        }),
                ])
                    ->label('Tindakan')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum Ada Request Produk')
            ->emptyStateDescription('Data request yang diajukan oleh owner dan mitra akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductRequests::route('/'),
        ];
    }
}
