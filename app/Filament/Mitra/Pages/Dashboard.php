<?php

namespace App\Filament\Mitra\Pages;

use App\Models\ConsignmentReturn;
use App\Models\ConsignmentStock;
use App\Models\ProductRequest;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard Mitra';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'mitra';
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Mitra\Widgets\MitraStatsOverview::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 3;
    }
}
