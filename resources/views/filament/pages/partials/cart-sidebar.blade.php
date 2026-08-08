<div class="pos-right">

    <div class="cart-header">
        <div class="cart-header-left">
            <div class="cart-icon-wrap">
                <svg style="width:14px;height:14px;color:#059669;" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M2.25 2.25a.75.75 0 0 0 0 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 0 0-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 0 0 0-1.5H5.378A2.25 2.25 0 0 1 7.5 15h11.218a.75.75 0 0 0 .674-.421 60.358 60.358 0 0 0 2.96-7.228.75.75 0 0 0-.525-.965A60.864 60.864 0 0 0 5.68 4.509l-.232-.867A1.875 1.875 0 0 0 3.636 2.25H2.25ZM3.75 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM16.5 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" />
                </svg>
            </div>
            <span class="cart-title">Keranjang</span>
            <span class="cart-count">({{ count($cart) }})</span>
        </div>
        @if (!empty($cart))
            <button wire:click="resetCart" class="cart-clear-btn" type="button">Kosongkan</button>
        @endif
    </div>

    <div class="cart-items">
        @if (empty($cart))
            <div class="cart-empty">
                <svg style="width:36px;height:36px;opacity:.2;" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
                </svg>
                <span style="font-size:12px;font-weight:600;color:#9ca3af;">Keranjang kosong</span>
                <span style="font-size:11px;color:#d1d5db;">Klik produk untuk menambah</span>
            </div>
        @else
            @foreach ($cart as $id => $item)
                <div class="cart-item" wire:key="cart-item-{{ $id }}">
                    <div class="cart-item-info">
                        <div class="cart-item-name">{{ $item['nama'] }}</div>
                        <div class="text-[10px] text-emerald-600 font-mono bg-emerald-50 inline-block px-1 rounded mt-0.5 border border-emerald-200">Lot: {{ $item['batch_code'] }}</div>
                        <div class="cart-item-price">@ Rp {{ number_format($item['harga'], 0, ',', '.') }}</div>
                    </div>
                    <div class="qty-wrap">
                        <button wire:click="updateQty({{ $id }}, {{ $item['qty'] - 1 }})" class="qty-btn"
                            type="button">−</button>
                        <input 
                            type="number" 
                            value="{{ $item['qty'] }}" 
                            wire:change="updateQty({{ $id }}, $event.target.value)" 
                            wire:key="input-qty-{{ $id }}-{{ $item['qty'] }}"
                            class="qty-val qty-input-manual" 
                            min="1" 
                        />
                        <button wire:click="updateQty({{ $id }}, {{ $item['qty'] + 1 }})" class="qty-btn"
                            type="button">+</button>
                    </div>
                    <div class="cart-item-right">
                        <span class="cart-item-sub">{{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                        <button wire:click="removeItem({{ $id }})" class="cart-remove-btn"
                            type="button">Hapus</button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="cart-footer">

        <div class="pay-row">
            <span class="pay-label">Subtotal</span>
            <span class="pay-val">Rp {{ number_format($this->totalHarga ?? 0, 0, ',', '.') }}</span>
        </div>

        <div class="pay-row">
            <span class="pay-label">Diskon (%)</span>
            <input type="number" wire:model.live="diskon" class="diskon-input" min="0" max="100"
                placeholder="0" />
        </div>

        <hr class="pay-divider" />

        <div class="total-row">
            <span class="total-label">Total Tagihan</span>
            <span class="total-val">Rp {{ number_format($this->totalHarga ?? 0, 0, ',', '.') }}</span>
        </div>

        @php
            $uangBayar = (int) ($bayar ?? 0) ?: 0;
            $hasCart = ! empty($cart);
            $totalTagihan = (int) ($this->total_harga ?? 0);
            $kembalian = (int) ($this->kembalian ?? 0);
            $showStatus = $hasCart && $uangBayar > 0;
            $isShortage = $showStatus && $kembalian < 0;
            $isExact = $showStatus && $kembalian === 0;
            $isSurplus = $showStatus && $kembalian > 0;
            $isDisabled = ! $hasCart || $uangBayar < $totalTagihan;
            $disabledReason = match (true) {
                default => null,
            };

            $cashRing = $isShortage ? '#fca5a5' : ($showStatus ? '#6ee7b7' : null);
        @endphp

        <div>
            <div style="font-size:11px;font-weight:600;color:#6b7280;margin-bottom:5px;">Uang Diterima</div>
            <div class="cash-wrap transition-shadow duration-150"
                style="{{ $cashRing ? 'box-shadow: 0 0 0 1.5px ' . $cashRing . ' inset; border-radius: 8px;' : '' }}">
                <span class="cash-prefix">Rp</span>
                <input type="number" wire:model.live.debounce.500ms="bayar" class="cash-input" placeholder="0"
                    min="0" />
            </div>
        </div>

        <div role="status" aria-live="polite">
            @if ($isShortage)
                <div class="change-badge shortage flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 transition-colors">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                            <path d="M12 9v4" />
                            <path d="M12 17h.01" />
                            <path d="M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78A1.5 1.5 0 0 0 22.18 18L13.71 3.86a1.5 1.5 0 0 0-2.42 0Z" />
                        </svg>
                    </span>
                    <div class="flex flex-1 items-center justify-between">
                        <span class="text-[11px] font-semibold text-red-500">Kurang bayar</span>
                        <span class="text-sm font-bold text-red-600">Rp {{ number_format(abs($this->kembalian), 0, ',', '.') }}</span>
                    </div>
                </div>
            @elseif ($isExact)
                <div class="change-badge exact flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 transition-colors">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                            <circle cx="12" cy="12" r="9" />
                            <path d="m8.5 12.5 2.5 2.5 4.5-5" />
                        </svg>
                    </span>
                    <div class="flex flex-1 items-center justify-between">
                        <span class="text-[11px] font-semibold text-emerald-600">Pas, tanpa kembalian</span>
                        <span class="text-sm font-bold text-emerald-700">Rp 0</span>
                    </div>
                </div>
            @elseif ($isSurplus)
                <div class="change-badge surplus flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 transition-colors">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                            <rect x="2.5" y="6.5" width="19" height="11" rx="2" />
                            <circle cx="12" cy="12" r="2.5" />
                        </svg>
                    </span>
                    <div class="flex flex-1 items-center justify-between">
                        <span class="text-[11px] font-semibold text-blue-500">Kembalian</span>
                        <span class="text-sm font-bold text-blue-700">Rp {{ number_format($this->kembalian, 0, ',', '.') }}</span>
                    </div>
                </div>
            @else
                <div style="height:32px;"></div>
            @endif
        </div>

        <button wire:click="submitTransaction" class="submit-btn" wire:loading.attr="disabled"
            @if ($isDisabled) disabled @endif type="button">
            <svg style="width:17px;height:17px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            Selesaikan Pembayaran
        </button>
        @if ($disabledReason)
            <p class="mt-1.5 flex items-center justify-center gap-1 text-center text-[11px] font-medium text-red-500" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" class="h-3.5 w-3.5 shrink-0">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 8v4.5" />
                    <path d="M12 16h.01" />
                </svg>
                {{ $disabledReason }}
            </p>
        @endif
    </div>
</div>