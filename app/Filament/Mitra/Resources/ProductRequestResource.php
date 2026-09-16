<?php

namespace App\Filament\Mitra\Resources;

use App\Filament\Mitra\Resources\ProductRequestResource\Pages;
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

    protected static ?string $navigationLabel = 'Request Produk';

    protected static ?string $pluralLabel = 'Daftar Request Produk';

    protected static ?string $modelLabel = 'Request Produk';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'mitra';
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'mitra';
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return $user?->role === 'mitra'
            && $record->user_id === $user?->id
            && $record->status === ProductRequest::STATUS_PENDING;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return $user?->role === 'mitra'
            && $record->user_id === $user?->id
            && $record->status === ProductRequest::STATUS_PENDING;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Request')
                    ->description('Pilih produk, jumlah, dan diskon yang ingin diajukan kepada Admin.')
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

                        Forms\Components\TextInput::make('diskon_persen')
                            ->label('Permintaan Diskon')
                            ->prefixIcon('heroicon-o-receipt-percent')
                            ->suffix('%')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->required()
                            ->disabled(fn (?Model $record) => $record !== null && $record->status !== ProductRequest::STATUS_PENDING)
                            ->helperText('Masukkan 0 jika tidak mengajukan diskon.')
                            ->validationMessages([
                                'required' => 'Permintaan diskon wajib diisi. Gunakan 0 jika tanpa diskon.',
                                'min' => 'Diskon tidak boleh kurang dari 0%.',
                                'max' => 'Diskon tidak boleh lebih dari 100%.',
                            ]),

                        Forms\Components\Placeholder::make('harga_mitra')
                            ->label('Harga Jual Mitra')
                            ->content(fn (?ProductRequest $record): string => filled($record?->harga_satuan)
                                ? 'Rp '.number_format($record->harga_satuan, 0, ',', '.').' / pcs'
                                : ($record?->status === ProductRequest::STATUS_DITOLAK ? 'Request ditolak' : 'Akan ditentukan Admin')),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => Auth::user()?->id),

                        Forms\Components\Hidden::make('tipe_request')
                            ->default('restok_apotek'),

                        Forms\Components\Hidden::make('partner_id')
                            ->default(fn () => Auth::user()?->partner_id),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $query
                    ->with(['partner', 'user.partner', 'product'])
                    ->where('partner_id', $user?->partner_id);
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

                Tables\Columns\TextColumn::make('asal_request')
                    ->label('Asal Request')
                    ->state(fn (ProductRequest $record): string => $record->requestSourceName())
                    ->icon('heroicon-o-building-storefront')
                    ->badge()
                    ->color('warning')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'partner',
                        fn (Builder $partnerQuery): Builder => $partnerQuery->where('nama_apotek', 'like', "%{$search}%")
                    )),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->icon('heroicon-o-hashtag')
                    ->suffix(' pcs')
                    ->weight('semibold')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('diskon_persen')
                    ->label('Diskon Diajukan')
                    ->state(fn (ProductRequest $record): float => (float) $record->diskon_persen)
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.').'%')
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('harga_satuan')
                    ->label('Harga Mitra')
                    ->money('IDR', locale: 'id')
                    ->placeholder(fn (ProductRequest $record): string => $record->status === ProductRequest::STATUS_DITOLAK
                        ? 'Request ditolak'
                        : 'Menunggu Admin')
                    ->description('per pcs')
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
                    ->label('Request Hari Ini')
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
                    ->options(ProductRequest::statusOptions())
                    ->native(false),
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
            ])
            ->emptyStateHeading('Belum Ada Request Produk')
            ->emptyStateDescription('Klik "Buat Request Produk" untuk mengajukan permintaan produk ke admin.')
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
        $user = Auth::user();
        $count = ProductRequest::where('partner_id', $user?->partner_id)
            ->where('status', ProductRequest::STATUS_PENDING)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
