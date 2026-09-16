<div class="border-b border-gray-200 pb-4 dark:border-white/10">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
            Data Pendaftar
        </h3>

        <x-filament::badge color="warning" icon="heroicon-o-clock">
            Menunggu Konfirmasi
        </x-filament::badge>
    </div>

    <x-filament::grid :default="1" :sm="2" class="gap-4">
        <dl class="min-w-0">
            <dt class="text-sm text-gray-500 dark:text-gray-400">Nama Pendaftar</dt>
            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white" style="overflow-wrap: anywhere;">
                {{ $record->name }}
            </dd>
        </dl>

        <dl class="min-w-0">
            <dt class="text-sm text-gray-500 dark:text-gray-400">Alamat Email</dt>
            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white" style="overflow-wrap: anywhere;">
                {{ $record->email }}
            </dd>
        </dl>
    </x-filament::grid>
</div>
