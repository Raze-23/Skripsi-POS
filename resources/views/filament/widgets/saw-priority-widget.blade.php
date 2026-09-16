@php
    $rankedProducts = $this->getRankedProducts();

    $factorMap = [
        'penjualan' => [
            'label' => 'Penjualan',
            'icon' => 'heroicon-o-arrow-trending-up',
            'class' => 'text-emerald-600',
        ],
        'kedaluwarsa' => [
            'label' => 'Kedaluwarsa',
            'icon' => 'heroicon-o-clock',
            'class' => 'text-amber-600',
        ],
        'stok' => [
            'label' => 'Sisa stok',
            'icon' => 'heroicon-o-archive-box-x-mark',
            'class' => 'text-orange-600',
        ],
        'req_mitra' => [
            'label' => 'Request Mitra',
            'icon' => 'heroicon-o-building-storefront',
            'class' => 'text-sky-600',
        ],
        'req_owner' => [
            'label' => 'Request Owner',
            'icon' => 'heroicon-o-clipboard-document-check',
            'class' => 'text-indigo-600',
        ],
    ];

    $movementMap = [
        'new' => ['color' => 'info', 'icon' => 'heroicon-m-sparkles'],
        'up' => ['color' => 'success', 'icon' => 'heroicon-m-arrow-trending-up'],
        'down' => ['color' => 'danger', 'icon' => 'heroicon-m-arrow-trending-down'],
        'score_up' => ['color' => 'success', 'icon' => 'heroicon-m-arrow-up'],
        'score_down' => ['color' => 'warning', 'icon' => 'heroicon-m-arrow-down'],
        'changed' => ['color' => 'info', 'icon' => 'heroicon-m-arrows-right-left'],
        'stable' => ['color' => 'gray', 'icon' => 'heroicon-m-minus'],
    ];

    $topProduct = $rankedProducts[0] ?? null;
    $topFactor = $topProduct ? ($factorMap[$topProduct['faktor_utama']] ?? $factorMap['penjualan']) : null;

    // Hanya menghitung produk yang peringkatnya murni naik atau turun
    $rankUpdated = collect($rankedProducts)
        ->filter(fn (array $item): bool => in_array($item['movement']['type'] ?? 'stable', ['up', 'down'], true))
        ->count();

    $lastChange = collect($rankedProducts)
        ->pluck('movement.changed_at')
        ->filter()
        ->sortByDesc(fn ($date) => $date->getTimestamp())
        ->first();
@endphp

<x-filament-widgets::widget wire:poll.15s>
    <div class="space-y-6">

        {{-- ── 1. STATUS ANALISIS ────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">
                Status Analisis
            </x-slot>

            <x-slot name="headerEnd">
                <div class="text-xs font-medium" style="color: red;">
                    Live
                </div>
            </x-slot>

            @if ($topProduct)
                <div class="grid divide-y divide-gray-200 dark:divide-gray-700 md:grid-cols-3 md:divide-x md:divide-y-0">

                    {{-- Kolom 1: Prioritas Utama (Diperindah) --}}
                    <div class="py-3 md:py-1 md:pr-6 flex flex-col justify-center">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prioritas Restok Utama</p>
                        <p class="mt-1.5 text-lg font-bold text-gray-900 dark:text-white leading-tight truncate">
                            {{ $topProduct['nama'] }}
                        </p>
                        @if($topFactor)
                            @php
                                // Menyesuaikan class warna teks menjadi warna standar Filament Badge
                                $badgeColor = match(true) {
                                    str_contains($topFactor['class'], 'emerald') => 'success',
                                    str_contains($topFactor['class'], 'amber') => 'warning',
                                    str_contains($topFactor['class'], 'orange') => 'danger',
                                    str_contains($topFactor['class'], 'sky') => 'info',
                                    str_contains($topFactor['class'], 'indigo') => 'primary',
                                    default => 'gray',
                                };
                            @endphp
                            <div class="mt-2">
                                <x-filament::badge :color="$badgeColor" :icon="$topFactor['icon']">
                                    Karena {{ strtolower($topFactor['label']) }}
                                </x-filament::badge>
                            </div>
                        @endif
                    </div>

                    {{-- Kolom 2: Metrik Vi (Lebih Menonjol) --}}
                    <div class="py-3 md:px-6 md:py-1 flex flex-col justify-center">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nilai Preferensi (Vi)</p>
                        <p class="mt-1 text-2xl font-black tracking-tight text-primary-600 dark:text-primary-400">
                            {{ number_format(ceil($topProduct['score'] * 1000) / 1000, 3, ',', '.') }}
                        </p>
                    </div>

                    {{-- Kolom 3: Pembaruan Sistem --}}
                    <div class="py-3 md:py-1 md:pl-6 flex flex-col justify-center">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pembaruan Terakhir</p>
                        @if ($lastChange)
                            <p class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white" title="{{ $lastChange->translatedFormat('d F Y, H:i') }}">
                                {{ $lastChange->diffForHumans() }}
                            </p>
                        @else
                            <p class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">
                                Stabil (Belum ada)
                            </p>
                        @endif
                        <p class="mt-1 text-xs text-gray-500">
                            <strong class="font-medium text-gray-700 dark:text-gray-300">{{ $rankUpdated }}</strong> dari {{ count($rankedProducts) }} rekomendasi berubah
                        </p>
                    </div>
                </div>

                {{-- Legenda Bobot --}}
                <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                    @foreach (\App\Filament\Widgets\SawPriorityWidget::BOBOT as $criterion => $weight)
                        @php $c = $factorMap[$criterion]; @endphp
                        <span class="inline-flex items-center gap-1.5 text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <x-dynamic-component :component="$c['icon']" class="h-3.5 w-3.5 flex-shrink-0 {{ $c['class'] }}" />
                            {{ $c['label'] }}
                            <strong class="font-bold text-gray-900 dark:text-gray-200">{{ number_format($weight * 100, 0) }}%</strong>
                        </span>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center">
                    <x-heroicon-o-chart-bar class="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600" />
                    <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada data produk untuk dianalisis sistem.</p>
                </div>
            @endif
        </x-filament::section>

        {{-- ── 2. PERINGKAT & SKOR ───────────────────────────────────────── --}}
        @if ($rankedProducts)
            <x-filament::section>
                <x-slot name="heading">
                    Peringkat Rekomendasi
                </x-slot>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($rankedProducts as $item)
                        @php
                            $factor = $factorMap[$item['faktor_utama']] ?? $factorMap['penjualan'];
                            $movement = $item['movement'];
                            $movementInfo = $movementMap[$movement['type']] ?? $movementMap['stable'];
                        @endphp

                        <div class="py-4 first:pt-0 last:pb-0">
                            <div class="flex items-center justify-between gap-4">

                                {{-- Kiri: Urutan & Nama --}}
                                <div class="flex items-center gap-6 min-w-0">

                                    {{-- Kotak Nomor Rank (Murni Hitam Putih Elegan) --}}
                                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white text-sm font-bold text-gray-900 ring-1 ring-inset ring-gray-300 dark:bg-gray-900 dark:text-white dark:ring-gray-700">
                                        {{ $loop->iteration }}
                                    </span>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $item['nama'] }}
                                        </p>
                                        <p class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                            <x-dynamic-component
                                                :component="$factor['icon']"
                                                class="h-3.5 w-3.5 flex-shrink-0 {{ $factor['class'] }}"
                                            />
                                            {{ $factor['label'] }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Kanan: Badge Perubahan & Skor Vi --}}
                                <div class="flex flex-shrink-0 items-center gap-4">
                                    @if ($movement['type'] !== 'stable')
                                        <div class="hidden sm:block">
                                            <x-filament::badge :color="$movementInfo['color']" :icon="$movementInfo['icon']" size="sm">
                                                {{ $movement['label'] }}
                                            </x-filament::badge>
                                        </div>
                                    @endif

                                    <div class="text-right w-16">
                                        <span class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ number_format(ceil($item['score'] * 1000) / 1000, 3, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            {{-- ── 3. MATRIKS KEPUTUSAN ──────────────────────────────────────── --}}
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">
                    Rincian Matriks Keputusan
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-max text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                                <th class="pb-3 pr-4 text-xs font-semibold text-gray-500">#</th>
                                <th class="pb-3 pr-6 text-xs font-semibold text-gray-500">Produk</th>
                                <th class="pb-3 px-4 text-right text-xs font-semibold text-gray-500">Penjualan</th>
                                <th class="pb-3 px-4 text-right text-xs font-semibold text-gray-500">Kedaluwarsa</th>
                                <th class="pb-3 px-4 text-right text-xs font-semibold text-gray-500">Stok</th>
                                <th class="pb-3 px-4 text-right text-xs font-semibold text-gray-500">Mitra</th>
                                <th class="pb-3 px-4 text-right text-xs font-semibold text-gray-500">Owner</th>
                                <th class="pb-3 pl-4 text-right text-xs font-semibold text-gray-500">Vi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($rankedProducts as $item)
                                <tr>
                                    <td class="py-2.5 pr-4 text-gray-500 dark:text-gray-400">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="py-2.5 pr-6 font-medium text-gray-800 dark:text-gray-200">
                                        {{ $item['nama'] }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right text-gray-600 dark:text-gray-400">
                                        {{ number_format($item['c1_display'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right text-gray-600 dark:text-gray-400">
                                        @if ($item['c2_display'] === null)
                                            -
                                        @elseif ($item['c2_display'] <= 0)
                                            0
                                        @else
                                            {{ number_format($item['c2_display'], 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-4 text-right text-gray-600 dark:text-gray-400">
                                        {{ number_format($item['c3_display'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right text-gray-600 dark:text-gray-400">
                                        {{ number_format($item['c4_display'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right text-gray-600 dark:text-gray-400">
                                        {{ number_format($item['c5_display'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 pl-4 text-right">
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ number_format(ceil($item['score'] * 1000) / 1000, 3, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-widgets::widget>
