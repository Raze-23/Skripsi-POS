<?php

namespace App\Filament\Mitra\Widgets;

use App\Models\ConsignmentReturn;
use App\Models\ConsignmentStock;
use App\Models\ProductRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MitraStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $partnerId = Auth::user()?->partner_id;

        $stokAktif = ConsignmentStock::where('partner_id', $partnerId)
            ->where('stok_titipan', '>', 0)
            ->count();

        $totalStok = ConsignmentStock::where('partner_id', $partnerId)
            ->sum('stok_titipan');

        $menungguKonfirmasi = ConsignmentReturn::where('partner_id', $partnerId)
            ->where('status', 'menunggu_konfirmasi')
            ->count();

        $menungguValidasi = ConsignmentReturn::where('partner_id', $partnerId)
            ->where('status', 'menunggu_validasi')
            ->count();

        $requestPending = ProductRequest::where('partner_id', $partnerId)
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('Stok Titipan Aktif', $totalStok . ' pcs')
                ->description($stokAktif . ' jenis produk dari apotek')
                ->descriptionIcon('heroicon-o-cube')
                ->color('success')
                ->icon('heroicon-o-building-storefront'),

            Stat::make('Penarikan Menunggu Konfirmasi', $menungguKonfirmasi)
                ->description('Segera konfirmasi rincian penarikan')
                ->descriptionIcon($menungguKonfirmasi > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($menungguKonfirmasi > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-receipt-refund'),

            Stat::make('Pembayaran Menunggu Validasi', $menungguValidasi)
                ->description('Admin sedang memeriksa pembayaran')
                ->descriptionIcon('heroicon-o-clock')
                ->color($menungguValidasi > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Request Produk Pending', $requestPending)
                ->description('Menunggu diproses oleh admin')
                ->descriptionIcon('heroicon-o-clock')
                ->color($requestPending > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-clipboard-document-check'),
        ];
    }
}
