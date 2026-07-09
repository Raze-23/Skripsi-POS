<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Support\Facades\Auth;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Dasbor')
                    ->icon('heroicon-m-funnel')
                    ->schema([
                        ToggleButtons::make('periode')
                            ->hiddenLabel()
                            ->options([
                                'hari_ini' => 'Hari Ini',
                                'minggu_ini' => '7 Hari Terakhir',
                                'bulan_ini' => 'Bulan Ini',
                                'tahun_ini' => 'Tahun Ini',
                                'kustom' => 'Pilih Tanggal',
                            ])
                            ->icons([
                                'hari_ini' => 'heroicon-m-calendar-days',
                                'minggu_ini' => 'heroicon-m-calendar',
                                'bulan_ini' => 'heroicon-m-chart-pie',
                                'tahun_ini' => 'heroicon-m-chart-bar',
                                'kustom' => 'heroicon-m-adjustments-horizontal',
                            ])
                            ->colors([
                                'hari_ini' => 'primary',
                                'minggu_ini' => 'info',
                                'bulan_ini' => 'success',
                                'tahun_ini' => 'warning',
                                'kustom' => 'danger',
                            ])
                            ->inline()
                            ->default('hari_ini')
                            ->live(),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Mulai Tanggal')
                                    ->default(now()->toDateString())
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->live(),
                                DatePicker::make('end_date')
                                    ->label('Sampai Tanggal')
                                    ->default(now()->toDateString())
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->live(),
                            ])
                            ->visible(fn (Get $get) => $get('periode') === 'kustom'),
                    ])
                    ->collapsible()
            ]);
    }
}