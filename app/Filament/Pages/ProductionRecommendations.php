<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SawPriorityWidget;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Support\Facades\Auth;

class ProductionRecommendations extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'rekomendasi-restock';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $activeNavigationIcon = 'heroicon-s-presentation-chart-bar';

    protected static ?string $navigationLabel = 'Rekomendasi Restock';

    protected static ?string $title = 'Rekomendasi Restock';

    protected static ?int $navigationSort = -1;

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'owner']);
    }

    public function getWidgets(): array
    {
        return [
            SawPriorityWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }

    public function filtersForm(Form $form): Form
    {
        $currentYear = (int) now()->year;

        return $form
            ->columns(6)
            ->schema([
                TextInput::make('year')
                    ->label('Tahun Analisis')
                    ->default($currentYear)
                    ->live()
                    ->columnSpan([
                        'default' => 6,
                        'md' => 2,
                        'xl' => 1,
                    ])
                    ->rules(['numeric', 'min:2026', 'max:'.($currentYear + 1)])
                    ->extraInputAttributes([
                        'class' => 'font-semibold',
                        'style' => 'text-align: center !important;',
                        'inputmode' => 'numeric',
                        'pattern' => '[0-9]*',
                    ])
                    ->prefixAction(
                        Action::make('decrement')
                            ->icon('heroicon-m-chevron-left')
                            ->disabled(fn (Get $get): bool => (int) $get('year') <= 2026)
                            ->color(fn (Get $get): string => (int) $get('year') <= 2026 ? 'gray' : 'primary')
                            ->action(function (Set $set, Get $get): void {
                                $year = (int) $get('year');

                                if ($year > 2026) {
                                    $set('year', $year - 1);
                                }
                            })
                    )
                    ->suffixAction(
                        Action::make('increment')
                            ->icon('heroicon-m-chevron-right')
                            ->disabled(fn (Get $get): bool => (int) $get('year') >= $currentYear + 1)
                            ->color(fn (Get $get): string => (int) $get('year') >= $currentYear + 1 ? 'gray' : 'primary')
                            ->action(function (Set $set, Get $get) use ($currentYear): void {
                                $year = (int) $get('year');

                                if ($year < $currentYear + 1) {
                                    $set('year', $year + 1);
                                }
                            })
                    ),
            ]);
    }
}
