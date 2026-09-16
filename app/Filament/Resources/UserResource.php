<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\ConsignmentReturn;
use App\Models\Partner;
use App\Models\ProductRequest;
use App\Models\User;
use Closure;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Kelola Pengguna';
    protected static ?string $pluralLabel = 'Daftar Pengguna';
    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function getDeleteBlockers(User $user): array
    {
        $blockers = [];

        if ($user->transactions()->exists()) {
            $blockers[] = 'memiliki riwayat transaksi POS';
        }

        $hasProductRequests = $user->productRequests()->exists();

        if ($user->role === 'mitra' && filled($user->partner_id)) {
            $hasProductRequests = $hasProductRequests || ProductRequest::query()
                ->where('partner_id', $user->partner_id)
                ->where('tipe_request', 'restok_apotek')
                ->exists();

            if (
                ConsignmentReturn::query()
                    ->where('partner_id', $user->partner_id)
                    ->where('status', 'selesai')
                    ->exists()
            ) {
                $blockers[] = 'memiliki riwayat konfirmasi penarikan produk';
            }
        }

        if ($hasProductRequests) {
            $blockers[] = match ($user->role) {
                'owner' => 'memiliki riwayat request produksi produk',
                'mitra' => 'memiliki riwayat request produk',
                default => 'memiliki riwayat request produk',
            };
        }

        return $blockers;
    }

    public static function sendDeleteBlockedNotification(User $user, array $blockers, string $title = 'Gagal Menghapus Akun'): void
    {
        Notification::make()
            ->danger()
            ->title($title)
            ->body("Akun {$user->name} tidak bisa dihapus karena " . implode(', ', $blockers) . '.')
            ->icon('heroicon-o-exclamation-triangle')
            ->send();
    }

    /**
     * Query dropdown apotek yang belum dikaitkan ke akun Mitra lain.
     * Dipakai baik di form Koreksi maupun di modal "Konfirmasi & Kaitkan".
     */
    protected static function getApotekBelumTerkaitOptions(?User $record): array
    {
        $partnerIdTerpakai = User::query()
            ->where('role', 'mitra')
            ->whereNotNull('partner_id')
            ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
            ->pluck('partner_id');

        return Partner::query()
            ->whereNotIn('id', $partnerIdTerpakai)
            ->pluck('nama_apotek', 'id')
            ->toArray();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun Pengguna')
                    ->description('Masukkan rincian kredensial dan hak akses untuk pengguna ini.')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->prefixIcon('heroicon-o-user')
                            ->required(fn (Get $get): bool => $get('role') !== 'mitra')
                            ->maxLength(255)
                            ->disabled(fn (Get $get): bool => $get('role') === 'mitra')
                            ->helperText(fn (Get $get): ?string => $get('role') === 'mitra'
                                ? 'Nama diisi oleh Mitra sendiri saat mendaftar dan tidak bisa diubah dari sini.'
                                : null),

                        Forms\Components\TextInput::make('email')
                            ->label('Alamat Email')
                            ->prefixIcon('heroicon-o-at-symbol')
                            ->email()
                            ->required(fn (Get $get): bool => $get('role') !== 'mitra')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->disabled(fn (Get $get): bool => $get('role') === 'mitra')
                            ->helperText(fn (Get $get): ?string => $get('role') === 'mitra'
                                ? 'Email diisi oleh Mitra sendiri saat mendaftar dan tidak bisa diubah dari sini.'
                                : null)
                            ->validationMessages([
                                'unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain.',
                            ]),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->disabled(fn (Get $get): bool => $get('role') === 'mitra')
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (Get $get, ?string $state): bool => $get('role') !== 'mitra' && filled($state))
                            ->required(fn (string $operation, Get $get): bool => $operation === 'create' && $get('role') !== 'mitra')
                            ->helperText(fn (Get $get, string $operation): string => $get('role') === 'mitra'
                                ? 'Password dikelola langsung oleh Mitra lewat akunnya sendiri (menu Edit Profile).'
                                : ($operation === 'edit' ? 'Kosongkan jika tidak ingin mengubah password.' : 'Minimal 8 karakter.')),

                        Forms\Components\Select::make('role')
                            ->label('Hak Akses (Role)')
                            ->prefixIcon('heroicon-o-shield-check')
                            ->options(function (string $operation): array {
                                $options = [
                                    'admin' => 'Admin',
                                    'kasir' => 'Kasir',
                                    'owner' => 'Owner',
                                ];

                                if ($operation === 'edit') {
                                    $options['mitra'] = 'Apotek Mitra';
                                }

                                return $options;
                            })
                            ->required()
                            ->live()
                            ->native(false)
                            ->disabled(fn (string $operation, ?User $record): bool => $operation === 'edit' && $record?->role === 'mitra')
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if ($state !== 'mitra') {
                                    $set('partner_id', null);
                                }
                            }),

                        Forms\Components\Select::make('partner_id')
                            ->label('Apotek Terkait')
                            ->prefixIcon('heroicon-o-building-storefront')
                            ->options(fn (?User $record) => static::getApotekBelumTerkaitOptions($record))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('role') === 'mitra')
                            ->required(fn (Get $get): bool => $get('role') === 'mitra')
                            ->helperText('Hanya menampilkan apotek yang belum terhubung ke akun Mitra lain.')
                            ->rule(function (Get $get, ?User $record) {
                                return function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    if ($get('role') !== 'mitra' || blank($value)) {
                                        return;
                                    }

                                    $sudahDipakai = User::query()
                                        ->where('partner_id', $value)
                                        ->where('role', 'mitra')
                                        ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                        ->exists();

                                    if ($sudahDipakai) {
                                        $fail('Apotek ini sudah terhubung dengan akun Mitra lain.');
                                    }
                                };
                            })
                            ->validationMessages([
                                'required' => 'Apotek wajib dipilih untuk akun Mitra.',
                            ]),

                        Forms\Components\Select::make('status')
                            ->label('Status Konfirmasi')
                            ->prefixIcon('heroicon-o-check-badge')
                            ->options([
                                'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                                'aktif' => 'Aktif & Terverifikasi',
                            ])
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('role') === 'mitra')
                            ->required(fn (Get $get): bool => $get('role') === 'mitra')
                            ->dehydrated(fn (Get $get): bool => $get('role') === 'mitra')
                            ->helperText('Akun baru bisa dipakai untuk login setelah statusnya "Aktif" dan sudah dikaitkan ke sebuah apotek.')
                            ->rule(function (Get $get) {
                                return function (string $attribute, $value, Closure $fail) use ($get) {
                                    if ($get('role') === 'mitra' && $value === 'aktif' && blank($get('partner_id'))) {
                                        $fail('Akun belum bisa diaktifkan sebelum dikaitkan dengan apotek.');
                                    }
                                };
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email disalin!')
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role Akses')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'admin' => 'danger',
                        'kasir' => 'info',
                        'owner' => 'warning',
                        'mitra' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'admin' => 'Admin',
                        'kasir' => 'Kasir',
                        'owner' => 'Owner',
                        'mitra' => 'Mitra',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'aktif' ? 'success' : 'warning')
                    ->icon(fn (string $state) => $state === 'aktif' ? 'heroicon-o-check-badge' : 'heroicon-o-clock')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'aktif' => 'Aktif',
                        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('partner.nama_apotek')
                    ->label('Terkoneksi ke Apotek')
                    ->color('gray')
                    ->placeholder('Belum dikaitkan')
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Saring berdasarkan Role')
                    ->options([
                        'admin' => 'Admin',
                        'kasir' => 'Kasir',
                        'owner' => 'Owner',
                        'mitra' => 'Mitra',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Saring berdasarkan Status')
                    ->options([
                        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                        'aktif' => 'Aktif',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('konfirmasi')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->role === 'mitra' && $record->status === 'menunggu_konfirmasi')
                    ->form(fn (User $record) => [
                        Forms\Components\Select::make('partner_id')
                            ->label('Apotek yang Dikaitkan')
                            ->prefixIcon('heroicon-o-building-storefront')
                            ->placeholder('Cari atau pilih apotek')
                            ->options(fn () => static::getApotekBelumTerkaitOptions($record))
                            ->searchable()
                            ->searchPrompt('Ketik nama apotek')
                            ->searchingMessage('Mencari apotek...')
                            ->noSearchResultsMessage('Apotek tidak ditemukan dalam daftar yang tersedia.')
                            ->preload()
                            ->native(false)
                            ->required()
                            ->helperText('Hanya menampilkan apotek yang belum terhubung ke akun Mitra lain.')
                            ->validationMessages([
                                'required' => 'Apotek wajib dipilih.',
                            ]),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update([
                            'partner_id' => $data['partner_id'],
                            'status' => 'aktif',
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Akun Mitra Dikonfirmasi')
                            ->body("Akun {$record->name} telah dikaitkan ke apotek terpilih dan kini bisa masuk ke Portal Mitra.")
                            ->icon('heroicon-o-check-badge')
                            ->send();
                    })
                    ->modalIcon('heroicon-o-check-badge')
                    ->modalHeading('Konfirmasi Akun Mitra')
                    ->modalDescription('Kaitkan akun dengan apotek untuk mengaktifkan akses Portal Mitra.')
                    ->modalWidth(MaxWidth::ExtraLarge)
                    ->modalAlignment(Alignment::Start)
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->extraModalWindowAttributes(['style' => 'border-radius: 8px;'])
                    ->modalContent(fn (User $record) => view(
                        'filament.resources.user-resource.partials.confirm-mitra-account',
                        ['record' => $record],
                    ))
                    ->modalSubmitActionLabel('Konfirmasi & Kaitkan')
                    ->modalSubmitAction(fn (StaticAction $action) => $action
                        ->icon('heroicon-o-check-badge')
                        ->extraAttributes(['style' => 'background-image: none; box-shadow: none; border-radius: 6px;']))
                    ->modalCancelActionLabel('Batal')
                    ->modalFooterActionsAlignment(Alignment::End),

                Tables\Actions\EditAction::make()
                    ->label('Koreksi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn (User $record): bool => ! ($record->role === 'mitra' && $record->status === 'menunggu_konfirmasi')),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (User $record): bool => ! ($record->role === 'mitra' && $record->status === 'menunggu_konfirmasi'))
                    ->before(function (User $record, Tables\Actions\DeleteAction $action) {
                        $blockers = static::getDeleteBlockers($record);

                        if (empty($blockers)) {
                            return;
                        }

                        static::sendDeleteBlockedNotification($record, $blockers);

                        $action->halt();
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Akun Terhapus')
                            ->body('Akun pengguna telah dihapus secara permanen dari sistem.')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records, Tables\Actions\DeleteBulkAction $action) {
                            $blockedUsers = $records->filter(fn (User $record): bool => ! empty(static::getDeleteBlockers($record)));

                            if ($blockedUsers->isEmpty()) {
                                return;
                            }

                            $firstBlockedUser = $blockedUsers->first();
                            $blockers = static::getDeleteBlockers($firstBlockedUser);

                            static::sendDeleteBlockedNotification(
                                $firstBlockedUser,
                                $blockers,
                                $blockedUsers->count() > 1 ? 'Gagal Menghapus Massal' : 'Gagal Menghapus Akun'
                            );

                            $action->halt();
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Hapus Masal Berhasil')
                                ->body('Semua akun yang dipilih telah dihapus.')
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = User::query()
            ->where('role', 'mitra')
            ->where('status', 'menunggu_konfirmasi')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Ada pendaftaran akun Mitra yang menunggu konfirmasi';
    }
}
