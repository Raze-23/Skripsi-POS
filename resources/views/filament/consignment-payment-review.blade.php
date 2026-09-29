@php
    $isTransfer = $record->metode_pembayaran === 'transfer_bank';
    $isPending = $record->status === 'menunggu_validasi';
    $proofUrl = $isTransfer && $record->bukti_pembayaran
        ? route('consignment-payment-proof.show', $record)
        : null;
@endphp

<style>
    .consignment-review { color: #172c27; font-size: 14px; line-height: 1.5; }
    .consignment-review * { box-sizing: border-box; }
    .consignment-review__hero { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 22px; border: 1px solid #b9dfcf; border-radius: 16px; background: linear-gradient(120deg, #ecfdf5, #f7fcfa); }
    .consignment-review__eyebrow { color: #376456; font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
    .consignment-review__amount { margin-top: 3px; color: #065f46; font-size: 29px; font-weight: 800; line-height: 1.2; }
    .consignment-review__subtle { margin-top: 5px; color: #64748b; font-size: 12px; }
    .consignment-review__badge { display: inline-flex; align-items: center; padding: 7px 11px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 700; }
    .consignment-review__badge--done { background: #d1fae5; color: #047857; }
    .consignment-review__card { margin-top: 16px; padding: 18px 20px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; }
    .consignment-review__title { margin: 0 0 14px; color: #0f2922; font-size: 15px; font-weight: 750; }
    .consignment-review__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 24px; row-gap: 16px; margin: 0; }
    .consignment-review__item { min-width: 0; }
    .consignment-review__item dt { color: #64748b; font-size: 12px; font-weight: 600; }
    .consignment-review__item dd { margin: 3px 0 0; overflow-wrap: anywhere; color: #182c26; font-size: 14px; font-weight: 650; }
    .consignment-review__item dd small { display: block; margin-top: 2px; color: #64748b; font-size: 12px; font-weight: 400; }
    .consignment-review__proof { display: flex; align-items: center; justify-content: center; width: 100%; min-height: 220px; padding: 12px; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 11px; background: #f8fafc; }
    .consignment-review__proof img { display: block; max-width: 100%; max-height: 360px; object-fit: contain; border-radius: 6px; }
    .consignment-review__link { display: inline-flex; align-items: center; gap: 5px; margin-top: 12px; color: #047857; font-size: 13px; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; }
    .consignment-review__note { margin-top: 16px; padding: 12px 15px; border-left: 3px solid #10b981; border-radius: 8px; background: #f0fdf4; color: #38554a; font-size: 13px; }
    @media (max-width: 640px) {
        .consignment-review__hero { padding: 17px; }
        .consignment-review__amount { font-size: 24px; }
        .consignment-review__card { padding: 16px; }
        .consignment-review__grid { grid-template-columns: 1fr; row-gap: 13px; }
    }
</style>

<div class="consignment-review">
    <div class="consignment-review__hero">
        <div>
            <div class="consignment-review__eyebrow">{{ $isPending ? 'Tagihan menunggu validasi' : 'Pembayaran retur' }}</div>
            <div class="consignment-review__amount">Rp {{ number_format($record->omzet_terbentuk, 0, ',', '.') }}</div>
            <div class="consignment-review__subtle">{{ $record->terjual }} pcs terjual × Rp {{ number_format($record->harga_satuan, 0, ',', '.') }} per pcs</div>
        </div>
        <span class="consignment-review__badge {{ $isPending ? '' : 'consignment-review__badge--done' }}">
            {{ $isPending ? 'Menunggu Validasi' : 'Selesai' }}
        </span>
    </div>

    <section class="consignment-review__card" aria-label="Rincian penarikan">
        <h3 class="consignment-review__title">Rincian Penarikan</h3>
        <dl class="consignment-review__grid">
            <div class="consignment-review__item"><dt>Apotek Mitra</dt><dd>{{ $record->partner->nama_apotek }}</dd></div>
            <div class="consignment-review__item"><dt>Produk dan Batch</dt><dd>{{ $record->productBatch->product->nama }}<small>{{ $record->productBatch->batch_code }}</small></dd></div>
            <div class="consignment-review__item"><dt>Rincian Barang</dt><dd>{{ $record->terjual }} terjual, {{ $record->qty_layak }} layak, {{ $record->qty_rusak }} rusak</dd></div>
            <div class="consignment-review__item"><dt>Waktu Pengajuan</dt><dd>{{ $record->diajukan_pada?->format('d M Y, H:i') ?? '-' }}</dd></div>
        </dl>
    </section>

    <section class="consignment-review__card" aria-label="Informasi pembayaran">
        <h3 class="consignment-review__title">Informasi Pembayaran</h3>
        <dl class="consignment-review__grid">
            <div class="consignment-review__item"><dt>Metode</dt><dd>{{ $isTransfer ? 'Transfer Bank' : 'Titip Tunai ke Sales' }}</dd></div>
            @if ($isTransfer)
                <div class="consignment-review__item"><dt>Rekening Tujuan</dt><dd>{{ $record->bank_tujuan }}: {{ $record->rekening_tujuan }}<small>Atas nama {{ $record->pemilik_rekening_tujuan }}</small></dd></div>
                <div class="consignment-review__item"><dt>Pengirim</dt><dd>{{ $record->nama_pengirim }}<small>{{ $record->bank_pengirim }}</small></dd></div>
                <div class="consignment-review__item"><dt>Waktu Transfer</dt><dd>{{ $record->dibayar_pada?->format('d M Y, H:i') ?? '-' }}</dd></div>
                <div class="consignment-review__item"><dt>Nomor Referensi</dt><dd>{{ $record->referensi_transfer ?: 'Tidak dicantumkan' }}</dd></div>
            @else
                <div class="consignment-review__item"><dt>Sales Penerima</dt><dd>{{ $record->sales?->nama ?? '-' }}</dd></div>
            @endif
        </dl>
    </section>

    @if ($proofUrl)
        <section class="consignment-review__card" aria-label="Bukti transfer">
            <h3 class="consignment-review__title">Bukti Transfer</h3>
            <div class="consignment-review__proof">
                <img src="{{ $proofUrl }}" alt="Bukti transfer dari {{ $record->partner->nama_apotek }}" />
            </div>
            <a class="consignment-review__link" href="{{ $proofUrl }}" target="_blank" rel="noopener noreferrer">Buka gambar ukuran penuh</a>
        </section>
    @endif

    @if (auth()->user()?->role === 'admin' && $isPending)
        <p class="consignment-review__note">Cocokkan nominal dan bukti dengan mutasi rekening atau setoran Sales sebelum menerima pembayaran.</p>
    @endif
</div>
