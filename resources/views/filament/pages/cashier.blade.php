<x-filament-panels::page>
    <div>
        {{-- Nyalakan satu per satu untuk mencari tahu file mana yang rusak --}}

        @include('filament.pages.partials.styles')

        <div class="pos-shell">
            @include('filament.pages.partials.product-grid')
            @include('filament.pages.partials.cart-sidebar')
        </div>
        @include('filament.pages.partials.modals')
        @include('filament.pages.partials.scripts')
    </div>
</x-filament-panels::page>
