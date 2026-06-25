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
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name">{{ $item['nama'] }}</div>
                        <div class="cart-item-price">@ Rp {{ number_format($item['harga'], 0, ',', '.') }}</div>
                    </div>
                    <div class="qty-wrap">
                        <button wire:click="updateQty({{ $id }}, {{ $item['qty'] - 1 }})" class="qty-btn"
                            type="button">−</button>
                        <span class="qty-val">{{ $item['qty'] }}</span>
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

        <div>
            <div style="font-size:11px;font-weight:600;color:#6b7280;margin-bottom:5px;">Uang Diterima</div>
            <div class="cash-wrap">
                <span class="cash-prefix">Rp</span>
                <input type="number" wire:model.live.debounce.500ms="bayar" class="cash-input" placeholder="0"
                    min="0" />
            </div>
        </div>

        @php $uangBayar = (int) $bayar ?: 0; @endphp
        @if ($uangBayar > 0 && !empty($cart))
            @if ($this->kembalian < 0)
                <div class="change-badge shortage">
                    <span>Kurang</span>
                    <span>Rp {{ number_format(abs($this->kembalian), 0, ',', '.') }}</span>
                </div>
            @elseif($this->kembalian === 0)
                <div class="change-badge exact">
                    <span>Pas</span>
                    <span>Rp 0</span>
                </div>
            @else
                <div class="change-badge surplus">
                    <span>Kembalian</span>
                    <span>Rp {{ number_format($this->kembalian, 0, ',', '.') }}</span>
                </div>
            @endif
        @else
            <div style="height:32px;"></div>
        @endif

        <button wire:click="submitTransaction" class="submit-btn" wire:loading.attr="disabled"
            @if (empty($cart) || $uangBayar < $this->total_harga) disabled @endif type="button">
            <svg style="width:17px;height:17px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            Selesaikan Pembayaran
        </button>
    </div>
</div>
