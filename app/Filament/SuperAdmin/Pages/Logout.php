<?php

namespace App\Filament\SuperAdmin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Logout extends Page
{
    public $defaultAction = 'confirmLogout';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowLeftStartOnRectangle;

    protected static ?string $navigationLabel = 'Logout';

    protected static string|UnitEnum|null $navigationGroup = 'General Settings';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.company-logout';

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public function confirmLogoutAction(): Action
    {
        return Action::make('confirmLogout')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedArrowLeftStartOnRectangle)
            ->modalHeading('Log out?')
            ->modalDescription('Are you sure you want to log out of the super admin control panel?')
            ->modalSubmitActionLabel('Logout')
            ->modalCancelActionLabel('Cancel')
            ->action(fn () => $this->logout());
    }

    public function logout(): void
    {
        Filament::auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(Filament::getLoginUrl(), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(Profile::getUrl(), navigate: true);
    }
}
