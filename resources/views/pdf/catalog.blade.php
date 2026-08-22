<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CV. Herbal At-Tiin</title>
    <style>
        @include('pdf.styles.catalog')
    </style>
</head>
<body>

    <div class="header">
        @php
            $logoPath = public_path('images/logo-attiin.png');
            if (!file_exists($logoPath)) {
                $logoPath = base_path('../public_html/images/logo-attiin.png');
            }
            if (!file_exists($logoPath) && isset($_SERVER['DOCUMENT_ROOT'])) {
                $logoPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/images/logo-attiin.png';
            }
            $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        @endphp

        @if($logoData)
            <img src="data:image/png;base64,{{ $logoData }}" class="logo">
        @endif

        <h1>CV. Herbal At-Tiin</h1>
        <p>Daftar Produk Herbal ATTIIN</p>
    </div>

    <div class="menu-container">
        @foreach($batches as $batch)
            <div class="menu-item">
                <div class="menu-main">
                    <div class="menu-title">{{ strtoupper($batch->product->nama) }}</div>
                    <div class="menu-line"></div>
                    <div class="menu-price">Rp {{ number_format($batch->product->harga_jual, 0, ',', '.') }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="footer">
        Dicetak secara resmi oleh Sistem Inventaris CV. Herbal At-Tiin pada {{ date('d F Y') }}
    </div>

</body>
</html>
