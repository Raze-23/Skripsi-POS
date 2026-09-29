<?php

use App\Models\ConsignmentReturn;
use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

Route::get('/admin/bukti-pembayaran-retur/{consignmentReturn}', function (ConsignmentReturn $consignmentReturn) {
    $user = auth()->user();
    abort_unless($user?->role === 'admin' || ($user?->role === 'mitra' && $user->partner_id === $consignmentReturn->partner_id), 403);
    $path = $consignmentReturn->bukti_pembayaran;
    abort_unless($path && str_starts_with($path, "bukti-pembayaran-retur/{$consignmentReturn->id}/") && Storage::disk('local')->exists($path), 404);
    return Storage::disk('local')->response($path, null, ['Content-Disposition' => 'inline']);
})->middleware('auth')->name('consignment-payment-proof.show');

Route::get('/admin/print-nota/{id}', function ($id) {
    $transaction = Transaction::with(['details.productBatch.product', 'kasir'])->findOrFail($id);
    return view('pdf.cetak-nota', compact('transaction'));
})->name('print.nota')->middleware(['auth']);

Route::get('/admin/print-surat-tugas-otomatis', function () {
    $stocks = App\Models\ConsignmentStock::with(['partner', 'productBatch.product'])
        ->where('stok_titipan', '>', 0)
        ->whereHas('productBatch', function ($query) {
            $query->whereNotNull('tanggal_kedaluwarsa')
                  ->whereDate('tanggal_kedaluwarsa', '<=', now()->addDays(30));
        })
        ->get();
    if ($stocks->isEmpty()) {
        abort(404, 'Saat ini tidak ada barang yang perlu ditarik dari Apotek manapun.');
    }
    $stocks = $stocks->groupBy('partner.nama_apotek');
    return view('pdf.surat-tugas-penarikan', compact('stocks'));
})->name('print.surat.tugas.otomatis')->middleware(['auth']);
