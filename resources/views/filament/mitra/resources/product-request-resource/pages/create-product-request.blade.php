@php
    $resource = $this->getResource();
@endphp

<x-filament-panels::page>
    @include('filament.resources.product-request-resource.pages.partials.styles')

    <div class="pr-shell pr-theme-mitra">
        <section class="pr-catalog" aria-labelledby="product-catalog-title">
            <div class="pr-catalog-header">
                <div>
                    <h2 id="product-catalog-title" class="pr-section-title">Pilih Produk</h2>
                    <p class="pr-section-meta">{{ $this->products->count() }} produk tersedia</p>
                </div>

                <label class="pr-search">
                    <span class="sr-only">Cari produk</span>
                    <x-filament::icon icon="heroicon-m-magnifying-glass" class="pr-search-icon" />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari produk..."
                        class="pr-search-input"
                        autocomplete="off"
                    />
                    @if ($search !== '')
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="pr-search-clear"
                            title="Hapus pencarian"
                        >
                            <x-filament::icon icon="heroicon-m-x-mark" />
                        </button>
                    @endif
                </label>
            </div>

            <div class="pr-grid">
                @forelse ($this->products as $product)
                    @php($isSelected = $selectedProductId === $product->id)

                    <article
                        wire:key="product-request-product-{{ $product->id }}"
                        class="pr-card {{ $isSelected ? 'is-selected' : '' }}"
                    >
                        <button
                            type="button"
                            wire:click="selectProduct({{ $product->id }})"
                            class="pr-card-select"
                            aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                            aria-label="Pilih {{ $product->nama }}"
                        >
                            <span class="pr-card-media">
                                <img
                                    src="{{ $product->foto ? asset('storage/' . $product->foto) : asset('images/notfound.png') }}"
                                    alt="{{ $product->nama }}"
                                    loading="lazy"
                                    class="{{ $product->is_discontinued ? 'is-discontinued' : '' }}"
                                />

                                <span class="pr-best-seller-badges" aria-label="Peringkat produk terlaris">
                                    @if ($product->best_seller_attiin_rank)
                                        <span class="pr-best-seller-badge is-attiin">
                                            <x-filament::icon icon="heroicon-s-trophy" />
                                            Top {{ $product->best_seller_attiin_rank }} Attiin
                                        </span>
                                    @endif
                                    @if ($product->best_seller_mitra_rank)
                                        <span class="pr-best-seller-badge is-mitra">
                                            <x-filament::icon icon="heroicon-s-star" />
                                            Top {{ $product->best_seller_mitra_rank }} Mitra
                                        </span>
                                    @endif
                                </span>

                                @if ($isSelected)
                                    <span class="pr-selected-badge" aria-label="Produk terpilih">
                                        <x-filament::icon icon="heroicon-m-check" />
                                    </span>
                                @endif
                            </span>

                            <span class="pr-card-content">
                                <span class="pr-card-name" title="{{ $product->nama }}">{{ $product->nama }}</span>
                                <span class="pr-card-price">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</span>
                            </span>
                        </button>

                        <button
                            type="button"
                            wire:click="openDetailModal({{ $product->id }})"
                            class="pr-info-button"
                            title="Lihat informasi lengkap {{ $product->nama }}"
                            aria-label="Lihat informasi lengkap {{ $product->nama }}"
                        >
                            <x-filament::icon icon="heroicon-m-information-circle" />
                        </button>
                    </article>
                @empty
                    <div class="pr-empty-state">
                        <span class="pr-empty-icon">
                            <x-filament::icon icon="heroicon-o-magnifying-glass" />
                        </span>
                        <strong>Produk tidak ditemukan</strong>
                        <span>Coba gunakan kata pencarian lain.</span>
                    </div>
                @endforelse
            </div>
        </section>

        <!-- BAGIAN SIDEBAR SUMMARY -->
        <aside class="pr-summary" aria-labelledby="request-summary-title">
            <form wire:submit.prevent="submit" class="pr-summary-form">
                <header class="pr-summary-header">
                    <span class="pr-summary-heading-icon">
                        <x-filament::icon icon="heroicon-o-clipboard-document-list" />
                    </span>
                    <div>
                        <h2 id="request-summary-title" class="pr-section-title">Detail Request</h2>
                        <p class="pr-section-meta">Restok apotek</p>
                    </div>
                </header>

                <div class="pr-summary-body">
                    @if ($this->selectedProduct)
                        <div class="pr-selected-product">
                            <img
                                src="{{ $this->selectedProduct->foto ? asset('storage/' . $this->selectedProduct->foto) : asset('images/notfound.png') }}"
                                alt="{{ $this->selectedProduct->nama }}"
                            />
                            <div>
                                <strong title="{{ $this->selectedProduct->nama }}">{{ $this->selectedProduct->nama }}</strong>
                                <span>Rp {{ number_format($this->selectedProduct->harga_jual, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="pr-no-selection">
                            <span class="pr-empty-icon">
                                <x-filament::icon icon="heroicon-o-shopping-bag" />
                            </span>
                            <strong>Belum ada produk dipilih</strong>
                            <span>Pilih satu produk dari katalog.</span>
                        </div>
                    @endif

                    @error('selectedProductId')
                        <p class="pr-error" role="alert">{{ $message }}</p>
                    @enderror

                    <div class="pr-quantity-field">
                        <label for="request-quantity">Jumlah request</label>
                        <div class="pr-quantity-control">
                            <button
                                type="button"
                                wire:click="updateJumlah({{ $jumlah - 1 }})"
                                @disabled(! $selectedProductId || $jumlah <= 1)
                            >
                                <x-filament::icon icon="heroicon-m-minus" />
                            </button>
                            <input
                                id="request-quantity"
                                type="number"
                                value="{{ $jumlah }}"
                                wire:change="updateJumlah($event.target.value)"
                                wire:key="request-quantity-{{ $selectedProductId ?? 'empty' }}-{{ $jumlah }}"
                                min="1"
                                inputmode="numeric"
                                @disabled(! $selectedProductId)
                            />
                            <span class="pr-quantity-unit">pcs</span>
                            <button
                                type="button"
                                wire:click="updateJumlah({{ $jumlah + 1 }})"
                                @disabled(! $selectedProductId)
                            >
                                <x-filament::icon icon="heroicon-m-plus" />
                            </button>
                        </div>
                        @error('jumlah')
                            <p class="pr-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pr-discount-field">
                        <label for="request-discount">Permintaan diskon</label>
                        <div class="pr-discount-control">
                            <span class="pr-discount-icon" aria-hidden="true">
                                <x-filament::icon icon="heroicon-o-receipt-percent" />
                            </span>
                            <input
                                id="request-discount"
                                type="number"
                                wire:model.live.debounce.250ms="diskonPersen"
                                min="0"
                                max="100"
                                step="0.01"
                                inputmode="decimal"
                                placeholder="0"
                                @disabled(! $selectedProductId)
                            />
                            <span class="pr-discount-unit">%</span>
                        </div>
                        <p class="pr-field-help">Isi 0 jika tidak mengajukan diskon.</p>
                        @error('diskonPersen')
                            <p class="pr-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <footer class="pr-summary-actions">
                    <button
                        type="submit"
                        class="pr-submit"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                        @disabled(! $selectedProductId)
                    >
                        <span wire:loading.remove wire:target="submit">
                            <x-filament::icon icon="heroicon-m-paper-airplane" />
                        </span>
                        <span wire:loading wire:target="submit">
                            <x-filament::loading-indicator />
                        </span>
                        <span wire:loading.remove wire:target="submit">Kirim Request</span>
                        <span wire:loading wire:target="submit">Mengirim...</span>
                    </button>
                    <a href="{{ $resource::getUrl('index') }}" wire:navigate class="pr-cancel">Batal</a>
                </footer>
            </form>
        </aside>
    </div>

    <x-filament::modal id="product-detail-modal" width="md" alignment="center">
        <x-slot name="heading">
            Detail Produk
        </x-slot>

        @if ($this->detailProduct)
            <div class="pr-product-detail">
                <div class="pr-product-detail-media">
                    <img
                        src="{{ $this->detailProduct->foto ? asset('storage/' . $this->detailProduct->foto) : asset('images/notfound.png') }}"
                        alt="{{ $this->detailProduct->nama }}"
                        class="{{ $this->detailProduct->is_discontinued ? 'is-discontinued' : '' }}"
                    >

                    @if ($this->detailProduct->best_seller_attiin_rank || $this->detailProduct->best_seller_mitra_rank)
                        <div class="pr-product-detail-badges">
                            @if ($this->detailProduct->best_seller_attiin_rank)
                                <span class="pr-best-seller-badge is-attiin">
                                    <x-filament::icon icon="heroicon-s-trophy" />
                                    Top {{ $this->detailProduct->best_seller_attiin_rank }} Attiin
                                </span>
                            @endif

                            @if ($this->detailProduct->best_seller_mitra_rank)
                                <span class="pr-best-seller-badge is-mitra">
                                    <x-filament::icon icon="heroicon-s-star" />
                                    Top {{ $this->detailProduct->best_seller_mitra_rank }} Mitra
                                </span>
                            @endif
                        </div>
                    @endif

                    @if ($this->detailProduct->is_discontinued)
                        <div class="pr-discontinued-notice">
                            Produk ini telah berhenti diproduksi
                        </div>
                    @endif
                </div>

                <div>
                    <h3 class="pr-product-detail-name">{{ $this->detailProduct->nama }}</h3>
                    <p class="pr-product-detail-description">{{ $this->detailProduct->deskripsi ?: 'Belum ada deskripsi untuk produk ini.' }}</p>
                </div>

                <div class="pr-product-detail-facts">
                    <div class="pr-product-detail-fact">
                        <span>Harga jual</span>
                        <strong>
                            Rp {{ number_format($this->detailProduct->harga_jual, 0, ',', '.') }}
                        </strong>
                    </div>

                    <div class="pr-product-detail-fact">
                        <span>Estimasi produksi</span>
                        <strong>{{ $this->detailProduct->estimasi_masak ?: '-' }} menit</strong>
                    </div>

                    <div class="pr-product-detail-fact is-stock">
                        <span>Stok di Attiin</span>
                        <strong>{{ number_format($this->detailProduct->stok_attiin ?? 0, 0, ',', '.') }} pcs</strong>
                    </div>
                </div>
            </div>
        @else
            <div class="pr-product-detail-loading">
                <x-filament::loading-indicator />
            </div>
        @endif

        <x-slot name="footer">
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id: 'product-detail-modal' })" class="w-full">
                Tutup
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
