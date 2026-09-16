<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements HasAvatar, FilamentUser
{
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'mitra' => $this->role === 'mitra'
                && $this->status === 'aktif'
                && filled($this->partner_id),
            'admin' => in_array($this->role, ['admin', 'kasir', 'owner']),
            default => false,
        };
    }


    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'partner_id',
        'status',
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'kasir_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function productRequests()
    {
        return $this->hasMany(ProductRequest::class);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&color=ffffff&background=10b981&bold=true";
    }

    /**
     * True jika akun mitra ini sudah dikonfirmasi & dikaitkan ke apotek.
     */
    public function isMitraAktif(): bool
    {
        return $this->role === 'mitra'
            && $this->status === 'aktif'
            && filled($this->partner_id);
    }

    /**
     * True jika akun ini masih menunggu konfirmasi admin.
     */
    public function isMenungguKonfirmasi(): bool
    {
        return $this->status === 'menunggu_konfirmasi';
    }


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
