<?php

namespace App\Services;

use App\Filament\SuperAdmin\Resources\Agents\AgentResource;
use App\Models\Agent;
use App\Models\PaymentRecord;
use App\Models\User;
use App\Notifications\SuperAdminAlertDatabaseNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SuperAdminNotificationService
{
    public function sync(): void
    {
        $alerts = $this->buildAlerts()->keyBy('alert_key');

        User::query()
            ->where('is_super_admin', true)
            ->where('is_active', true)
            ->each(function (User $user) use ($alerts): void {
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
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function buildAlerts(): Collection
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
            ->where('due_at', '<=', now())
            ->whereRaw('LOWER(status) in (?, ?)', [
                PaymentRecord::STATUS_PENDING,
                PaymentRecord::STATUS_FAILED,
            ])
            ->orderBy('due_at')
            ->limit(25)
            ->get()
            ->filter(fn (PaymentRecord $record): bool => $record->agent !== null)
            ->map(function (PaymentRecord $record): array {
                $normalizedStatus = strtolower(trim((string) $record->status));

                return [
                    'alert_key' => 'billing:'.$record->getKey(),
                    'type' => 'billing_overdue',
                    'category' => 'billing',
                    'severity' => $normalizedStatus === PaymentRecord::STATUS_FAILED ? 'critical' : 'high',
                    'title' => ($record->agent?->company_name ?? 'Client').' payment is overdue',
                    'body' => sprintf(
                        'Billing record %s was due %s and is still %s.',
                        $record->reference ?: '#'.$record->getKey(),
                        optional($record->due_at)->format('M j, Y g:i A') ?? 'Unknown date',
                        str($normalizedStatus)->headline()->toString(),
                    ),
                    'client_id' => $record->agent_id,
                    'client_name' => $record->agent?->company_name,
                    'url' => AgentResource::getUrl('billing', ['record' => $record->agent_id], panel: 'super-admin'),
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
                    'url' => AgentResource::getUrl('edit', ['record' => $agent], panel: 'super-admin'),
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
                    'url' => AgentResource::getUrl('edit', ['record' => $agent], panel: 'super-admin'),
                ];
            });
    }
}
