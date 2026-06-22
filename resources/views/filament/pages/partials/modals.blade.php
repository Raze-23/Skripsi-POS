<div id="barcode-scanner-modal" wire:ignore.self>
    <div class="scanner-overlay-backdrop" onclick="closeBarcodeScanner()"></div>
    <div class="scanner-panel-wrap">
        <div class="scanner-panel">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid #f3f4f6;background:#fff;"
                class="dark:bg-gray-900">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span
                        style="width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block;animation:pulse 1.5s infinite;"></span>
                    <span style="font-size:13px;font-weight:700;color:#1f2937;">Kamera Scanner</span>
                </div>
                <button onclick="closeBarcodeScanner()" type="button"
                    style="width:34px;height:34px;border-radius:8px;border:1.5px solid #e5e7eb;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;"
                    onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">
                    <svg style="width:16px;height:16px;color:#374151;" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div style="padding:12px;background:#000;min-height:250px;" wire:ignore>
                <div id="barcode-reader"></div>
            </div>
            <div style="padding:10px 16px;text-align:center;background:#fff;border-top:1px solid #f3f4f6;">
                <p style="font-size:11.5px;color:#6b7280;margin:0;">Arahkan kamera ke QR Code produk</p>
            </div>
        </div>
    </div>
</div>

@if ($showReceiptModal)
    <div class="receipt-backdrop" wire:click="closeReceiptModal"></div>
    <div class="receipt-panel">
        <div class="receipt-card" style="background:#fff; border-radius:16px; padding:24px; max-width:360px; width:100%; text-align:center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="width: 56px; height: 56px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px;">
                <svg style="width:28px;height:28px;color:#059669;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
            </div>
            <h2 style="font-size:18px; font-weight:900; color:#111827; margin:0 0 4px;">Transaksi Berhasil!</h2>
            <p style="font-size:12px; color:#6b7280; margin:0 0 20px;">Data penjualan telah tercatat ke dalam sistem</p>

            <div style="background:#f9fafb; border:1.5px solid #e5e7eb; border-radius:12px; padding:14px; margin-bottom:20px;" class="dark:bg-gray-800">
                <p style="font-size:11px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em; margin:0 0 4px;">
                    Kembalian pelanggan
                </p>
                <p style="font-size:26px; font-weight:900; color:#059669; font-variant-numeric:tabular-nums; margin:0;">
                    Rp {{ number_format($kembalianAkhir, 0, ',', '.') }}
                </p>
            </div>
            <p style="font-size:12px; color:#6b7280; margin:0 0 16px;">Apakah Anda ingin mencetak struk belanja?</p>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <button
                    wire:click="closeReceiptModal"
                    type="button"
                    style="padding:11px; border:1.5px solid #e5e7eb; border-radius:10px; background:#fff; font-size:13px; font-weight:700; color:#374151; cursor:pointer; transition:background .15s;"
                    onmouseover="this.style.background='#f3f4f6'"
                    onmouseout="this.style.background='#fff'"
                >
                    Kembali
                </button>
                <button
                    type="button"
                    onclick="jalankanCetakNota({{ $lastTransactionId }})"
                    style="padding:11px; border:none; border-radius:10px; background:#059669; font-size:13px; font-weight:700; color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:background .15s;"
                    onmouseover="this.style.background='#047857'"
                    onmouseout="this.style.background='#059669'"
                >
                    <svg style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                    </svg>
                    Cetak Nota
                </button>
            </div>
        </div>
    </div>
@endif
