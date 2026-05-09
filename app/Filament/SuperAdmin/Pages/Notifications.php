<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\SuperAdmin\Resources\Agents\AgentResource;
use App\Models\Agent;
use App\Models\PaymentRecord;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;

class Notifications extends Page
{
    use HasMaxWidth;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notifications';

    protected static string|UnitEnum|null $navigationGroup = 'General Settings';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.super-admin-notifications';

    protected Width|string|null $maxContentWidth = Width::Full;

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Notifications';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function billingAlerts(): Collection
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
                $status = filled($record->agent?->payment_status)
                    ? str((string) $record->agent?->payment_status)->headline()->toString()
                    : 'Unassigned';

                return [
                    'severity' => $record->status === PaymentRecord::STATUS_FAILED ? 'critical' : 'high',
                    'title' => ($record->agent?->company_name ?? 'Client').' payment is overdue',
                    'body' => sprintf(
                        'Billing record %s was due %s and is still %s. Client payment status: %s.',
                        $record->reference ?: '#'.$record->getKey(),
                        optional($record->due_at)->format('M j, Y g:i A') ?? 'Unknown date',
                        str($record->status)->headline()->toString(),
                        $status,
                    ),
                    'meta' => [
                        'Client' => $record->agent?->company_name ?? '-',
                        'Amount' => number_format((float) $record->amount, 2).' '.strtoupper((string) $record->currency),
                        'Due Date' => optional($record->due_at)->format('M j, Y g:i A') ?? '-',
                    ],
                    'action_label' => 'Open Billing',
                    'action_url' => AgentResource::getUrl('billing', ['record' => $record->agent_id]),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function accountAlerts(): Collection
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
                    'severity' => match ($agent->payment_status) {
                        Agent::PAYMENT_STATUS_SUSPENDED => 'critical',
                        Agent::PAYMENT_STATUS_CANCELED => 'high',
                        default => 'high',
                    },
                    'title' => $agent->company_name.' account requires billing attention',
                    'body' => 'Client payment status is currently '.str((string) $agent->payment_status)->headline()->toString().'.',
                    'meta' => [
                        'Client' => $agent->company_name,
                        'Plan' => filled($agent->subscription_plan) ? str((string) $agent->subscription_plan)->headline()->toString() : 'Unassigned',
                        'Status' => str((string) $agent->payment_status)->headline()->toString(),
                    ],
                    'action_label' => 'Open Client',
                    'action_url' => AgentResource::getUrl('edit', ['record' => $agent]),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function runtimeAlerts(): Collection
    {
        return Agent::query()
            ->whereNotNull('last_error_at')
            ->orderByDesc('last_error_at')
            ->limit(15)
            ->get()
            ->map(function (Agent $agent): array {
                /** @var Carbon|null $lastErrorAt */
                $lastErrorAt = $agent->last_error_at;

                return [
                    'severity' => 'normal',
                    'title' => $agent->company_name.' has recent runtime errors',
                    'body' => 'Last provider/runtime error was recorded '.$lastErrorAt?->diffForHumans().'.',
                    'meta' => [
                        'Client' => $agent->company_name,
                        'Last Error' => $lastErrorAt?->format('M j, Y g:i A') ?? '-',
                        'API Requests' => (string) $agent->api_request_count,
                    ],
                    'action_label' => 'Open Client',
                    'action_url' => AgentResource::getUrl('edit', ['record' => $agent]),
                ];
            });
    }
}
