<?php

namespace App\Providers\Filament;


use App\Filament\Mitra\Pages\EditProfile as MitraEditProfile;
use App\Filament\Mitra\Pages\Login as MitraLogin;
use App\Filament\Mitra\Pages\Register as MitraRegister;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class MitraPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('mitra')
            ->path('mitra')
            ->login(MitraLogin::class)
            ->registration(MitraRegister::class)
            ->colors([
                'primary' => Color::Yellow,
            ])
            ->font('Inter')
            ->simplePageMaxContentWidth(MaxWidth::Medium)
            ->sidebarCollapsibleOnDesktop()
            ->brandName('Portal Mitra — AT-TIIN')
            ->brandLogo(asset('images/logo-attiin.png'))
            ->brandLogoHeight('5.5rem')
            ->favicon(asset('images/logo-attiin.png'))
            ->profile(MitraEditProfile::class)
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Mitra/Resources'), for: 'App\\Filament\\Mitra\\Resources')
            ->discoverPages(in: app_path('Filament/Mitra/Pages'), for: 'App\\Filament\\Mitra\\Pages')
            ->pages([
                \App\Filament\Mitra\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Mitra/Widgets'), for: 'App\\Filament\\Mitra\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => request()->routeIs('filament.mitra.auth.login', 'filament.mitra.auth.register')
                    ? view('filament.mitra.auth-theme')->render()
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string =>
                '<script>
                    function fixNativeValidation() {
                        document.querySelectorAll("form:not([novalidate])").forEach(function (form) {
                            form.setAttribute("novalidate", "novalidate");
                        });
                    }

                    document.addEventListener("DOMContentLoaded", fixNativeValidation);
                    document.addEventListener("livewire:navigated", fixNativeValidation);

                    new MutationObserver(fixNativeValidation).observe(document.body, {
                        childList: true,
                        subtree: true,
                    });
                </script>',
            );
    }
}
