<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Barcode Produk - CV. Herbal At-Tiin</title>
    <style>
        @page { size: A4; margin: 15mm 15mm 25mm 15mm; }
        body {
            font-family: 'Georgia', serif;
            text-align: center;
            background: #ffffff;
            color: #1a1a1a;
            margin: 0;
        }
        footer {
            position: fixed;
            bottom: -15px; left: 0px; right: 0px;
            height: 30px; padding-top: 10px;
            border-top: 1px solid #d4af37;
            font-size: 10px; color: #666;
            text-align: center; font-family: 'Arial', sans-serif;
        }

        .header { margin-bottom: 30px; }
        .logo { width: 110px; height: auto; margin-bottom: 12px; }
        .header h2 { font-size: 24px; font-weight: 300; margin: 0 0 5px 0; letter-spacing: 5px; text-transform: uppercase; color: #065f46; }
        .header p { font-size: 11px; color: #d4af37; text-transform: uppercase; letter-spacing: 2px; margin: 0; }
        .grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px; /* Jarak rapi antar kotak */
            table-layout: fixed; /* Memaksa 3 kolom memiliki lebar yang sama persis */
        }
        .grid-td {
            width: 33.33%;
            vertical-align: top;
        }
        .barcode-card {
            padding: 12px 8px;
            border: 1px solid #e2e8f0;
            border-top: 4px solid #065f46;
            border-bottom: 2px solid #d4af37;
            border-radius: 6px;
            background-color: #fafafa;
            page-break-inside: avoid;
        }
        .prod-name { font-size: 10px; font-weight: bold; margin-bottom: 10px; height: 28px; overflow: hidden; text-transform: uppercase; color: #065f46; line-height: 1.4; font-family: 'Arial', sans-serif; }
        .barcode-box { margin: 5px auto; display: block; background: #fff; padding: 6px; border: 1px solid #eee; border-radius: 4px; }

        .barcode-box img { max-width: 100%; height: 38px; }

        .sku-text { font-size: 11px; color: #d4af37; margin-top: 8px; letter-spacing: 2.5px; font-family: 'Courier New', Courier, monospace; font-weight: bold; }
    </style>
</head>
<body>

    <footer>
        Dicetak secara resmi oleh Sistem Inventaris CV. Herbal At-Tiin &bull; {{ date('d M Y H:i') }}
    </footer>

    <main>
        <div class="header">
            @php
                $logoPath = public_path('images/logo-attiin.png');
                $logoData = '';
                if (file_exists($logoPath)) {
                    $logoData = base64_encode(file_get_contents($logoPath));
                }
            @endphp
            @if($logoData)
                <img src="data:image/png;base64,{{ $logoData }}" class="logo" alt="Logo At-Tiin">
            @endif
            <h2>Label Barcode</h2>
        </div>

        <table class="grid-table">
            @php
                $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
            @endphp
            @foreach($products->chunk(3) as $row)
                <tr>
                    @foreach($row as $product)
                        <td class="grid-td">
                            <div class="barcode-card">
                                <div class="prod-name">{{ Str::limit($product->nama, 35) }}</div>
                                <div class="barcode-box">
                                    @php
                                        // Skala diturunkan sedikit dari 1.5 menjadi 1.2 agar aman untuk SKU yang sangat panjang
                                        $barcodeData = base64_encode($generator->getBarcode($product->sku, $generator::TYPE_CODE_128, 1.2, 38));
                                    @endphp
                                    <img src="data:image/png;base64,{{ $barcodeData }}" alt="Barcode {{ $product->sku }}">
                                </div>
                                <div class="sku-text">{{ $product->sku }}</div>
                            </div>
                        </td>
                    @endforeach
                    @for($i = $row->count(); $i < 3; $i++)
                        <td class="grid-td"></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    </main>

</body>
</html>
