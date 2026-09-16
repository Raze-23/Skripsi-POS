<div class="mitra-profile">
    @push('styles')
        @include('filament.mitra.pages.partials.profile-styles')
    @endpush

    <header class="mitra-profile-topbar">
        <a href="{{ filament()->getUrl() }}" class="mitra-profile-brand">
            <img src="{{ asset('images/logo-attiin.png') }}" alt="CV. Herbal AT-TIIN" />
            <span>Portal Mitra</span>
        </a>

        <div class="mitra-profile-navigation">
            <x-filament::link
                :href="filament()->getUrl()"
                icon="heroicon-o-arrow-left"
                color="gray"
            >
                Kembali
            </x-filament::link>

            <x-filament-panels::user-menu />
        </div>
    </header>

    <div class="mitra-profile-heading">
        <h1>{{ $this->getHeading() }}</h1>

        @if ($this->getUser()->isMitraAktif())
            <x-filament::badge color="success" icon="heroicon-o-check-badge">
                Mitra Aktif
            </x-filament::badge>
        @endif
    </div>

    <x-filament-panels::form id="form" wire:submit="save">
        {{ $this->form }}

        <div class="mitra-profile-actions">
            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </div>
    </x-filament-panels::form>

    <x-filament-actions::modals />
</div>
