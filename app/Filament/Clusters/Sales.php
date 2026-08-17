<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Illuminate\Support\Facades\Auth;

class Sales extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Kasir';

    protected static ?int $navigationSort = 1;

    protected static ?string $clusterBreadcrumb = 'Kasir';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['kasir']);
    }
}
