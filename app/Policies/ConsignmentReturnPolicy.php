<?php

namespace App\Policies;

use App\Models\ConsignmentReturn;
use App\Models\User;

class ConsignmentReturnPolicy
{
    public function before (User $user, string $ability)
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
    public function view(User $user, ConsignmentReturn $consignmentReturn): bool
    {
        return false;
    }
    public function create(User $user): bool
    {
        return false;
    }
    public function update(User $user, ConsignmentReturn $consignmentReturn): bool
    {
        return false;
    }
    public function delete(User $user, ConsignmentReturn $consignmentReturn): bool
    {
        return false;
    }
    public function restore(User $user, ConsignmentReturn $consignmentReturn): bool
    {
        return false;
    }
    public function forceDelete(User $user, ConsignmentReturn $consignmentReturn): bool
    {
        return false;
    }
}
