<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Sales extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Kasir';

    protected static ?int $navigationSort = 1;

    protected static ?string $clusterBreadcrumb = 'Kasir';
}
