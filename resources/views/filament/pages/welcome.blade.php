<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center text-center py-12 px-4 space-y-6">
        <div class="p-6 bg-green-100 rounded-full dark:bg-green-900/30">
            <x-heroicon-o-sparkles class="w-16 h-16 text-green-600 dark:text-green-500" />
        </div>

        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
            Selamat Datang kembali, {{ $this->getOwnerName() }}!
        </h1>

        <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl">
            Pantau aktivitas penjualan, stok produk, dan kelola jaringan mitra dengan lebih efisien hari ini.
        </p>

        <div class="mt-8">
            <x-filament::button
                href="{{ \App\Filament\Pages\Dashboard::getUrl() }}"
                tag="a"
                size="lg"
                color="primary"
                icon="heroicon-o-arrow-right"
                icon-position="after"
            >
                Buka Dashboard Utama
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
