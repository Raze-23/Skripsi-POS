<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Illuminate\Support\Facades\Auth;

class Stock extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Stok';

    protected static ?string $clusterBreadcrumb = 'Stok';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin';
    }
}
