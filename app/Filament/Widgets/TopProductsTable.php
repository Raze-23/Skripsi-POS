<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopProductsTable extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.top-products-table';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 5;

    public function getTopProducts(): array
    {
        $year = (int) ($this->filters['year'] ?? now()->year);

        $kasirSales = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('product_batches', 'product_batches.id', '=', 'transaction_details.product_batch_id')
            ->where('transactions.status', 'Selesai')
            ->whereYear('transactions.created_at', $year)
            ->select('product_batches.product_id', DB::raw('SUM(transaction_details.qty) as kasir_qty'))
            ->groupBy('product_batches.product_id')
            ->pluck('kasir_qty', 'product_id');

        $apotekSales = DB::table('consignment_returns')
            ->join('product_batches', 'product_batches.id', '=', 'consignment_returns.product_batch_id')
            ->whereYear('consignment_returns.created_at', $year)
            ->select('product_batches.product_id', DB::raw('SUM(consignment_returns.terjual) as apotek_qty'))
            ->groupBy('product_batches.product_id')
            ->pluck('apotek_qty', 'product_id');

        $allProductIds = $kasirSales->keys()->merge($apotekSales->keys())->unique();

        if ($allProductIds->isEmpty()) return [];

        $products = DB::table('products')
            ->whereIn('id', $allProductIds)
            ->pluck('nama', 'id');

        $combined = $allProductIds->map(function ($productId) use ($kasirSales, $apotekSales, $products) {
            $kasir = (int) ($kasirSales[$productId] ?? 0);
            $apotek = (int) ($apotekSales[$productId] ?? 0);

            return [
                'nama' => $products[$productId] ?? 'Produk Dihapus',
                'kasir' => $kasir,
                'apotek' => $apotek,
                'total' => $kasir + $apotek,
            ];
        })
        ->sortByDesc('total')
        ->take(5)
        ->values()
        ->toArray();

        return $combined;
    }
}
