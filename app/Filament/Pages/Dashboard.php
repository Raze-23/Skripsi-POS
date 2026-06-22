<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
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
                TextInput::make('year')
                    ->label('Tahun Laporan')
                    ->default(date('Y'))
                    ->numeric()
                    ->extraAttributes(['style' => 'max-width: 220px;'])
                    ->extraInputAttributes(['style' => 'text-align: center; font-weight: 900; font-size: 1.1rem;'])
                    ->prefixAction(
                        Action::make('mundur')
                            ->icon('heroicon-m-chevron-left')
                            ->action(fn (Set $set, $state) => $set('year', (int)$state - 1))
                    )
                    ->suffixAction(
                        Action::make('maju')
                            ->icon('heroicon-m-chevron-right')
                            ->action(fn (Set $set, $state) => $set('year', (int)$state + 1))
                    )
                    ->live(),
            ]);
    }
}
