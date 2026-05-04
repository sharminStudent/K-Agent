<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Agent;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class SystemMonitoringStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'System Monitoring';

    protected ?string $description = 'Platform-wide request activity, recent errors, and limit pressure.';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $totalApiRequests = (int) Agent::query()->sum('api_request_count');
        $recentErrorCompanies = Agent::query()
            ->whereNotNull('last_error_at')
            ->where('last_error_at', '>=', now()->subDays(7))
            ->count();
        $chatLimitPressure = Agent::query()
            ->whereNotNull('chat_limit')
            ->whereColumn('monthly_chat_count', '>=', 'chat_limit')
            ->count();
        $leadLimitPressure = Agent::query()
            ->whereNotNull('lead_limit')
            ->whereColumn('monthly_lead_count', '>=', 'lead_limit')
            ->count();

        return [
            Stat::make('API Requests', (string) $totalApiRequests)
                ->description('Tracked request count across all companies')
                ->icon(Heroicon::OutlinedSignal)
                ->chart($this->getApiRequestTrend())
                ->color('primary'),
            Stat::make('Recent Error Companies', (string) $recentErrorCompanies)
                ->description('Companies with an error signal in the last 7 days')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->chart($this->getDateBasedTrend('last_error_at'))
                ->color($recentErrorCompanies > 0 ? 'danger' : 'success'),
            Stat::make('Chat Limit Pressure', (string) $chatLimitPressure)
                ->description('Companies at or above chat usage limit')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->chart($this->getPressureTrend('chat_limit', 'monthly_chat_count'))
                ->color($chatLimitPressure > 0 ? 'warning' : 'success'),
            Stat::make('Lead Limit Pressure', (string) $leadLimitPressure)
                ->description('Companies at or above lead usage limit')
                ->icon(Heroicon::OutlinedUsers)
                ->chart($this->getPressureTrend('lead_limit', 'monthly_lead_count'))
                ->color($leadLimitPressure > 0 ? 'warning' : 'success'),
        ];
    }

    /**
     * @return array<int, float>
     */
    protected function getApiRequestTrend(): array
    {
        $points = collect(range(6, 0))
            ->map(function (int $offset): float {
                return (float) Agent::query()
                    ->whereDate('updated_at', Carbon::now()->subDays($offset)->toDateString())
                    ->sum('api_request_count');
            });

        return $this->normalizeTrend($points);
    }

    /**
     * @return array<int, float>
     */
    protected function getDateBasedTrend(string $column): array
    {
        return collect(range(6, 0))
            ->map(function (int $offset) use ($column): float {
                return (float) Agent::query()
                    ->whereNotNull($column)
                    ->whereDate($column, Carbon::now()->subDays($offset)->toDateString())
                    ->count();
            })
            ->all();
    }

    /**
     * @return array<int, float>
     */
    protected function getPressureTrend(string $limitColumn, string $usageColumn): array
    {
        $points = collect(range(6, 0))
            ->map(function (int $offset) use ($limitColumn, $usageColumn): float {
                return (float) Agent::query()
                    ->whereNotNull($limitColumn)
                    ->whereColumn($usageColumn, '>=', $limitColumn)
                    ->whereDate('updated_at', Carbon::now()->subDays($offset)->toDateString())
                    ->count();
            });

        return $this->normalizeTrend($points);
    }

    /**
     * @param  Collection<int, float>  $points
     * @return array<int, float>
     */
    protected function normalizeTrend(Collection $points): array
    {
        if ($points->sum() > 0) {
            return $points->all();
        }

        return array_fill(0, 7, 0.0);
    }
}
