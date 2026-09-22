<x-filament-panels::page>
    @include('filament.resources.product-request-resource.pages.partials.styles')

    <div class="pr-shell pr-theme-owner">
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
                            aria-label="Hapus pencarian"
                        >
                            <x-filament::icon icon="heroicon-m-x-mark" />
                        </button>
                    @endif
                </label>
            </div>

            <div class="pr-grid">
                @forelse ($this->products as $product)
                    @php($isSelected = $selectedProductId === $product->id)

                    <button
                        type="button"
                        wire:key="product-request-product-{{ $product->id }}"
                        wire:click="selectProduct({{ $product->id }})"
                        class="pr-card {{ $isSelected ? 'is-selected' : '' }}"
                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                    >
                        <span class="pr-card-media">
                            <img
                                src="{{ $product->foto ? asset('storage/' . $product->foto) : asset('images/notfound.png') }}"
                                alt="{{ $product->nama }}"
                                loading="lazy"
                                class="{{ $product->is_discontinued ? 'is-discontinued' : '' }}"
                            />

                            @if ($isSelected)
                                <span class="pr-selected-badge" aria-label="Produk terpilih">
                                    <x-filament::icon icon="heroicon-m-check" />
                                </span>
                            @endif
                        </span>

                        <span class="pr-card-content">
                            <span class="pr-card-name" title="{{ $product->nama }}">{{ $product->nama }}</span>
                        </span>
                    </button>
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

        <aside class="pr-summary" aria-labelledby="request-summary-title">
            <form wire:submit.prevent="submit" class="pr-summary-form">
                <header class="pr-summary-header">
                    <span class="pr-summary-heading-icon">
                        <x-filament::icon icon="heroicon-o-clipboard-document-list" />
                    </span>
                    <div>
                        <h2 id="request-summary-title" class="pr-section-title">Detail Usulan</h2>
                        <p class="pr-section-meta">Produksi owner</p>
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
                                <span>{{ $this->selectedProduct->estimasi_masak ?: '-' }} menit / produksi</span>
                            </div>
                        </div>

                        <div class="pr-production-insights" aria-label="Acuan jumlah produksi">
                            <div class="pr-production-insight-grid">
                                <div>
                                    <span title="Total stok dari batch yang belum kedaluwarsa">Stok Attiin</span>
                                    <strong>{{ number_format($this->selectedProduct->stok_attiin, 0, ',', '.') }} pcs</strong>
                                </div>
                                <div>
                                    <span>Terjual bulan ini</span>
                                    <strong>{{ number_format($this->selectedProduct->terjual_bulan_ini, 0, ',', '.') }} pcs</strong>
                                </div>
                                <div>
                                    <span title="Request mitra berstatus menunggu keputusan atau diproses">Kebutuhan mitra</span>
                                    <strong>{{ number_format($this->selectedProduct->kebutuhan_mitra_aktif, 0, ',', '.') }} pcs</strong>
                                </div>
                                <div @class(['is-warning' => $this->selectedProduct->stok_mendekati_kedaluwarsa > 0])>
                                    <span title="Stok batch yang akan kedaluwarsa dalam 30 hari">ED ≤ 30 hari</span>
                                    <strong>{{ number_format($this->selectedProduct->stok_mendekati_kedaluwarsa, 0, ',', '.') }} pcs</strong>
                                </div>
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
                        <label for="request-quantity">Jumlah target produksi</label>
                        <div class="pr-quantity-control">
                            <button
                                type="button"
                                wire:click="updateJumlah({{ $jumlah - 1 }})"
                                @disabled(! $selectedProductId || $jumlah <= 1)
                                title="Kurangi jumlah"
                                aria-label="Kurangi jumlah"
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
                                title="Tambah jumlah"
                                aria-label="Tambah jumlah"
                            >
                                <x-filament::icon icon="heroicon-m-plus" />
                            </button>
                        </div>

                        @error('jumlah')
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
                        <span wire:loading.remove wire:target="submit">Kirim Usulan</span>
                        <span wire:loading wire:target="submit">Mengirim...</span>
                    </button>

                    <a href="{{ $this->getResource()::getUrl('index') }}" wire:navigate class="pr-cancel">Batal</a>
                </footer>
            </form>
        </aside>
    </div>
</x-filament-panels::page>
