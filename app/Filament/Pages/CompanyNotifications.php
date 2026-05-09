<?php

namespace App\Filament\Pages;

use App\Services\ActivityLogService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
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

    /**
     * @var array<int, string>
     */
    public array $selectedNotificationIds = [];

    public bool $selectAllOnPage = false;

    public string $readFilter = 'all';

    public string $dateFilter = 'all';

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
        $query = Filament::auth()->user()?->notifications();

        if (! $query) {
            return collect();
        }

        return $this->applyFilters($query)
            ->latest()
            ->limit(50)
            ->get();
    }

    public function updatedSelectAllOnPage(bool $state): void
    {
        $this->selectedNotificationIds = $state
            ? $this->getNotifications()->pluck('id')->all()
            : [];
    }

    public function updatedSelectedNotificationIds(): void
    {
        $visibleCount = $this->getNotifications()->count();

        $this->selectAllOnPage = $visibleCount > 0
            && count($this->selectedNotificationIds) === $visibleCount;
    }

    public function updatedReadFilter(): void
    {
        $this->resetSelection();
    }

    public function updatedDateFilter(): void
    {
        $this->resetSelection();
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

    public function markAsUnread(string $notificationId): void
    {
        $notification = Filament::auth()->user()
            ?->notifications()
            ->whereKey($notificationId)
            ->first();

        if (! $notification) {
            return;
        }

        if ($notification->read_at !== null) {
            $notification->forceFill(['read_at' => null])->save();

            app(ActivityLogService::class)->log(
                event: 'admin.notification.unread',
                description: 'A workspace notification was marked as unread.',
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

    public function markSelectedAsRead(): void
    {
        $notifications = $this->getSelectedNotifications();

        if ($notifications->isEmpty()) {
            return;
        }

        $notifications->each(function (DatabaseNotification $notification): void {
            if ($notification->read_at === null) {
                $notification->markAsRead();
            }
        });

        $this->logBulkAction('admin.notifications.bulk_read', 'Selected notifications were marked as read.', $notifications->count());

        $this->resetSelection();

        Notification::make()
            ->success()
            ->title('Notifications updated')
            ->body('Selected notifications were marked as read.')
            ->send();
    }

    public function markSelectedAsUnread(): void
    {
        $notifications = $this->getSelectedNotifications();

        if ($notifications->isEmpty()) {
            return;
        }

        $notifications->each(function (DatabaseNotification $notification): void {
            if ($notification->read_at !== null) {
                $notification->forceFill(['read_at' => null])->save();
            }
        });

        $this->logBulkAction('admin.notifications.bulk_unread', 'Selected notifications were marked as unread.', $notifications->count());

        $this->resetSelection();

        Notification::make()
            ->success()
            ->title('Notifications updated')
            ->body('Selected notifications were marked as unread.')
            ->send();
    }

    public function deleteSelected(): void
    {
        $notifications = $this->getSelectedNotifications();

        if ($notifications->isEmpty()) {
            return;
        }

        $deletedCount = $notifications->count();
        $notifications->each->delete();

        $this->logBulkAction('admin.notifications.bulk_deleted', 'Selected notifications were deleted.', $deletedCount);

        $this->resetSelection();

        Notification::make()
            ->success()
            ->title('Notifications deleted')
            ->body('Selected notifications were deleted.')
            ->send();
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->readFilter === 'read', fn (Builder $builder) => $builder->whereNotNull('read_at'))
            ->when($this->readFilter === 'unread', fn (Builder $builder) => $builder->whereNull('read_at'))
            ->when($this->dateFilter === 'week', fn (Builder $builder) => $builder->where('created_at', '>=', Carbon::now()->startOfWeek()));
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    protected function getSelectedNotifications(): Collection
    {
        $ids = array_values(array_filter($this->selectedNotificationIds));

        if ($ids === []) {
            return collect();
        }

        return Filament::auth()->user()
            ?->notifications()
            ->whereKey($ids)
            ->get() ?? collect();
    }

    protected function resetSelection(): void
    {
        $this->selectedNotificationIds = [];
        $this->selectAllOnPage = false;
    }

    protected function logBulkAction(string $event, string $description, int $count): void
    {
        app(ActivityLogService::class)->log(
            event: $event,
            description: $description,
            category: 'admin',
            user: Filament::auth()->user(),
            meta: [
                'summary' => $count.' notification(s) affected.',
            ],
        );
    }
}
