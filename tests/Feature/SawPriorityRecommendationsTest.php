<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ProductionRecommendations;
use App\Filament\Widgets\DailyOutflowChart;
use App\Filament\Widgets\MonthlyRevenueTrendChart;
use App\Filament\Widgets\SawPriorityWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TopProductsTable;
use App\Models\Product;
use App\Models\SawPrioritySnapshot;
use App\Models\User;
use App\Services\SawPriorityMovementTracker;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class SawPriorityRecommendationsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dashboard_excludes_top_products_and_saw_widgets(): void
    {
        $widgets = app(Dashboard::class)->getWidgets();

        $this->assertSame([
            StatsOverview::class,
            MonthlyRevenueTrendChart::class,
            DailyOutflowChart::class,
        ], $widgets);
        $this->assertNotContains(TopProductsTable::class, $widgets);
        $this->assertNotContains(SawPriorityWidget::class, $widgets);
    }

    public function test_production_recommendation_page_only_contains_saw_widget(): void
    {
        $this->assertSame(
            [SawPriorityWidget::class],
            app(ProductionRecommendations::class)->getWidgets(),
        );
    }

    public function test_saw_widget_renders_the_score_chart_and_decision_matrix(): void
    {
        $admin = User::create([
            'name' => 'Admin Pengujian SAW',
            'email' => 'admin-saw-widget@example.com',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $this->createProduct('Produk Render SAW');
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(SawPriorityWidget::class, [
            'filters' => ['year' => (int) now()->year],
        ])
            ->assertSee('Peringkat dan Skor SAW')
            ->assertSee('Rincian Matriks Keputusan')
            ->assertSee('poin');
    }

    public function test_rank_movements_remain_visible_until_the_next_change(): void
    {
        $firstProduct = $this->createProduct('Produk SAW Pertama');
        $secondProduct = $this->createProduct('Produk SAW Kedua');
        $tracker = app(SawPriorityMovementTracker::class);
        $year = (int) now()->year;

        $initial = $tracker->track([
            $this->rankedRow($firstProduct, 0.8000, 20),
            $this->rankedRow($secondProduct, 0.6000, 10),
        ], $year);

        $this->assertSame('new', $initial[0]['movement']['type']);
        $this->assertSame(80.0, $initial[0]['poin_saw']);

        $changed = $tracker->track([
            $this->rankedRow($secondProduct, 0.9000, 30),
            $this->rankedRow($firstProduct, 0.7000, 20),
        ], $year);

        $this->assertSame('up', $changed[0]['movement']['type']);
        $this->assertSame('Naik 1 peringkat', $changed[0]['movement']['label']);
        $this->assertSame('down', $changed[1]['movement']['type']);
        $this->assertSame('Turun 1 peringkat', $changed[1]['movement']['label']);

        $unchangedRefresh = $tracker->track([
            $this->rankedRow($secondProduct, 0.9000, 30),
            $this->rankedRow($firstProduct, 0.7000, 20),
        ], $year);

        $this->assertSame('up', $unchangedRefresh[0]['movement']['type']);
        $this->assertSame('down', $unchangedRefresh[1]['movement']['type']);
        $this->assertSame(
            2,
            SawPrioritySnapshot::query()
                ->where('year', $year)
                ->whereIn('product_id', [$firstProduct->id, $secondProduct->id])
                ->count(),
        );

        $snapshot = SawPrioritySnapshot::query()
            ->where('product_id', $secondProduct->id)
            ->where('year', $year)
            ->firstOrFail();

        $this->assertSame(1, $snapshot->current_rank);
        $this->assertSame(2, $snapshot->previous_rank);
        $this->assertSame(0.9, $snapshot->current_score);
        $this->assertSame(0.6, $snapshot->previous_score);
    }

    private function createProduct(string $name): Product
    {
        return Product::create([
            'nama' => $name,
            'harga_beli' => 8_000,
            'harga_jual' => 12_000,
        ]);
    }

    private function rankedRow(Product $product, float $score, int $sales): array
    {
        return [
            'id' => $product->id,
            'nama' => $product->nama,
            'score' => $score,
            'c1_display' => $sales,
            'c2_display' => 60,
            'c3_display' => 10,
            'c4_display' => 2,
            'c5_display' => 1,
        ];
    }
}
