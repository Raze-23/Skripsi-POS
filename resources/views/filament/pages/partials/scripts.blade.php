<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<div id="pos-toast" role="alert" aria-live="assertive">
    <svg class="toast-icon" id="toast-icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"></svg>
    <span id="toast-msg"></span>
</div>

{{-- Overlay flash saat scan terdeteksi --}}
<div id="scan-flash-overlay"></div>

<script>
    let html5QrCode = null;
    let scannerRunning = false;
    let scanCooldown = false;
    let lastScannedCode = '';
    let lastScanTime = 0;

    // ======= Web Audio API Beep =======
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    let audioCtx = null;

    function playBeep(frequency = 1200, duration = 180) {
        try {
            if (!audioCtx) audioCtx = new AudioCtx();
            // Resume jika suspended (kebijakan autoplay browser)
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            oscillator.type = 'square';
            oscillator.frequency.setValueAtTime(frequency, audioCtx.currentTime);
            gainNode.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration / 1000);
            oscillator.start(audioCtx.currentTime);
            oscillator.stop(audioCtx.currentTime + duration / 1000);
        } catch (e) {
            console.warn('Beep audio failed:', e);
        }
    }

    // ======= Visual Flash saat scan terdeteksi =======
    function flashScanFeedback() {
        const overlay = document.getElementById('scan-flash-overlay');
        if (!overlay) return;
        overlay.classList.add('active');
        setTimeout(() => overlay.classList.remove('active'), 350);
    }

    // ======= Scanner Functions =======
    function openBarcodeScanner() {
        const modal = document.getElementById('barcode-scanner-modal');
        modal.classList.add('open');

        setTimeout(() => {
            if (scannerRunning) return;
            try {
                const readerEl = document.getElementById("barcode-reader");
                if (!readerEl) return;
                // Bersihkan isi sebelumnya agar tidak terjadi duplikasi
                readerEl.innerHTML = '';

                // formatsToSupport di CONSTRUCTOR (wajib untuk html5-qrcode v2.3.8)
                html5QrCode = new Html5Qrcode("barcode-reader", {
                    formatsToSupport: [
                        Html5QrcodeSupportedFormats.QR_CODE,
                        Html5QrcodeSupportedFormats.EAN_13,
                        Html5QrcodeSupportedFormats.EAN_8,
                        Html5QrcodeSupportedFormats.CODE_128,
                        Html5QrcodeSupportedFormats.CODE_39,
                    ],
                    verbose: false,
                    // Gunakan native BarcodeDetector jika tersedia (JAUH lebih cepat)
                    useBarCodeDetectorIfSupported: true
                });

                html5QrCode.start(
                    { facingMode: "environment" },
                    {
                        fps: 25,                          // ← naik dari 15, scan lebih sering per detik
                        qrbox: { width: 280, height: 280 }, // ← area scan lebih besar
                        aspectRatio: 1.0,
                        disableFlip: false,
                        experimentalFeatures: {
                            useBarCodeDetectorIfSupported: true  // Native API = lebih cepat
                        }
                    },
                    (decodedText) => {
                        const now = Date.now();

                        // Smart cooldown: kode SAMA = 2 detik, kode BEDA = 800ms
                        if (scanCooldown) {
                            if (decodedText === lastScannedCode && (now - lastScanTime) < 2000) return;
                            if (decodedText !== lastScannedCode && (now - lastScanTime) < 800) return;
                        }

                        scanCooldown = true;
                        lastScannedCode = decodedText;
                        lastScanTime = now;

                        console.log('📷 QR Terdeteksi:', decodedText);

                        // 1. Instant visual feedback — flash hijau + getar
                        flashScanFeedback();
                        if (navigator.vibrate) navigator.vibrate(100);

                        // 2. Quick beep instan (feedback deteksi, bukan konfirmasi)
                        playBeep(800, 80);

                        // 3. Kirim ke Livewire
                        Livewire.dispatch('process-barcode', { sku: decodedText });

                        // 4. Reset cooldown
                        setTimeout(() => {
                            scanCooldown = false;
                        }, 1200);
                    },
                    () => {} // per-frame error, diabaikan
                ).then(() => {
                    scannerRunning = true;
                    console.log('🎥 Scanner aktif — FPS: 25');
                }).catch((err) => {
                    console.error("Camera error:", err);
                    document.getElementById('barcode-reader').innerHTML =
                        '<p style="color:#ef4444; text-align:center; padding:30px 10px; font-size:12px;">' +
                        'Kamera diblokir oleh sistem.<br>Gunakan Localhost atau koneksi HTTPS!</p>';
                });
            } catch (e) {
                console.error("Scanner init error:", e);
                closeBarcodeScanner();
            }
        }, 300);
    }

    function closeBarcodeScanner() {
        document.getElementById('barcode-scanner-modal').classList.remove('open');
        scanCooldown = false;
        lastScannedCode = '';
        if (html5QrCode && scannerRunning) {
            html5QrCode.stop()
                .then(() => {
                    scannerRunning = false;
                    html5QrCode.clear();
                    html5QrCode = null;
                })
                .catch(err => {
                    console.warn(err);
                    scannerRunning = false;
                    html5QrCode = null;
                });
        } else {
            html5QrCode = null;
            scannerRunning = false;
        }
    }

    // ======= Toast Notification =======
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

    // ======= Livewire Event Listeners =======
    document.addEventListener('livewire:initialized', () => {
        // Beep SUKSES — produk berhasil masuk keranjang
        Livewire.on('play-beep', () => {
            console.log('✅ Produk masuk keranjang');
            playBeep(1400, 150);
            // Double beep untuk konfirmasi
            setTimeout(() => playBeep(1800, 100), 160);
        });

        // Beep ERROR — stok habis / produk tidak ditemukan
        Livewire.on('play-error-beep', () => {
            console.log('❌ Scan gagal (stok habis/tidak ditemukan)');
            playBeep(300, 400);
        });

        Livewire.on('product-added', (data) => {
            showToast((data[0]?.name ?? 'Produk') + ' ditambahkan ke keranjang', 'success');
        });

        Livewire.on('cart-cleared', () => {
            showToast('Keranjang dikosongkan', 'warn');
        });

        Livewire.on('transaction-success', () => {
            showToast('Pembayaran selesai.', 'success');
        });

        Livewire.on('stock-warning', (data) => {
            let productName = data[0]?.name ? ' ' + data[0].name : '';
            showToast('⚠️ Stok' + productName + ' tidak mencukupi!', 'error');
        });
    });

    // ======= Cetak Nota =======
    function jalankanCetakNota(transactionId) {
        if (!transactionId) return;

        const urlNota = "{{ route('print.nota', ['id' => '__ID__']) }}".replace('__ID__', transactionId);

        const iframeLama = document.getElementById('frame-cetak-nota');
        if (iframeLama) iframeLama.remove();

        const iframe = document.createElement('iframe');
        iframe.id = 'frame-cetak-nota';
        iframe.src = urlNota;
        iframe.style.cssText = 'position:fixed; top:-9999px; left:-9999px; width:1px; height:1px; border:none; visibility:hidden;';

        document.body.appendChild(iframe);

        iframe.addEventListener('load', () => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => {
                    Livewire.dispatch('close-receipt');
                }, 500);
            } catch (e) {
                window.open(urlNota, '_blank', 'width=420,height=600');
                Livewire.dispatch('close-receipt');
            }
        });
    }

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            Livewire.dispatch('close-receipt');
        }
    });
</script>
