<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Tugas Penarikan - Herbalattiin</title>
    <style>
        @include('pdf.styles.surat-tugas')
    </style>
</head>
<body onload="window.print()">

    <div class="print-container">

        @php
            $logoPath = public_path('images/logo-attiin.png');
            $logoExists = file_exists($logoPath);
            $logoBase64 = $logoExists
                ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                : null;
        @endphp

        <table class="letterhead">
            <tr>
                <td>
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="CV. Herbal At-Tiin" class="brand-logo-img">
                    @else
                        <div class="brand-logo-fallback">CV. HERBAL AT-TIIN</div>
                    @endif
                    <div class="brand-contact">
                        Jl. Kutilang, Kerten, Kec. Laweyan, Kota Surakarta, Jawa Tengah<br>
                        Telp: 0812-3672-1367
                    </div>
                </td>
                <td class="col-date">
                    <div class="print-date">
                        Dicetak: <strong>{{ \Carbon\Carbon::now()->translatedFormat('d M Y, H:i') }}</strong>
                    </div>
                </td>
            </tr>
        </table>

        <div class="title-banner">
            <h1>Surat Tugas Penarikan Barang</h1>
            <p>Dokumen resmi untuk proses verifikasi dan serah terima produk yang mendekati masa kedaluwarsa. Surat ini ditandatangani oleh pihak apotek dan petugas penarik setelah seluruh proses penyortiran serta pemeriksaan barang selesai dilakukan.</p>
            <div class="badge-row">
                <span class="badge">Wajib Verifikasi</span>
            </div>
        </div>

        @foreach($stocks as $namaApotek => $items)
            <div class="apotek-section">

                <div class="apotek-header">
                    <table class="apotek-header-table">
                        <tr>
                            <td class="col-index">
                                <span class="apotek-index">Lokasi {{ $loop->iteration }}/{{ $loop->count }}</span>
                            </td>
                            <td>
                                <span class="apotek-title">{{ $namaApotek }}</span>
                                <span class="apotek-subtitle">Lakukan verifikasi fisik untuk seluruh item di bawah ini.</span>
                            </td>
                            <td class="col-stats">
                                <div class="stat-block"><span class="stat-value">{{ $items->count() }} SKU</span> <span class="stat-label">jenis produk</span></div>
                                <div class="stat-block"><span class="stat-value">{{ $items->sum('stok_titipan') }} pcs</span> <span class="stat-label">stok konsinyasi</span></div>
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="28%">Deskripsi Produk</th>
                            <th width="14%">Batch Code</th>
                            <th width="10%" class="text-center">Stok Konsinyasi</th>
                            <th width="12%" class="text-center">Terjual (Pcs)</th>
                            <th width="15%" class="text-center">Retur Layak (Pcs)</th>
                            <th width="16%" class="text-center">Retur Rusak (Pcs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $index => $item)
                            <tr>
                                <td class="text-center text-light">{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $item->productBatch->product->nama ?? 'Produk Dihapus' }}</strong>
                                </td>
                                <td class="font-mono">{{ $item->productBatch->batch_code }}</td>
                                <td class="text-center"><strong>{{ $item->stok_titipan }}</strong></td>
                                <td><span class="fill-box"></span></td>
                                <td><span class="fill-box"></span></td>
                                <td><span class="fill-box"></span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="notes-box">
                    <div class="notes-label">Catatan / Keterangan</div>
                    <div class="notes-lines"></div>
                </div>

            </div>
        @endforeach

        <div class="final-signoff">
            <div class="final-signoff-label">Verifikasi &amp; Serah Terima</div>
            <div class="final-signoff-sub">Mencakup seluruh lokasi pada proses penarikan ini.</div>
            <table class="signatures">
                <tr>
                    <td class="sig-left">
                        <div class="sig-title">Mengetahui,<br>Pihak Apotek</div>
                        <div class="sig-line"></div>
                        <div class="sig-name">Nama Terang &amp; Stempel</div>
                        <div class="sig-date">Tanggal: ______________</div>
                    </td>
                    <td class="sig-right">
                        <div class="sig-title">Petugas Penarikan<br>(Sales)</div>
                        <div class="sig-line"></div>
                        <div class="sig-name">Nama Terang &amp; Tanda Tangan</div>
                        <div class="sig-date">Tanggal: ______________</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="doc-footer">
            Dokumen dihasilkan otomatis oleh Sistem Herbalattiin &mdash; bersifat rahasia &amp; hanya untuk keperluan internal.
        </div>

    </div>

</body>
</html>

