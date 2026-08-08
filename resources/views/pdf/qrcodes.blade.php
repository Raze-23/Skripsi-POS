<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar QR Code Produk - CV. Herbal At-Tiin</title>
    <style>
        @include('pdf.styles.qrcodes')
    </style>
</head>
<body>

    <footer>
        Dicetak oleh Sistem POS CV. Herbal At-Tiin pada {{ now()->translatedFormat('d F Y, H:i') }}
    </footer>

    <main>
        <div class="header">
            @php
                $logoPath = public_path('images/logo-attiin.png');
                $logoData = '';
                if (file_exists($logoPath)) {
                    $logoData = base64_encode(file_get_contents($logoPath));
                }

                $batches = $batches->sortBy(function ($b) {
                    return $b->tanggal_kedaluwarsa
                        ? \Carbon\Carbon::parse($b->tanggal_kedaluwarsa)->timestamp
                        : PHP_INT_MAX;
                })->values();
            @endphp
            
            @if($logoData)
                <img src="data:image/png;base64,{{ $logoData }}" class="logo" alt="Logo At-Tiin">
            @endif
            <h2>Label Batch Produk</h2>
        </div>

        <table class="grid-table">
            @foreach($batches->chunk(3) as $row)
                <tr>
                    @foreach($row as $batch)
                        <td class="grid-td">
                            <div class="qrcode-card">
                                <div class="prod-name">{{ Str::limit($batch->product->nama, 35) }}</div>
                                
                                <div class="qrcode-box">
                                    @php
                                        $qrCodeData = base64_encode(QrCode::size(90)->generate($batch->batch_code));
                                    @endphp
                                    <img src="data:image/svg+xml;base64,{{ $qrCodeData }}" alt="QR Code">
                                </div>
                                
                                <div class="sku-text">{{ $batch->batch_code }}</div>
                                
                                <div class="expiry-box">
                                    <span class="expiry-label">Batas Kedaluwarsa</span>
                                    @if($batch->tanggal_kedaluwarsa)
                                        <span class="expiry-date">{{ \Carbon\Carbon::parse($batch->tanggal_kedaluwarsa)->translatedFormat('d M Y') }}</span>
                                    @else
                                        <span class="expiry-date expiry-safe">NON-EXPIRED</span>
                                    @endif
                                </div>

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