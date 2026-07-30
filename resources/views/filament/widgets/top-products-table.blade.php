<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold tracking-tight text-gray-950 dark:text-white">
                5 Produk Terlaris
            </h2>
            <span class="text-xs font-medium text-gray-500">Tahun {{ $this->filters['year'] ?? now()->year }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="pb-2 text-left font-semibold text-gray-500 dark:text-gray-400 w-8">#</th>
                        <th class="pb-2 text-left font-semibold text-gray-500 dark:text-gray-400">Produk</th>
                        <th class="pb-2 text-right font-semibold text-gray-500 dark:text-gray-400">Kasir</th>
                        <th class="pb-2 text-right font-semibold text-gray-500 dark:text-gray-400">Apotek</th>
                        <th class="pb-2 text-right font-semibold text-gray-500 dark:text-gray-400">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->getTopProducts() as $index => $item)
                        <tr>
                            <td class="py-2.5">
                                <span class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white rounded-full bg-primary-600 dark:bg-primary-500">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td class="py-2.5 font-medium text-gray-700 dark:text-gray-200">
                                {{ $item['nama'] }}
                            </td>
                            <td class="py-2.5 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($item['kasir']) }}
                            </td>
                            <td class="py-2.5 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($item['apotek']) }}
                            </td>
                            <td class="py-2.5 text-right font-semibold text-primary-600 dark:text-primary-400">
                                {{ number_format($item['total']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-sm text-center text-gray-500">
                                Belum ada data penjualan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
