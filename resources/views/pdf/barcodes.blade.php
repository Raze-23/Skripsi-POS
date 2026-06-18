<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Lembar Barcode Produk</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            text-align: center;
            background: #ffffff;
            color: #333;
            margin: 0;
        }

        /* HEADER STYLING */
        .header {
            margin-bottom: 30px;
        }

        .header h2 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 5px 0;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #065f46;
            /* Emerald Green */
        }

        .header p {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .divider {
            width: 50px;
            height: 2px;
            background-color: #d4af37;
            margin: 15px auto;
        }

        /* Gold accent */

        /* GRID & CARD STYLING */
        .grid {
            width: 100%;
            display: block;
        }

        .barcode-card {
            float: left;
            width: 31%;
            /* Disesuaikan agar presisi 3 kolom */
            margin: 1.1%;
            padding: 15px 10px;
            border: 1px dashed #ccc;
            border-radius: 6px;
            background-color: #fafafa;
            /* Latar belakang super tipis */
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        .prod-name {
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 12px;
            height: 30px;
            overflow: hidden;
            text-transform: uppercase;
            color: #111;
            line-height: 1.3;
        }

        .barcode-box {
            margin: 5px auto;
            display: block;
            background: #fff;
            padding: 5px;
            border-radius: 4px;
        }

        .barcode-box img {
            max-width: 100%;
            height: 38px;
        }

        .sku-text {
            font-size: 11px;
            color: #222;
            margin-top: 8px;
            letter-spacing: 2.5px;
            font-family: 'Courier New', Courier, monospace;
            /* Font ala mesin kasir */
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -15px;
            left: 0px;
            right: 0px;
            height: 30px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 10px;
            color: #999;
            text-align: center;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Barcode Produk</h2>
        <p>Inventaris CV. Herbal At-Tiin</p>
        <div class="divider"></div>
    </div>

    <div class="grid clearfix">
        @php
            $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
        @endphp

        @foreach ($products as $product)
            <div class="barcode-card">
                <div class="prod-name">{{ Str::limit($product->nama, 35) }}</div>
                <div class="barcode-box">
                    @php
                        $barcodeData = base64_encode(
                            $generator->getBarcode($product->sku, $generator::TYPE_CODE_128, 1.5, 38),
                        );
                    @endphp
                    <img src="data:image/png;base64,{{ $barcodeData }}" alt="Barcode {{ $product->sku }}">
                </div>
                <div class="sku-text">{{ $product->sku }}</div>
            </div>
        @endforeach
    </div>

    <div class="footer">
        Dicetak secara resmi oleh Sistem Inventaris CV. Herbal At-Tiin pada {{ date('d F Y') }}
    </div>

</body>

</html>
