<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Transaksi #{{ $transaction->id }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            width: 58mm;
            max-width: 58mm;
            margin: 0 auto;
            color: #000;
            background: #fff;
            font-size: 11px;
            line-height: 1.4;
        }

        .ticket {
            width: 48mm;
            max-width: 48mm;
            margin: 0 auto;
            padding-top: 5mm;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }


        .store-name {
            font-size: 15px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .store-desc {
            font-size: 10px;
            color: #222;
            line-height: 1.2;
        }


        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .divider-solid {
            border-top: 1.5px solid #000;
            margin: 6px 0;
        }


        .meta-info {
            font-size: 10.5px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
            table-layout: fixed;
        }
        td {
            vertical-align: top;
            padding: 2px 0;
        }
        .item-name {
            display: block;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 2px;
            word-wrap: break-word;
        }
        .item-qty-price {
            font-size: 10px;
            color: #111;
        }


        .total-area { margin-top: 5px; }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 3px;
        }
        .total-row.grand-total {
            font-size: 14px;
            font-weight: 900;
            margin: 5px 0;
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
        }

        .footer {
            margin-top: 10px;
            text-align: center;
            padding-bottom: 15px;
        }

        .footer-greeting {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .footer-quote {
            font-size: 10.5px;
            font-style: italic;
            margin-bottom: 6px;
            line-height: 1.3;
        }


        ::-webkit-scrollbar { display: none; }
    </style>
</head>
<body>

    <div class="ticket">
        <div class="text-center">
            <div class="store-name">CV. Herbal At-Tiin</div>
            <div class="store-desc">Jl. Kutilang, Kerten, Kec. Laweyan<br>Kota Surakarta</div>
        </div>

        <div class="divider"></div>

        <div class="meta-info">
            <span>Tgl: {{ $transaction->created_at->format('d/m/Y H:i') }}</span>
        </div>

        <div class="divider-solid"></div>

        <table>
            @foreach($transaction->details as $item)
            <tr>
                <td colspan="2">
                    <span class="item-name">{{ $item->productBatch->product->nama }}</span>
                </td>
            </tr>
            <tr>
                <td class="item-qty-price">
                    {{ $item->qty }} x {{ number_format($item->productBatch->product->harga_jual ?? ($item->subtotal / $item->qty), 0, ',', '.') }}
                </td>
                <td class="text-right font-bold">
                    {{ number_format($item->subtotal, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </table>

        <div class="divider"></div>

        <div class="total-area">
            @if($transaction->diskon_persen > 0)
            <div class="total-row">
                <span>Subtotal</span>
                <span>{{ number_format($transaction->details->sum('subtotal'), 0, ',', '.') }}</span>
            </div>
            <div class="total-row">
                <span>Diskon ({{ $transaction->diskon_persen }}%)</span>
                <span>-{{ number_format(($transaction->details->sum('subtotal') * $transaction->diskon_persen) / 100, 0, ',', '.') }}</span>
            </div>
            @endif

            <div class="total-row grand-total">
                <span>TOTAL</span>
                <span>Rp {{ number_format($transaction->total_harga, 0, ',', '.') }}</span>
            </div>

            <div class="total-row">
                <span>TUNAI</span>
                <span>{{ number_format($transaction->nominal_bayar, 0, ',', '.') }}</span>
            </div>
            <div class="total-row">
                <span>KEMBALI</span>
                <span>{{ number_format($transaction->nominal_kembalian, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="divider-solid"></div>

        <div class="footer">
            <div class="footer-greeting">Terima Kasih atas Kepercayaannya</div>
            <div class="footer-quote">Terima kasih telah berbelanja di tempat kami. Semoga produk ini bermanfaat, membantu pemulihan Anda, dan membawa berkah.</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
