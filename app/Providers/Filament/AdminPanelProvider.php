<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Support\WorkspaceBranding;
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
use Filament\View\PanelsRenderHook;
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
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('K-Agent')
            ->brandLogo(fn (): string => request()->routeIs('filament.admin.auth.login')
                ? WorkspaceBranding::loginLogoUrl()
                : WorkspaceBranding::lightLogoUrl())
            ->darkModeBrandLogo(fn (): string => request()->routeIs('filament.admin.auth.login')
                ? WorkspaceBranding::loginLogoUrl()
                : WorkspaceBranding::darkLogoUrl())
            ->brandLogoHeight('2.75rem')
            ->login(Login::class)
            ->colors([
                'primary' => Color::hex('#d3033d'),
                'gray' => Color::Slate,
            ])
            ->navigationItems([
                NavigationItem::make('General Settings')
                    ->icon(Heroicon::OutlinedCog8Tooth)
                    ->url('#')
                    ->sort(4),
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): \Illuminate\Contracts\View\View => view('filament.partials.runtime-overrides'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\SetupStatus::class,
                \App\Filament\Widgets\CompanyStats::class,
                \App\Filament\Widgets\ConversationTrends::class,
                \App\Filament\Widgets\WorkspaceAccount::class,
                \App\Filament\Widgets\RecentChatSessionsTable::class,
                \App\Filament\Widgets\RecentLeadsTable::class,
                \App\Filament\Widgets\WorkspaceUsersTable::class,
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
