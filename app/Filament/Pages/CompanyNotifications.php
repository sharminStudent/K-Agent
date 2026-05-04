<?php

namespace App\Filament\Pages;

use App\Services\ActivityLogService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use UnitEnum;

class CompanyNotifications extends Page
{
    use HasMaxWidth;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notifications';

    protected static string|UnitEnum|null $navigationGroup = 'General Settings';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.company-notifications';

    public static function canAccess(): bool
    {
        return Filament::auth()->check() && ! Filament::auth()->user()?->isSuperAdmin();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Notifications';
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function getNotifications(): Collection
    {
        return Filament::auth()->user()
            ?->notifications()
            ->latest()
            ->limit(50)
            ->get() ?? collect();
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = Filament::auth()->user()
            ?->notifications()
            ->whereKey($notificationId)
            ->first();

        if (! $notification) {
            return;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();

            app(ActivityLogService::class)->log(
                event: 'admin.notification.read',
                description: 'A workspace notification was marked as read.',
                category: 'admin',
                user: Filament::auth()->user(),
                meta: [
                    'summary' => (string) data_get($notification->data, 'title', 'Notification'),
                ],
            );
        }
    }

    public function markAllAsRead(): void
    {
        Filament::auth()->user()?->unreadNotifications->markAsRead();

        app(ActivityLogService::class)->log(
            event: 'admin.notifications.read_all',
            description: 'All workspace notifications were marked as read.',
            category: 'admin',
            user: Filament::auth()->user(),
        );

        Notification::make()
            ->success()
            ->title('Notifications updated')
            ->body('All notifications have been marked as read.')
            ->send();
    }
}
