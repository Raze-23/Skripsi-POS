<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Transaksi #{{ $transaction->id }}</title>
    <style>
        @include('pdf.styles.cetak-nota')
    </style>
</head>
<body>

    @php
        $groupedItems = $transaction->details
            ->groupBy(fn ($item) => $item->productBatch->product->id ?? $item->productBatch_id ?? $item->id)
            ->map(function ($group) {
                $first = $group->first();
                return (object) [
                    'nama'     => $first->productBatch->product->nama ?? 'Produk',
                    'harga'    => $first->productBatch->product->harga_jual
                                    ?? ($first->qty > 0 ? $first->subtotal / $first->qty : 0),
                    'qty'      => $group->sum('qty'),
                    'subtotal' => $group->sum('subtotal'),
                ];
            });
        $logoPath = public_path('images/logo-attiin.png');
        if (!file_exists($logoPath)) {
            $logoPath = base_path('../public_html/images/logo-attiin.png');
        }
        if (!file_exists($logoPath) && isset($_SERVER['DOCUMENT_ROOT'])) {
            $logoPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/images/logo-attiin.png';
        }
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
    @endphp

    <div class="ticket">
        <div class="text-center">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" style="width: 80px; margin-bottom: 8px; filter: grayscale(100%);">
            @endif
            <div class="store-name">CV. Herbal At-Tiin</div>
            <div class="store-desc">
                Jl. Kutilang, Kerten, Kec. Laweyan<br>
                Kota Surakarta<br>
                Telp: +62-882-9847-1715<br>
                IG: @attiin.herbal
            </div>
        </div>

        <div class="divider"></div>

        <div class="meta-info">
            <span>Dicetak pada:</span>
            <span>{{ $transaction->created_at->format('d/m/y H:i') }}</span>
        </div>

        <div class="divider"></div>

        <table>
            @foreach($groupedItems as $item)
            <tr class="item-row">
                <td colspan="2">
                    <span class="item-name">{{ $item->nama }}</span>
                </td>
            </tr>
            <tr>
                <td class="item-qty-price">
                    {{ $item->qty }} x {{ number_format($item->harga, 0, ',', '.') }}
                </td>
                <td class="text-right item-subtotal">
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
                <span>Total</span>
                <span>Rp {{ number_format($transaction->total_harga, 0, ',', '.') }}</span>
            </div>

            <div class="total-row">
                <span>Tunai</span>
                <span>{{ number_format($transaction->nominal_bayar, 0, ',', '.') }}</span>
            </div>
            <div class="total-row">
                <span>Kembali</span>
                <span>{{ number_format($transaction->nominal_kembalian, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <div class="footer">
            <div class="footer-quote">Dari alam, diracik dengan sepenuh hati untuk kesehatan Anda. Terima kasih telah mempercayakan perjalanan sehat Anda bersama kami.</div>
        </div>
    </div>

    <script>
        @include('pdf.scripts.cetak-nota')
    </script>
</body>
</html>
