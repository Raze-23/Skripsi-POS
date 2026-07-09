<?php

namespace App\Models;

use Filament\Models\Contracts\HasAvatar; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements HasAvatar
{
    use HasFactory, Notifiable;


    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'kasir_id');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&color=ffffff&background=10b981&bold=true";
    }


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
