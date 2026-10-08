<?php

namespace App\Providers\Filament;

use App\Enums\AdminNavigationGroup;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Widgets\StatsOverview;
use App\Http\Middleware\SetUserLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(EditProfile::class)
            ->brandName(fn (): string => settings('general.site_name').' · Admin')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.25rem')
            ->colors(fn (): array => [
                'primary' => Color::hex(preg_match('/^#[0-9a-f]{6}$/i', (string) settings('appearance.accent')) ? settings('appearance.accent') : '#f7941d'),
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->favicon(fn (): string => settings()->faviconUrl() ?? asset('favicon.svg'))
            ->navigationGroups(AdminNavigationGroup::class)
            ->navigationItems([
                NavigationItem::make('website')
                    ->label(fn (): string => __('admin.nav.view_site'))
                    ->url(fn (): string => route('home', ['locale' => app()->getLocale()]), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->sort(100),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                StatsOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SetUserLocale::class,
            ], isPersistent: true);
    }
}
