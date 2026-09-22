<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center text-center py-12 px-4 space-y-6">
        <div class="p-6 bg-yellow-100 rounded-full dark:bg-yellow-900/30">
            <x-heroicon-o-sparkles class="w-16 h-16 text-yellow-600 dark:text-yellow-500" />
        </div>
        
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
            Selamat Datang, {{ $this->getApotekName() }}!
        </h1>
        
        <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl">
            Terima kasih telah menjadi bagian dari jaringan mitra Herbal At-Tiin. Melalui portal ini, Anda dapat memantau stok, mengajukan permintaan restok, dan mengelola profil apotek Anda dengan mudah.
        </p>

        <div class="mt-8">
            <x-filament::button
                href="{{ \App\Filament\Mitra\Pages\Dashboard::getUrl() }}"
                tag="a"
                size="lg"
                color="primary"
                icon="heroicon-o-arrow-right"
                icon-position="after"
            >
                Masuk ke Dashboard
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
