@php
    $faktorMap = [
        'penjualan' => [
            'label' => 'Penjualan tinggi',
            'icon'  => 'heroicon-o-fire',
            'class' => 'text-danger-500',
        ],
        'stok' => [
            'label' => 'Stok menipis',
            'icon'  => 'heroicon-o-archive-box',
            'class' => 'text-warning-500',
        ],
        'kedaluwarsa' => [
            'label' => 'Mendekati kedaluwarsa',
            'icon'  => 'heroicon-o-clock',
            'class' => 'text-warning-500',
        ],
        'produksi' => [
            'label' => 'Cepat diproduksi',
            'icon'  => 'heroicon-o-bolt',
            'class' => 'text-primary-500',
        ],
    ];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold tracking-tight text-gray-950 dark:text-white flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500" />
                Rekomendasi Produksi
            </h2>
            <span class="text-xs font-medium text-gray-500">
                Tahun {{ $this->filters['year'] ?? now()->year }}
            </span>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($this->getRankedProducts() as $index => $item)
                @php
                    $faktor = $faktorMap[$item['faktor_utama']] ?? $faktorMap['stok'];

                    if ($index < 2) {
                        $badgeLabel = 'Sangat Mendesak';
                        $badgeColor = 'danger';
                    } elseif ($index < 4) {
                        $badgeLabel = 'Mendesak';
                        $badgeColor = 'warning';
                    } else {
                        $badgeLabel = 'Perlu Disiapkan';
                        $badgeColor = 'primary';
                    }
                @endphp

                <div class="flex items-center justify-between gap-3 py-2.5">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="flex items-center justify-center flex-shrink-0 w-6 h-6 text-xs font-bold text-white rounded-full bg-primary-600 dark:bg-primary-500">
                            {{ $index + 1 }}
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">
                                {{ $item['nama'] }}
                            </p>
                            <p class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                <x-dynamic-component :component="$faktor['icon']" class="w-3.5 h-3.5 flex-shrink-0 {{ $faktor['class'] }}" />
                                {{ $faktor['label'] }}
                            </p>
                        </div>
                    </div>

                    <x-filament::badge :color="$badgeColor" size="sm">
                        {{ $badgeLabel }}
                    </x-filament::badge>
                </div>
            @empty
                <div class="py-4 text-sm text-center text-gray-500">
                    Belum ada data untuk dihitung.
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>