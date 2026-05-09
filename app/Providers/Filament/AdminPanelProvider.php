<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Widgets\CompanyStats;
use App\Filament\Widgets\ConversationTrends;
use App\Filament\Widgets\RecentChatSessionsTable;
use App\Filament\Widgets\RecentLeadsTable;
use App\Support\WorkspaceBranding;
use Filament\Facades\Filament;
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
use Illuminate\Contracts\View\View;
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
            ->brandName((string) config('app.name', 'Workspace'))
            ->brandLogo(fn (): string => Filament::auth()->check()
                ? WorkspaceBranding::lightLogoUrl()
                : WorkspaceBranding::loginLogoUrl())
            ->darkModeBrandLogo(fn (): string => Filament::auth()->check()
                ? WorkspaceBranding::darkLogoUrl()
                : WorkspaceBranding::loginLogoUrl())
            ->brandLogoHeight('2.75rem')
            ->login(Login::class)
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
                    ->visible(fn (): bool => ! (bool) auth()->user()?->isSuperAdmin())
                    ->extraAttributes([
                        'class' => 'ka-logout-nav-item',
                        'x-on:click.prevent' => '$dispatch(\'open-modal\', { id: \'company-logout-confirmation\' })',
                    ]),
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View => view('filament.partials.runtime-overrides'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                CompanyStats::class,
                ConversationTrends::class,
                RecentChatSessionsTable::class,
                RecentLeadsTable::class,
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
