<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Agent;
use App\Models\ChatSession;
use App\Models\Lead;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PlatformStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Platform Overview';

    protected ?string $description = 'Platform totals plus agent engagement, conversion, and health signals.';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $activeCompanies = Agent::query()->where('is_active', true);
        $totalCompanies = Agent::query()->count();
        $activeCompanyCount = (clone $activeCompanies)->count();
        $newCompaniesThisWeek = Agent::query()
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
        $chatSessionsCount = ChatSession::query()->count();
        $leadCount = Lead::query()->count();
        $leadConversionRate = $chatSessionsCount > 0 ? round(($leadCount / $chatSessionsCount) * 100, 1) : 0;
        $healthyAgentCount = Agent::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('last_error_at')
                    ->orWhere('last_error_at', '<', now()->subDays(7));
            })
            ->count();
        $healthRate = $activeCompanyCount > 0 ? round(($healthyAgentCount / $activeCompanyCount) * 100, 1) : 0;

        return [
            Stat::make('Companies', (string) $totalCompanies)
                ->description($activeCompanyCount.' active workspaces')
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->chart($this->getDailyTrend(Agent::class))
                ->color('primary'),
            Stat::make('Inactive Companies', (string) Agent::query()->where('is_active', false)->count())
                ->description('Currently disabled workspaces')
                ->icon(Heroicon::OutlinedPauseCircle)
                ->chart($this->getDailyTrend(Agent::class, fn (Builder $query): Builder => $query->where('is_active', false)))
                ->color('gray'),
            Stat::make('New Companies This Week', (string) $newCompaniesThisWeek)
                ->description('New workspaces created since '.now()->startOfWeek()->format('M j'))
                ->icon(Heroicon::OutlinedRocketLaunch)
                ->chart($this->getDailyTrend(Agent::class))
                ->color('success'),
            Stat::make('Chat Sessions', (string) ChatSession::query()->count())
                ->description((string) ChatSession::query()->whereDate('created_at', today())->count().' created today')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->chart($this->getDailyTrend(ChatSession::class))
                ->color('warning'),
            Stat::make('Lead Conversion', $leadConversionRate.'%')
                ->description($leadCount.' leads from '.$chatSessionsCount.' total chats')
                ->icon(Heroicon::OutlinedChartBarSquare)
                ->chart($this->getConversionTrend())
                ->color($leadConversionRate >= 10 ? 'primary' : 'gray'),
            Stat::make('Healthy Agents', $healthRate.'%')
                ->description($healthyAgentCount.' active workspaces without recent errors')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->chart($this->getHealthyAgentsTrend())
                ->color($healthRate >= 80 ? 'success' : 'warning'),
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  null|callable(Builder): Builder  $modifyQueryUsing
     * @return array<int, float>
     */
    protected function getDailyTrend(string $modelClass, ?callable $modifyQueryUsing = null): array
    {
        return collect(range(6, 0))
            ->map(function (int $offset) use ($modelClass, $modifyQueryUsing): float {
                $query = $modelClass::query()
                    ->whereDate('created_at', Carbon::now()->subDays($offset)->toDateString());

                if ($modifyQueryUsing) {
                    $query = $modifyQueryUsing($query);
                }

                return (float) $query->count();
            })
            ->all();
    }

    /**
     * @return array<int, float>
     */
    protected function getConversionTrend(): array
    {
        return collect(range(6, 0))
            ->map(function (int $offset): float {
                $date = Carbon::now()->subDays($offset)->toDateString();
                $sessions = ChatSession::query()
                    ->whereDate('created_at', $date)
                    ->count();
                $leads = Lead::query()
                    ->whereDate('created_at', $date)
                    ->count();

                if ($sessions === 0) {
                    return 0.0;
                }

                return round(($leads / $sessions) * 100, 1);
            })
            ->all();
    }

    /**
     * @return array<int, float>
     */
    protected function getHealthyAgentsTrend(): array
    {
        return collect(range(6, 0))
            ->map(function (int $offset): float {
                $date = Carbon::now()->subDays($offset);
                $activeCount = Agent::query()
                    ->where('is_active', true)
                    ->count();

                if ($activeCount === 0) {
                    return 0.0;
                }

                $healthyCount = Agent::query()
                    ->where('is_active', true)
                    ->where(function (Builder $query) use ($date): void {
                        $query
                            ->whereNull('last_error_at')
                            ->orWhere('last_error_at', '<', $date->copy()->subDays(7));
                    })
                    ->count();

                return round(($healthyCount / $activeCount) * 100, 1);
            })
            ->all();
    }
}
