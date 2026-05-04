<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Support\WorkspaceBranding;
use App\Filament\SuperAdmin\Widgets\PlatformStats;
use App\Filament\SuperAdmin\Widgets\RecentCompaniesTable;
use App\Filament\SuperAdmin\Widgets\RecentPlatformChatsTable;
use App\Filament\SuperAdmin\Widgets\SystemMonitoringStats;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('super-admin')
            ->path('super-admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('Agent Super Admin')
            ->brandLogo(fn (): string => Filament::auth()->check()
                ? WorkspaceBranding::lightLogoUrl()
                : WorkspaceBranding::loginLogoUrl())
            ->darkModeBrandLogo(fn (): string => Filament::auth()->check()
                ? WorkspaceBranding::darkLogoUrl()
                : WorkspaceBranding::loginLogoUrl())
            ->brandLogoHeight('2.75rem')
            ->login(Login::class)
            ->globalSearch(false)
            ->colors([
                'primary' => Color::hex('#d3033d'),
                'gray' => Color::Slate,
            ])
            ->navigationItems([
                NavigationItem::make('Logout')
                    ->group('General Settings')
                    ->icon(Heroicon::OutlinedArrowLeftStartOnRectangle)
                    ->url('#')
                    ->sort(99)
                    ->visible(fn (): bool => (bool) auth()->user()?->isSuperAdmin())
                    ->extraAttributes([
                        'class' => 'ka-logout-nav-item',
                        'x-on:click.prevent' => '$dispatch(\'open-modal\', { id: \'company-logout-confirmation\' })',
                    ]),
            ])
            ->navigationGroups([
                NavigationGroup::make('Management'),
                NavigationGroup::make('Conversations'),
                NavigationGroup::make('Workspace Content'),
                NavigationGroup::make('General Settings'),
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View => view('filament.partials.runtime-overrides'),
            )
            ->discoverResources(in: app_path('Filament/SuperAdmin/Resources'), for: 'App\\Filament\\SuperAdmin\\Resources')
            ->discoverPages(in: app_path('Filament/SuperAdmin/Pages'), for: 'App\\Filament\\SuperAdmin\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                PlatformStats::class,
                SystemMonitoringStats::class,
                RecentCompaniesTable::class,
                RecentPlatformChatsTable::class,
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
            ]);
    }
}
