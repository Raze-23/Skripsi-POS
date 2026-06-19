<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CV. Herbal At-Tiin</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body {
            font-family: 'Georgia', serif;
            color: #1a1a1a;
            background-color: #ffffff;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .primary-color { color: #065f46; }
        .accent-color { color: #d4af37; }

        .header { text-align: center; margin-bottom: 50px; border-bottom: 2px solid #d4af37; padding-bottom: 20px; }

        .logo { width: 120px; height: auto; margin-bottom: 15px; }

        .header h1 {
            font-size: 28px;
            font-weight: 300;
            letter-spacing: 6px;
            margin: 0;
            text-transform: uppercase;
            color: #065f46;
        }
        .header p {
            font-size: 12px;
            font-style: italic;
            color: #d4af37;
            letter-spacing: 2px;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .menu-container { width: 100%; margin-top: 30px; }
        .menu-item { width: 100%; margin-bottom: 30px; page-break-inside: avoid; }

        .menu-main { width: 100%; display: table; }
        .menu-title {
            display: table-cell;
            text-align: left;
            font-size: 16px;
            font-weight: bold;
            color: #065f46;
            padding-right: 10px;
        }
        .menu-line {
            display: table-cell;
            border-bottom: 1px solid #d4af37;
            width: auto;
            opacity: 0.5;
        }
        .menu-price {
            display: table-cell;
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #065f46;
            padding-left: 10px;
            width: 120px;
        }

        .menu-sub {
            font-size: 11px;
            color: #555555;
            font-family: 'Arial', sans-serif;
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        @php
            $logoPath = public_path('images/logo-attiin.png');
            $logoData = '';
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
            }
        @endphp

        @if($logoData)
            <img src="data:image/png;base64,{{ $logoData }}" class="logo">
        @endif

        <h1>CV. Herbal At-Tiin</h1>
        <p>Daftar Produk Herbal ATTIIN</p>
    </div>

    <div class="menu-container">
        @foreach($products as $product)
            <div class="menu-item">
                <div class="menu-main">
                    <div class="menu-title">{{ strtoupper($product->nama) }}</div>
                    <div class="menu-line"></div>
                    <div class="menu-price">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</div>
                </div>
                <div class="menu-sub">
                    <span style="color: #d4af37;"></span> SKU: {{ $product->sku }}
                    &nbsp;&nbsp; <span style="color: #d4af37;"></span> Sisa Stok: {{ $product->stok_toko }}
                    &nbsp;&nbsp; <span style="color: #d4af37;"></span> Kedaluwarsa: {{ $product->tanggal_kedaluwarsa->format('d/m/Y') }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="footer">
        Dicetak secara resmi oleh Sistem Inventaris CV. Herbal At-Tiin pada {{ date('d F Y') }}
    </div>

</body>
</html>
