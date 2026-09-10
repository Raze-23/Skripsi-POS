<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\ConsignmentReturn;
use App\Models\Partner;
use App\Models\ProductRequest;
use App\Models\User;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

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
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Alamat Email')
                            ->prefixIcon('heroicon-o-at-symbol')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->validationMessages([
                                'unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain.',
                            ]),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->password()
                            ->revealable() 
                            ->minLength(8) 
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(fn (string $operation): string => $operation === 'edit' ? 'Kosongkan jika tidak ingin mengubah password.' : 'Minimal 8 karakter.'),

                        Forms\Components\Select::make('role')
                            ->label('Hak Akses (Role)')
                            ->prefixIcon('heroicon-o-shield-check')
                            ->options([
                                'admin' => 'Admin',
                                'kasir' => 'Kasir',
                                'owner' => 'Owner',
                                'mitra' => 'Apotek Mitra',
                            ])
                            ->required()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if ($state !== 'mitra') {
                                    $set('partner_id', null);
                                }
                            }),

                        Forms\Components\Select::make('partner_id')
                            ->label('Apotek Terkait')
                            ->prefixIcon('heroicon-o-building-storefront')
                            ->options(function (?User $record) {
                                $partnerIdTerpakai = User::query()
                                    ->where('role', 'mitra')
                                    ->whereNotNull('partner_id')
                                    ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                    ->pluck('partner_id');

                                return Partner::query()
                                    ->whereNotIn('id', $partnerIdTerpakai)
                                    ->pluck('nama_apotek', 'id');
                            })
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

                Tables\Columns\TextColumn::make('partner.nama_apotek')
                    ->label('Terkoneksi ke Apotek')
                    ->color('gray')
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
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Koreksi')
                    ->icon('heroicon-o-pencil-square') 
                    ->color('primary'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash') 
                    ->color('danger')
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
}
