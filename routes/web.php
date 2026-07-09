<?php
use App\Models\Transaction;
use Illuminate\Support\Facades\Route;

Route::get('/admin/print-nota/{id}', function ($id) {
    $transaction = Transaction::with(['details.productBatch.product', 'kasir'])->findOrFail($id);
    return view('pdf.cetak-nota', compact('transaction'));
})->name('print.nota')->middleware(['auth']);
