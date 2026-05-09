<?php

namespace App\Filament\Pages;

use App\Services\ActivityLogService;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;

class CompanyNotifications extends Page implements HasTable
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
        return Filament::auth()->check() && ! Filament::auth()->user()?->isSuperAdmin();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Notifications';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getNotificationsQuery())
            ->description('Review recent activity and lead alerts for this workspace.')
            ->columns([
                TextColumn::make('data.title')
                    ->label('Notification')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('data->title', 'like', "%{$search}%");
                    })
                    ->description(fn (DatabaseNotification $record): string => (string) data_get($record->data, 'body', 'No details available.'))
                    ->wrap(),
                TextColumn::make('data.lead_name')
                    ->label('Lead')
                    ->placeholder('-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('data->lead_name', 'like', "%{$search}%");
                    }),
                TextColumn::make('data.lead_email')
                    ->label('Email')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('data.chat_session_id')
                    ->label('Session')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', Carbon::now()->startOfWeek())),
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
                    ->visible(fn (self $livewire): bool => count($livewire->selectedTableRecords) === 1)
                    ->action(fn (Collection $records) => $this->deleteRecords($records))
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
                    ->visible(fn (self $livewire): bool => count($livewire->selectedTableRecords) > 1),
            ])
            ->emptyStateHeading('No notifications yet')
            ->emptyStateDescription('Lead alerts and workspace notifications will appear here.');
    }

    protected function getNotificationsQuery(): Builder
    {
        $user = Filament::auth()->user();

        abort_unless($user, 403);

        return $user->notifications()->getQuery();
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
            event: 'admin.notifications.bulk_deleted',
            description: 'Selected notifications were deleted.',
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
            event: $isRead ? 'admin.notification.read' : 'admin.notification.unread',
            description: $isRead
                ? 'A workspace notification was marked as read.'
                : 'A workspace notification was marked as unread.',
            category: 'admin',
            user: Filament::auth()->user(),
            meta: [
                'summary' => (string) data_get($notification->data, 'title', 'Notification'),
            ],
        );
    }
}
