<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;

class PartnerPolicy
{
    public function before(User $user, string $ability) : bool|null
    {
        if ($user->role === 'admin') {
            return true;
        }
        return null;
    }
    public function viewAny(User $user): bool
    {
        return false;
    }
    public function view(User $user, Partner $partner): bool
    {
        return false;
    }
    public function create(User $user): bool
    {
        return false;
    }
    public function update(User $user, Partner $partner): bool
    {
        return false;
    }
    public function delete(User $user, Partner $partner): bool
    {
        return false;
    }
    public function restore(User $user, Partner $partner): bool
    {
        return false;
    }
    public function forceDelete(User $user, Partner $partner): bool
    {
        return false;
    }
}
