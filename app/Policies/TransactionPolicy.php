<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'kasir';
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->role === 'kasir';
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return false;
    }

    public function cancel(User $user, Transaction $transaction): bool
    {
        return $user->role === 'kasir' && $transaction->status !== 'Batal';
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return false;
    }

    public function restore(User $user, Transaction $transaction): bool
    {
        return false;
    }

    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return false;
    }
}
