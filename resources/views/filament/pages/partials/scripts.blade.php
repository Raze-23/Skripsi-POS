<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
{{-- AUDIO BEEP ELEMEN --}}

<audio id="beep-sound" src="data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU"></audio>

{{-- Toast notification --}}
<div id="pos-toast" role="alert" aria-live="assertive">
    <svg class="toast-icon" id="toast-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        stroke-width="2.5"></svg>
    <span id="toast-msg"></span>
</div>

<script>
    let html5QrCode = null;
    let scannerRunning = false;

    function openBarcodeScanner() {
        document.getElementById('barcode-scanner-modal').classList.add('open');
        setTimeout(() => {
            if (scannerRunning) return;
            try {
                html5QrCode = new Html5Qrcode("barcode-reader");
                html5QrCode.start({
                            facingMode: "environment"
                        }, {
                            fps: 10,
                            qrbox: {
                                width: 220,
                                height: 120
                            },
                            aspectRatio: 1.2,
                            formatsToSupport: [
                                Html5QrcodeSupportedFormats.EAN_13,
                                Html5QrcodeSupportedFormats.CODE_128,
                                Html5QrcodeSupportedFormats.CODE_39,
                                Html5QrcodeSupportedFormats.UPC_A,
                                Html5QrcodeSupportedFormats.QR_CODE,
                            ]
                        },
                        (decodedText) => {
                            @this.set('searchSku', decodedText);
                            @this.call('scanBarcode');
                            if (navigator.vibrate) navigator.vibrate(120);
                            closeBarcodeScanner();
                        },
                        () => {}
                    ).then(() => {
                        scannerRunning = true;
                    })
                    .catch((err) => {
                        console.error("Camera error:", err);
                        closeBarcodeScanner();
                        showToast('Gagal akses kamera. Periksa izin browser.', 'error');
                    });
            } catch (e) {
                console.error(e);
                closeBarcodeScanner();
            }
        }, 200);
    }

    function closeBarcodeScanner() {
        document.getElementById('barcode-scanner-modal').classList.remove('open');
        if (html5QrCode && scannerRunning) {
            html5QrCode.stop()
                .then(() => {
                    scannerRunning = false;
                    html5QrCode.clear();
                    html5QrCode = null;
                })
                .catch(err => console.warn(err));
        } else {
            html5QrCode = null;
            scannerRunning = false;
        }
    }

    // Toast notification
    let toastTimer = null;

    function showToast(msg, type = 'success') {
        const toast = document.getElementById('pos-toast');
        const msgEl = document.getElementById('toast-msg');
        const iconEl = document.getElementById('toast-icon-svg');

        const icons = {
            success: '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>',
            error: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>',
            warn: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>',
        };

        toast.className = 'show ' + type;
        msgEl.textContent = msg;
        iconEl.innerHTML = icons[type] || icons.success;

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.remove('show');
        }, 3200);
    }

    //  Livewire event listeners (BEEP SOUND)
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('play-beep', () => {
            const beepSound = document.getElementById('beep-sound');
            beepSound.currentTime = 0; // Reset agar bisa diputar berulang cepat
            beepSound.play().catch((err) => {
                console.log("Audio autoplay dicegah browser. Harap klik sekali pada halaman.");
            });
        });

        // 1. Lebih singkat: "Paracetamol ditambahkan."
        Livewire.on('product-added', (data) => {
            showToast((data[0]?.name ?? 'Produk') + ' ditambahkan.', 'success');
        });

        // 2. Lebih netral: "Keranjang dibersihkan."
        Livewire.on('cart-cleared', () => {
            showToast('Keranjang dibersihkan.', 'warn');
        });

        // 3. Lebih profesional tanpa tanda seru: "Pembayaran selesai."
        Livewire.on('transaction-success', () => {
            showToast('Pembayaran selesai.', 'success');
        });

        // 4. Lebih informatif & diubah menjadi warna merah (error): "Stok Paracetamol tidak mencukupi."
        Livewire.on('stock-warning', (data) => {
            let productName = data[0]?.name ? ' ' + data[0].name : '';
            showToast('Stok' + productName + ' tidak mencukupi.', 'error');
        });
    });
    
    function jalankanCetakNota(transactionId) {
        if (!transactionId) return;

        const urlNota = "{{ route('print.nota', ['id' => '__ID__']) }}".replace('__ID__', transactionId);

        const iframeLama = document.getElementById('frame-cetak-nota');
        if (iframeLama) iframeLama.remove();

        const iframe = document.createElement('iframe');
        iframe.id = 'frame-cetak-nota';
        iframe.src = urlNota;
        // Menggunakan visibility:hidden seringkali lebih ramah untuk fungsi print browser
        iframe.style.cssText =
            'position:fixed; top:-9999px; left:-9999px; width:1px; height:1px; border:none; visibility:hidden;';

        document.body.appendChild(iframe);

        iframe.addEventListener('load', () => {
            try {
                // 1. Fokuskan ke dalam iframe
                iframe.contentWindow.focus();

                // 2. PERBAIKAN: Paksa eksekusi perintah cetak dari halaman induk
                iframe.contentWindow.print();

                // 3. PERBAIKAN: Beri jeda 0.5 detik sebelum Livewire merender ulang (menutup modal)
                // agar sistem peramban web memiliki waktu untuk memunculkan dialog print.
                setTimeout(() => {
                    @this.call('closeReceiptModal');
                }, 500);

            } catch (e) {
                console.warn('Iframe print terblokir browser, membuka jendela pop-up cadangan:', e);
                window.open(urlNota, '_blank', 'width=420,height=600');
                @this.call('closeReceiptModal');
            }
        });
    }
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            @this.call('closeReceiptModal');
        }
    });
</script>
