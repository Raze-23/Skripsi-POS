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
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ConsignmentReturn $consignmentReturn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ConsignmentReturn $consignmentReturn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ConsignmentReturn $consignmentReturn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ConsignmentReturn $consignmentReturn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ConsignmentReturn $consignmentReturn): bool
    {
        return false;
    }
}
