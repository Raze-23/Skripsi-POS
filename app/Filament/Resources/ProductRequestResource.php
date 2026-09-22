<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductRequestResource\Pages;
use App\Models\Product;
use App\Models\ProductRequest;
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

class ProductRequestResource extends Resource
{
    protected static ?string $model = ProductRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return match (Auth::user()?->role) {
            'admin' => 'Daftar Request',
            'owner' => 'Usulan Produksi',
            default => 'Request Produk',
        };
    }

    public static function getPluralModelLabel(): string
    {
        return Auth::user()?->role === 'owner' ? 'Daftar Usulan Produksi' : 'Daftar Request Produk';
    }

    public static function getModelLabel(): string
    {
        return Auth::user()?->role === 'owner' ? 'Usulan Produksi' : 'Request Produk';
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
        $isOwner = Auth::user()?->role === 'owner';

        return $form
            ->schema([
                Forms\Components\Section::make($isOwner ? 'Detail Usulan Produksi' : 'Detail Request')
                    ->description($isOwner ? 'Pilih produk dan jumlah yang diusulkan untuk diproduksi.' : 'Pilih produk dan jumlah yang ingin diminta.')
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
                            ->helperText($isOwner ? 'Jumlah target yang ingin diproduksi.' : 'Jumlah produk yang direquest.')
                            ->validationMessages([
                                'required' => 'Jumlah wajib diisi.',
                                'min' => 'Jumlah minimal 1 pcs.',
                            ]),

                        Forms\Components\Placeholder::make('diskon_admin')
                            ->label('Diskon Mitra')
                            ->content(fn (?ProductRequest $record): string => $record?->tipe_request === 'restok_apotek' && filled($record->harga_satuan)
                                ? number_format((float) $record->diskon_persen, 2, ',', '.').'%'
                                : ($record?->tipe_request === 'restok_apotek' ? 'Menunggu Admin' : '-'))
                            ->hidden(fn () => $isOwner), // Sembunyikan untuk owner agar minimalis

                        Forms\Components\Placeholder::make('harga_mitra')
                            ->label('Harga Jual Mitra')
                            ->content(fn (?ProductRequest $record): string => $record?->tipe_request === 'restok_apotek' && filled($record->harga_satuan)
                                ? 'Rp '.number_format($record->harga_satuan, 0, ',', '.').' / pcs'
                                : '-')
                            ->hidden(fn () => $isOwner), // Sembunyikan untuk owner agar minimalis

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
                    ->columns($isOwner ? 1 : 2), // Jadi 1 kolom penuh untuk owner agar lebih rapi
            ]);
    }

    public static function table(Table $table): Table
    {
        $isOwner = Auth::user()?->role === 'owner';

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
                    })
                    ->hidden(fn () => $isOwner), // Sembunyikan tipe jika yang login adalah owner (karena pasti usulan produksi semua)

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
                        $record->status === ProductRequest::STATUS_DITOLAK => 'Ditolak',
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
                    ->modalHeading(fn () => Auth::user()?->role === 'owner' ? 'Batalkan Usulan Produksi' : 'Batalkan Request Produk')
                    ->modalDescription(fn (ProductRequest $record) => "Apakah Anda yakin ingin membatalkan pengajuan {$record->jumlah} pcs \"{$record->product?->nama}\"? Tindakan ini tidak dapat dibatalkan.")
                    ->modalSubmitActionLabel('Ya, Batalkan')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Dibatalkan')
                            ->body('Data berhasil dibatalkan dan dihapus dari sistem.')
                    ),

                // ... (Aksi khusus Admin (Proses, Tolak, Kirim, Selesai) dibiarkan utuh karena Owner tidak melihatnya)
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('proses')
                        ->label('Tandai Diproses')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn (ProductRequest $record) => Auth::user()?->role === 'admin' && $record->status === ProductRequest::STATUS_PENDING)
                        ->action(function (ProductRequest $record) {
                            $record->update(['status' => ProductRequest::STATUS_DIPROSES]);
                            Notification::make()->title('Sedang Diproses')->success()->send();
                        }),

                    Tables\Actions\Action::make('tolak')
                        ->label('Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (ProductRequest $record) => Auth::user()?->role === 'admin' && $record->status === ProductRequest::STATUS_PENDING)
                        ->action(function (ProductRequest $record) {
                            $record->update(['status' => ProductRequest::STATUS_DITOLAK]);
                            Notification::make()->title('Ditolak')->danger()->send();
                        }),

                    Tables\Actions\Action::make('selesai')
                        ->label('Tandai Selesai')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (ProductRequest $record) => Auth::user()?->role === 'admin' && $record->tipe_request === 'produksi_owner' && $record->status === ProductRequest::STATUS_DIPROSES)
                        ->action(function (ProductRequest $record) {
                            $record->update(['status' => ProductRequest::STATUS_SELESAI]);
                            Notification::make()->title('Selesai Diproses')->success()->send();
                        }),
                ])
                    ->label('Tindakan Admin')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->color('gray')
                    ->visible(fn () => Auth::user()?->role === 'admin'),
            ])
            ->emptyStateHeading(fn () => Auth::user()?->role === 'owner' ? 'Belum Ada Usulan Produksi' : 'Belum Ada Request Produk')
            ->emptyStateDescription('Data yang diajukan akan muncul di sini.')
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
}
