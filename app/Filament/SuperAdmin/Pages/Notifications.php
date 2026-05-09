<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Notifications\SuperAdminAlertDatabaseNotification;
use App\Services\ActivityLogService;
use App\Services\SuperAdminNotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use UnitEnum;

class Notifications extends Page implements HasTable
{
    use HasMaxWidth;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notifications';

    protected static string|UnitEnum|null $navigationGroup = 'General Settings';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.company-notifications';

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public function mount(): void
    {
        app(SuperAdminNotificationService::class)->sync();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Notifications';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshNotifications')
                ->label('Refresh')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (): void {
                    app(SuperAdminNotificationService::class)->sync();

                    Notification::make()
                        ->success()
                        ->title('Notifications refreshed')
                        ->body('Super admin alerts were rebuilt from the latest billing and runtime data.')
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getNotificationsQuery())
            ->description('Billing, client account, and runtime alerts across all workspaces.')
            ->columns([
                TextColumn::make('data.title')
                    ->label('Notification')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('data->title', 'like', "%{$search}%")
                            ->orWhere('data->body', 'like', "%{$search}%");
                    })
                    ->description(fn (DatabaseNotification $record): string => (string) data_get($record->data, 'body', 'No details available.'))
                    ->wrap(),
                TextColumn::make('data.client_name')
                    ->label('Client')
                    ->placeholder('-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('data->client_name', 'like', "%{$search}%");
                    }),
                TextColumn::make('data.category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'billing' => 'danger',
                        'account' => 'warning',
                        'runtime' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('data.severity')
                    ->label('Severity')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'normal' => 'success',
                        default => 'gray',
                    }),
                ToggleColumn::make('read_at')
                    ->label('Read')
                    ->getStateUsing(fn (DatabaseNotification $record): bool => $record->read_at !== null)
                    ->updateStateUsing(function (DatabaseNotification $record, bool $state): bool {
                        if ($state) {
                            $record->markAsRead();
                        } else {
                            $record->forceFill(['read_at' => null])->save();
                        }

                        $this->logNotificationStateChange($record, $state);

                        return $state;
                    }),
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                TextColumn::make('data.url')
                    ->label('Open')
                    ->formatStateUsing(fn (): string => 'View')
                    ->url(fn (DatabaseNotification $record): ?string => data_get($record->data, 'url'))
                    ->openUrlInNewTab(false),
            ])
            ->filters([
                TernaryFilter::make('read_state')
                    ->label('Read State')
                    ->placeholder('All')
                    ->trueLabel('Read')
                    ->falseLabel('Unread')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('read_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('read_at'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                SelectFilter::make('category')
                    ->options([
                        'billing' => 'Billing',
                        'account' => 'Account',
                        'runtime' => 'Runtime',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('data->category', $data['value'])
                        : $query),
            ])
            ->selectable()
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->toolbarActions([
                BulkAction::make('deleteSelected')
                    ->label('Delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $this->deleteRecords($records))
                    ->extraAttributes([
                        'x-cloak' => true,
                        'x-show' => 'getSelectedRecordsCount() === 1',
                    ])
                    ->deselectRecordsAfterCompletion(),
                BulkActionGroup::make([
                    BulkAction::make('bulkRead')
                        ->label('Mark All Read')
                        ->icon(Heroicon::OutlinedEnvelopeOpen)
                        ->action(fn (Collection $records) => $this->markRecordsAsRead($records))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulkUnread')
                        ->label('Mark All Unread')
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->action(fn (Collection $records) => $this->markRecordsAsUnread($records))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulkDelete')
                        ->label('Delete All')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $this->deleteRecords($records))
                        ->deselectRecordsAfterCompletion(),
                ])
                    ->label('Bulk action')
                    ->extraAttributes([
                        'x-cloak' => true,
                        'x-show' => 'getSelectedRecordsCount() > 1',
                    ]),
            ])
            ->emptyStateHeading('No notifications yet')
            ->emptyStateDescription('Billing and runtime alerts will appear here.');
    }

    protected function getNotificationsQuery(): Builder
    {
        $user = Filament::auth()->user();

        abort_unless($user, 403);

        return $user->notifications()
            ->getQuery()
            ->where('type', SuperAdminAlertDatabaseNotification::class);
    }

    protected function markRecordsAsRead(Collection $records): void
    {
        $records->each(function (DatabaseNotification $record): void {
            if ($record->read_at === null) {
                $record->markAsRead();
                $this->logNotificationStateChange($record, true);
            }
        });

        Notification::make()
            ->success()
            ->title('Notifications updated')
            ->body('Selected notifications were marked as read.')
            ->send();
    }

    protected function markRecordsAsUnread(Collection $records): void
    {
        $records->each(function (DatabaseNotification $record): void {
            if ($record->read_at !== null) {
                $record->forceFill(['read_at' => null])->save();
                $this->logNotificationStateChange($record, false);
            }
        });

        Notification::make()
            ->success()
            ->title('Notifications updated')
            ->body('Selected notifications were marked as unread.')
            ->send();
    }

    protected function deleteRecords(Collection $records): void
    {
        $deletedCount = $records->count();
        $records->each->delete();

        app(ActivityLogService::class)->log(
            event: 'super_admin.notifications.bulk_deleted',
            description: 'Selected super admin notifications were deleted.',
            category: 'admin',
            user: Filament::auth()->user(),
            meta: [
                'summary' => $deletedCount.' notification(s) deleted.',
            ],
        );

        Notification::make()
            ->success()
            ->title('Notifications deleted')
            ->body('Selected notifications were deleted.')
            ->send();
    }

    protected function logNotificationStateChange(DatabaseNotification $notification, bool $isRead): void
    {
        app(ActivityLogService::class)->log(
            event: $isRead ? 'super_admin.notification.read' : 'super_admin.notification.unread',
            description: $isRead
                ? 'A super admin notification was marked as read.'
                : 'A super admin notification was marked as unread.',
            category: 'admin',
            user: Filament::auth()->user(),
            meta: [
                'summary' => (string) data_get($notification->data, 'title', 'Notification'),
            ],
        );
    }
}
