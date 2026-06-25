<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    let html5QrCode = null;
    let scannerRunning = false;
    let scanCooldown = false;
    let lastScannedCode = '';
    let lastScanTime = 0;
    let isStarting = false;
    let isFromScanner = false;

    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    let audioCtx = null;

    function playBeep(frequency = 1200, duration = 180) {
        try {
            if (!audioCtx) audioCtx = new AudioCtx();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const o = audioCtx.createOscillator();
            const g = audioCtx.createGain();
            o.connect(g);
            g.connect(audioCtx.destination);
            o.type = 'square';
            o.frequency.setValueAtTime(frequency, audioCtx.currentTime);
            g.gain.setValueAtTime(0.3, audioCtx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration / 1000);
            o.start(audioCtx.currentTime);
            o.stop(audioCtx.currentTime + duration / 1000);
        } catch (e) {}
    }

    function playErrorBuzz() {
        try {
            if (!audioCtx) audioCtx = new AudioCtx();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const o = audioCtx.createOscillator();
            const g = audioCtx.createGain();
            o.connect(g);
            g.connect(audioCtx.destination);
            o.type = 'sawtooth';
            o.frequency.setValueAtTime(200, audioCtx.currentTime);
            g.gain.setValueAtTime(0.3, audioCtx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
            o.start(audioCtx.currentTime);
            o.stop(audioCtx.currentTime + 0.4);
        } catch (e) {}
    }

    function flashScanFeedback(type = 'success') {
        const overlay = document.getElementById('scan-flash-overlay');
        if (!overlay) return;
        overlay.style.backgroundColor = type === 'error' ? '#ef4444' : '#ffffff';
        overlay.style.opacity = type === 'error' ? '0.6' : '0.85';
        setTimeout(() => {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.style.backgroundColor = '', 200);
        }, type === 'error' ? 400 : 150);
    }

    function openBarcodeScanner() {
        if (isStarting || scannerRunning) return;
        const modal = document.getElementById('barcode-scanner-modal');
        if (modal) modal.classList.add('open');
        isStarting = true;

        setTimeout(() => {
            try {
                const readerEl = document.getElementById("barcode-reader");
                const errorBox = document.getElementById("scanner-error-box");
                if (!readerEl || !errorBox) { isStarting = false; return; }

                readerEl.style.display = 'block';
                errorBox.style.display = 'none';
                readerEl.innerHTML = '';

                html5QrCode = new Html5Qrcode("barcode-reader", {
                    formatsToSupport: [
                        Html5QrcodeSupportedFormats.QR_CODE,
                        Html5QrcodeSupportedFormats.EAN_13,
                        Html5QrcodeSupportedFormats.CODE_128
                    ],
                    verbose: false,
                    useBarCodeDetectorIfSupported: true
                });

                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 25, qrbox: { width: 280, height: 280 }, aspectRatio: 1.0, disableFlip: false },
                    (decodedText) => {
                        const now = Date.now();
                        if (scanCooldown) {
                            if (decodedText === lastScannedCode && (now - lastScanTime) < 2000) return;
                            if (decodedText !== lastScannedCode && (now - lastScanTime) < 800) return;
                        }
                        scanCooldown = true;
                        lastScannedCode = decodedText;
                        lastScanTime = now;
                        isFromScanner = true;
                        if (navigator.vibrate) navigator.vibrate(50);
                        Livewire.dispatch('process-barcode', { sku: decodedText });
                        setTimeout(() => { scanCooldown = false; isFromScanner = false; }, 1500);
                    },
                    () => {}
                ).then(() => {
                    scannerRunning = true;
                    isStarting = false;
                }).catch((err) => {
                    isStarting = false;
                    let pesanError = "Kamera sedang digunakan aplikasi lain atau error sistem.";
                    let isHttpsError = window.location.protocol !== 'https:' && window.location.hostname !== 'localhost';
                    if (isHttpsError) {
                        pesanError = "Browser memblokir kamera!<br><br>Gunakan jaringan <b>localhost</b> atau koneksi <b>HTTPS</b>.";
                    } else if (err.name === "NotAllowedError" || String(err).includes("permission")) {
                        pesanError = "Izin kamera ditolak!<br><br>Klik <b>ikon 🔒 Gembok</b> di sebelah URL di atas, pilih <b>Izinkan Kamera (Allow)</b>, lalu Refresh (F5).";
                    } else if (err.name === "NotFoundError") {
                        pesanError = "Tidak ada perangkat kamera yang terdeteksi di perangkat Anda.";
                    }
                    readerEl.style.display = 'none';
                    document.getElementById('scanner-error-msg').innerHTML = pesanError;
                    errorBox.style.display = 'flex';
                });
            } catch (e) {
                isStarting = false;
                closeBarcodeScanner();
            }
        }, 300);
    }

    function closeBarcodeScanner() {
        const modal = document.getElementById('barcode-scanner-modal');
        if (modal) modal.classList.remove('open');
        scanCooldown = false;
        lastScannedCode = '';
        isStarting = false;
        isFromScanner = false;
        if (html5QrCode && scannerRunning) {
            html5QrCode.stop().then(() => {
                scannerRunning = false;
                html5QrCode.clear();
                html5QrCode = null;
            }).catch(() => { scannerRunning = false; html5QrCode = null; });
        } else {
            html5QrCode = null;
            scannerRunning = false;
        }
    }

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
        toastTimer = setTimeout(() => { toast.classList.remove('show'); }, 3200);
    }

    document.addEventListener('livewire:initialized', () => {
        Livewire.on('play-beep', () => {
            if (!isFromScanner) return;
            playBeep(1200, 150);
            flashScanFeedback('success');
        });

        Livewire.on('play-error-beep', () => {
            if (!isFromScanner) return;
            playErrorBuzz();
            flashScanFeedback('error');
            if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
        });

        Livewire.on('product-added', (data) => {
            showToast(`${data[0]?.name ?? 'Produk'} masuk keranjang`, 'success');
        });

        Livewire.on('cart-cleared', () => { showToast('Keranjang dikosongkan', 'warn'); });
        Livewire.on('transaction-success', () => { showToast('Pembayaran berhasil', 'success'); });

        Livewire.on('stock-warning', (data) => {
            showToast('Gagal: ' + (data[0]?.name || 'Stok Habis'), 'error');
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
        iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;visibility:hidden;';
        document.body.appendChild(iframe);
        iframe.addEventListener('load', () => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => { Livewire.dispatch('close-receipt'); }, 500);
            } catch (e) {
                window.open(urlNota, '_blank', 'width=420,height=600');
                Livewire.dispatch('close-receipt');
            }
        });
    }

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc') Livewire.dispatch('close-receipt');
    });
</script>
