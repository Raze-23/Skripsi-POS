<x-filament-panels::page>

    <div class="livewire-root-wrapper" style="width: 100%;">

        @include('filament.pages.partials.styles')

        <div class="pos-shell">
            @include('filament.pages.partials.product-grid')
            @include('filament.pages.partials.cart-sidebar')
        </div>

        @include('filament.pages.partials.modals')
        @include('filament.pages.partials.scripts')

    </div>
    </x-filament-panels::page>
