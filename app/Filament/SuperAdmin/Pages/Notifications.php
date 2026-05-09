<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\SuperAdmin\Resources\Agents\AgentResource;
use App\Models\Agent;
use App\Models\PaymentRecord;
use App\Models\User;
use App\Notifications\SuperAdminAlertDatabaseNotification;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        $this->syncAlerts();
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
                    $this->syncAlerts();

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

    protected function syncAlerts(): void
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $user->isSuperAdmin()) {
            return;
        }

        $alerts = $this->buildAlerts()->keyBy('alert_key');

        $existing = $user->notifications()
            ->where('type', SuperAdminAlertDatabaseNotification::class)
            ->get()
            ->keyBy(fn (DatabaseNotification $notification): string => (string) data_get($notification->data, 'alert_key'));

        $staleKeys = $existing->keys()->diff($alerts->keys());

        if ($staleKeys->isNotEmpty()) {
            $existing->only($staleKeys->all())->each->delete();
        }

        $alerts->each(function (array $payload, string $alertKey) use ($existing, $user): void {
            /** @var DatabaseNotification|null $notification */
            $notification = $existing->get($alertKey);

            if ($notification) {
                $notification->forceFill([
                    'data' => $payload,
                ])->save();

                return;
            }

            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => SuperAdminAlertDatabaseNotification::class,
                'data' => $payload,
            ]);
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildAlerts(): Collection
    {
        return $this->billingAlerts()
            ->concat($this->accountAlerts())
            ->concat($this->runtimeAlerts())
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function billingAlerts(): Collection
    {
        return PaymentRecord::query()
            ->with('agent')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereIn('status', [PaymentRecord::STATUS_PENDING, PaymentRecord::STATUS_FAILED])
            ->orderBy('due_at')
            ->limit(25)
            ->get()
            ->filter(fn (PaymentRecord $record): bool => $record->agent !== null)
            ->map(function (PaymentRecord $record): array {
                return [
                    'alert_key' => 'billing:'.$record->getKey(),
                    'type' => 'billing_overdue',
                    'category' => 'billing',
                    'severity' => $record->status === PaymentRecord::STATUS_FAILED ? 'critical' : 'high',
                    'title' => ($record->agent?->company_name ?? 'Client').' payment is overdue',
                    'body' => sprintf(
                        'Billing record %s was due %s and is still %s.',
                        $record->reference ?: '#'.$record->getKey(),
                        optional($record->due_at)->format('M j, Y g:i A') ?? 'Unknown date',
                        str($record->status)->headline()->toString(),
                    ),
                    'client_id' => $record->agent_id,
                    'client_name' => $record->agent?->company_name,
                    'url' => AgentResource::getUrl('billing', ['record' => $record->agent_id]),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function accountAlerts(): Collection
    {
        return Agent::query()
            ->whereIn('payment_status', [
                Agent::PAYMENT_STATUS_PAST_DUE,
                Agent::PAYMENT_STATUS_SUSPENDED,
                Agent::PAYMENT_STATUS_CANCELED,
            ])
            ->orderBy('company_name')
            ->limit(25)
            ->get()
            ->map(function (Agent $agent): array {
                return [
                    'alert_key' => 'account:'.$agent->getKey().':'.$agent->payment_status,
                    'type' => 'account_attention',
                    'category' => 'account',
                    'severity' => match ($agent->payment_status) {
                        Agent::PAYMENT_STATUS_SUSPENDED => 'critical',
                        Agent::PAYMENT_STATUS_CANCELED => 'high',
                        default => 'high',
                    },
                    'title' => $agent->company_name.' account requires billing attention',
                    'body' => 'Client payment status is currently '.str((string) $agent->payment_status)->headline()->toString().'.',
                    'client_id' => $agent->getKey(),
                    'client_name' => $agent->company_name,
                    'url' => AgentResource::getUrl('edit', ['record' => $agent]),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function runtimeAlerts(): Collection
    {
        return Agent::query()
            ->whereNotNull('last_error_at')
            ->orderByDesc('last_error_at')
            ->limit(15)
            ->get()
            ->map(function (Agent $agent): array {
                return [
                    'alert_key' => 'runtime:'.$agent->getKey().':'.$agent->last_error_at?->timestamp,
                    'type' => 'runtime_error',
                    'category' => 'runtime',
                    'severity' => 'normal',
                    'title' => $agent->company_name.' has recent runtime errors',
                    'body' => 'Last provider/runtime error was recorded '.$agent->last_error_at?->diffForHumans().'.',
                    'client_id' => $agent->getKey(),
                    'client_name' => $agent->company_name,
                    'url' => AgentResource::getUrl('edit', ['record' => $agent]),
                ];
            });
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
