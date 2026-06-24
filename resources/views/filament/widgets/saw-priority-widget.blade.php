<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold tracking-tight text-gray-950 dark:text-white">
                Prioritas Restok
            </h2>
            <span class="text-xs font-medium text-gray-500">Poin SAW</span>
        </div>

        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($this->rankedProducts as $index => $item)
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white rounded-full bg-primary-600 dark:bg-primary-500">
                            {{ $index + 1 }}
                        </span>

                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ $item['nama'] }}
                        </span>
                    </div>

                    <span class="text-xs font-semibold text-primary-600 dark:text-primary-400">
                        {{ number_format($item['score'], 2) }}
                    </span>
                </div>
            @empty
                <div class="py-4 text-sm text-center text-gray-500">
                    Belum ada data prioritas.
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
