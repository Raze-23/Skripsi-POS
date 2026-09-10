<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductRequestResource\Pages;
use App\Models\Product;
use App\Models\ProductRequest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
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
            && $record->status === 'pending';
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();
        
        if ($user?->role === 'admin') {
            return false;
        }

        return in_array($user?->role, ['owner', 'mitra']) 
            && $record->user_id === $user?->id 
            && $record->status === 'pending';
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
                            ->disabled(fn (?Model $record) => $record !== null && $record->status !== 'pending')
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
                            ->disabled(fn (?Model $record) => $record !== null && $record->status !== 'pending')
                            ->helperText('Jumlah produk yang direquest.')
                            ->validationMessages([
                                'required' => 'Jumlah wajib diisi.',
                                'min' => 'Jumlah minimal 1 pcs.',
                            ]),

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

                Tables\Columns\TextColumn::make('partner.nama_apotek')
                    ->label('Apotek')
                    ->icon('heroicon-o-building-storefront')
                    ->placeholder('-')
                    ->toggleable()
                    ->visible(fn () => Auth::user()?->role === 'admin'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (string $state) => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'diproses' => 'heroicon-o-arrow-path',
                        'selesai' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'diproses' => 'info',
                        'selesai' => 'success',
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
                    ->options([
                        'pending' => 'Pending',
                        'diproses' => 'Diproses',
                        'selesai' => 'Selesai',
                    ])
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
                        ->modalDescription(fn (ProductRequest $record) => "Request {$record->jumlah} pcs \"{$record->product?->nama}\" akan ditandai sebagai Diproses.")
                        ->modalSubmitActionLabel('Ya, Tandai Diproses')
                        ->visible(fn (ProductRequest $record) => Auth::user()?->role === 'admin' && $record->status === 'pending')
                        ->action(function (ProductRequest $record) {
                            $record->update(['status' => 'diproses']);

                            Notification::make()
                                ->title('Request Sedang Diproses')
                                ->body("{$record->jumlah} pcs \"{$record->product?->nama}\" kini berstatus Diproses dan menunggu penyiapan barang.")
                                ->icon('heroicon-o-arrow-path')
                                ->iconColor('info')
                                ->color('info')
                                ->duration(4500)
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
                        ->visible(fn (ProductRequest $record) => Auth::user()?->role === 'admin' && $record->status === 'diproses')
                        ->action(function (ProductRequest $record) {
                            $record->update(['status' => 'selesai']);

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
            'index'  => Pages\ListProductRequests::route('/'),
            'create' => Pages\CreateProductRequest::route('/create'),
            'edit'   => Pages\EditProductRequest::route('/{record}/edit'), 
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (Auth::user()?->role !== 'admin') return null;

        $count = ProductRequest::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}