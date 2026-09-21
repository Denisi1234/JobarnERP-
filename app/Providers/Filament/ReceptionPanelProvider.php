<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ReceptionPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('reception')
            ->path('reception-panel')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->brandName('Jobarn — Reception')
            ->brandLogo(asset('images/jobarn-logo.svg'))
            ->brandLogoHeight('2rem')
            ->discoverResources(in: app_path('Filament/Reception/Resources'), for: 'App\\Filament\\Reception\\Resources')
            ->discoverPages(in: app_path('Filament/Reception/Pages'), for: 'App\\Filament\\Reception\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Reception/Widgets'), for: 'App\\Filament\\Reception\\Widgets')
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
            ->viteTheme('resources/css/filament/app/theme.css');
    }
}
