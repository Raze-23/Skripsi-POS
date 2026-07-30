<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
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
        $currentYear = now()->year;

        return $form
            ->columns(6)
            ->schema([
                TextInput::make('year')
                    ->hiddenLabel()
                    ->default($currentYear)
                    ->live()
                    ->columnSpan(1)
                    ->extraAttributes([
                        'style' => 'max-width: 180px;',
                    ])
                    ->rules(['numeric', 'min:2026'])
                    ->extraInputAttributes([
                        'class' => 'font-bold text-lg',
                        'style' => 'text-align: center !important;',
                        'inputmode' => 'numeric', 
                        'pattern' => '[0-9]*',
                    ])
                    ->prefixAction(
                        Action::make('decrement')
                            ->icon('heroicon-m-chevron-left')
                            ->disabled(fn (Get $get) => (int) $get('year') <= 2026)
                            ->color(fn (Get $get) => (int) $get('year') <= 2026 ? 'gray' : 'primary')
                            ->action(function (Set $set, Get $get) {
                                $current = (int) $get('year');
                                if ($current > 2026) {
                                    $set('year', $current - 1);
                                }
                            })
                    )
                    ->suffixAction(
                        Action::make('increment')
                            ->icon('heroicon-m-chevron-right')
                            ->color('primary')
                            ->action(function (Set $set, Get $get) {
                                $current = (int) $get('year');
                                $set('year', $current + 1);
                            })
                    ),
            ]);
    }
}