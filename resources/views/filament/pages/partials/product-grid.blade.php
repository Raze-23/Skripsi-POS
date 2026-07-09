<div class="pos-left">

    <div class="pos-search-wrap flex items-center gap-2">
        <div class="relative flex-1 min-w-[420px]">
            <svg class="pos-search-icon w-4 h-4"
                style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z" />
            </svg>

            <input type="text" wire:model.live.debounce.300ms="search" wire:keydown.enter="scanBarcode"
                placeholder="Cari produk atau scan batch code..." class="pos-search-input w-full" style="padding-left:36px;" autofocus />
        </div>

        <button onclick="openBarcodeScanner()" class="pos-scan-btn" type="button">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5ZM6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
            </svg>
        </button>
    </div>

    <div class="pos-product-grid">
        @forelse($this->products as $product)
            @php
                $totalStok = $product->productBatches->sum('stok_toko');
                $nearestExpiry = $product->productBatches->min('tanggal_kedaluwarsa');
                $isExpired = $nearestExpiry && \Carbon\Carbon::parse($nearestExpiry)->lt(now()->startOfDay());
                $isDisabled = $totalStok <= 0 || $isExpired;
            @endphp
            <div @if(!$isDisabled) wire:click="addToCart({{ $product->id }})" @endif
                 class="product-card"
                 style="{{ $isDisabled ? 'opacity: 0.5; filter: grayscale(100%); cursor: not-allowed !important;' : '' }}">

                <div class="product-img">
                    @if ($product->foto)
                        <img src="{{ asset('storage/' . $product->foto) }}" alt="{{ $product->nama }}" />
                    @else
                        <div class="no-img">
                            <svg style="width:22px;height:22px;color:#d1d5db;" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                    @endif

                    @if ($isExpired)
                        <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.7); z-index:11;">
                            <span style="background:#dc2626; color:#fff; font-size:11px; font-weight:800; padding:6px 12px; border-radius:8px; text-transform:uppercase; letter-spacing:1px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">KEDALUWARSA</span>
                        </div>
                    @elseif ($totalStok <= 0)
                        <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.7); z-index:11;">
                            <span style="background:#ef4444; color:#fff; font-size:12px; font-weight:800; padding:6px 12px; border-radius:8px; text-transform:uppercase; letter-spacing:1px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">HABIS</span>
                        </div>
                    @elseif ($totalStok <= 5)
                        <div class="stock-badge">Sisa {{ $totalStok }}</div>
                    @endif
                </div>

                <div class="product-body">
                    <div class="product-name">{{ $product->nama }}</div>
                    <div class="product-price" style="{{ $isDisabled ? 'color:#6b7280;' : '' }}">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</div>
                </div>

            </div>
        @empty
            <div class="empty-state">
                <svg style="width:36px;height:36px;opacity:.3;" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                <span style="font-size:13px;font-weight:600;">Produk tidak ditemukan</span>
            </div>
        @endforelse
    </div>
</div>
