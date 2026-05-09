<?php

namespace App\Filament\Widgets;

use App\Models\ChatSession;
use App\Models\KnowledgeFile;
use App\Models\Lead;
use App\Models\Agent;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class CompanyStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Company Activity';

    protected ?string $description = 'Core signals from your agent workspace.';

    public static function canView(): bool
    {
        return ! auth()->user()?->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $agentId = auth()->user()?->agent_id;

        if ($agentId === null) {
            return [
                Stat::make('Company Agent', 'Not configured')
                    ->description('Create your company agent to unlock dashboard data.')
                    ->icon(Heroicon::OutlinedCog8Tooth)
                    ->color('gray'),
                Stat::make('Leads', '0')
                    ->description('Lead capture starts after setup.')
                    ->icon(Heroicon::OutlinedUsers)
                    ->color('gray'),
                Stat::make('Knowledge Files', '0')
                    ->description('Upload knowledge after company setup.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('gray'),
            ];
        }

        $chatSessions = ChatSession::query()->where('agent_id', $agentId);
        $leads = Lead::query()->where('agent_id', $agentId);
        $knowledgeFiles = KnowledgeFile::query()->where('agent_id', $agentId);
        $agent = Agent::query()->find($agentId);

        $lastSevenDays = Carbon::now()->subDays(7);
        $sessionCount = (clone $chatSessions)->count();
        $leadCount = (clone $leads)->count();
        $leadRate = $sessionCount > 0 ? round(($leadCount / $sessionCount) * 100, 1) : 0;
        $sessionTrend = $this->getTrendData(ChatSession::class, $agentId);
        $leadTrend = $this->getTrendData(Lead::class, $agentId);
        $activeChatTrend = $this->getTrendData(ChatSession::class, $agentId, fn (Builder $query): Builder => $query->where('status', 'active'));
        $knowledgeTrend = $this->getTrendData(KnowledgeFile::class, $agentId);
        $aiPerformance = $this->calculateAiPerformanceScore($sessionCount, $leadRate, (clone $knowledgeFiles)->where('status', 'ready')->count(), (clone $knowledgeFiles)->count(), $agent?->last_error_at);
        $aiPerformanceTrend = $this->buildAiPerformanceTrend($sessionTrend, $leadTrend, $knowledgeTrend, $activeChatTrend);

        return [
            Stat::make('Chat Sessions', (string) $sessionCount)
                ->description((clone $chatSessions)->where('created_at', '>=', $lastSevenDays)->count().' in the last 7 days')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->chart($sessionTrend)
                ->color('warning'),
            Stat::make('Leads', (string) $leadCount)
                ->description((clone $leads)->where('created_at', '>=', $lastSevenDays)->count().' captured in the last 7 days')
                ->icon(Heroicon::OutlinedUsers)
                ->chart($leadTrend)
                ->color('success'),
            Stat::make('Lead Conversion', $leadRate.'%')
                ->description('Leads divided by total chat sessions')
                ->icon(Heroicon::OutlinedChartBarSquare)
                ->chart($leadTrend)
                ->color($leadRate >= 10 ? 'primary' : 'gray'),
            Stat::make('Active Chats', (string) (clone $chatSessions)->where('status', 'active')->count())
                ->description((clone $chatSessions)->where('status', 'active')->where('created_at', '>=', $lastSevenDays)->count().' active in the last 7 days')
                ->icon(Heroicon::OutlinedBolt)
                ->chart($activeChatTrend)
                ->color('primary'),
            Stat::make('Knowledge Files', (string) (clone $knowledgeFiles)->count())
                ->description((clone $knowledgeFiles)->where('status', 'ready')->count().' files processed and ready')
                ->icon(Heroicon::OutlinedDocumentText)
                ->chart($knowledgeTrend)
                ->color('info'),
            Stat::make('AI Performance', $aiPerformance.'%')
                ->description($this->aiPerformanceDescription($aiPerformance, $agent?->last_error_at))
                ->icon(Heroicon::OutlinedCpuChip)
                ->chart($aiPerformanceTrend)
                ->color($aiPerformance >= 80 ? 'success' : ($aiPerformance >= 60 ? 'warning' : 'danger')),
        ];
    }

    protected function calculateAiPerformanceScore(int $sessionCount, float $leadRate, int $readyKnowledgeCount, int $knowledgeCount, mixed $lastErrorAt): int
    {
        $knowledgeScore = $knowledgeCount > 0 ? min(100, (int) round(($readyKnowledgeCount / $knowledgeCount) * 100)) : 70;
        $conversionScore = min(100, (int) round($leadRate * 5));
        $usageScore = $sessionCount > 0 ? min(100, 55 + min(45, $sessionCount * 4)) : 45;
        $stabilityPenalty = $lastErrorAt && Carbon::parse($lastErrorAt)->gte(now()->subDays(7)) ? 25 : 0;

        return max(0, min(100, (int) round(($knowledgeScore * 0.45) + ($conversionScore * 0.3) + ($usageScore * 0.25) - $stabilityPenalty)));
    }

    /**
     * @param  array<int, float>  $sessionTrend
     * @param  array<int, float>  $leadTrend
     * @param  array<int, float>  $knowledgeTrend
     * @param  array<int, float>  $activeChatTrend
     * @return array<int, float>
     */
    protected function buildAiPerformanceTrend(array $sessionTrend, array $leadTrend, array $knowledgeTrend, array $activeChatTrend): array
    {
        return collect(range(0, 6))
            ->map(function (int $index) use ($sessionTrend, $leadTrend, $knowledgeTrend, $activeChatTrend): float {
                $sessions = $sessionTrend[$index] ?? 0;
                $leads = $leadTrend[$index] ?? 0;
                $knowledge = $knowledgeTrend[$index] ?? 0;
                $active = $activeChatTrend[$index] ?? 0;
                $conversionScore = $sessions > 0 ? min(100, ($leads / $sessions) * 100 * 0.5) : 35;
                $usageScore = min(100, 40 + ($sessions * 8));
                $knowledgeScore = min(100, 55 + ($knowledge * 10));
                $activeScore = min(100, 45 + ($active * 9));

                return round((($conversionScore * 0.3) + ($usageScore * 0.25) + ($knowledgeScore * 0.25) + ($activeScore * 0.2)), 1);
            })
            ->all();
    }

    protected function aiPerformanceDescription(int $score, mixed $lastErrorAt): string
    {
        if ($lastErrorAt && Carbon::parse($lastErrorAt)->gte(now()->subDays(7))) {
            return 'Recent provider issues reduced the current score.';
        }

        if ($score >= 80) {
            return 'Healthy response and knowledge coverage.';
        }

        if ($score >= 60) {
            return 'Stable overall, with room to improve.';
        }

        return 'Needs attention on knowledge or reliability.';
    }

    /**
     * @param  class-string<ChatSession|Lead|KnowledgeFile>  $modelClass
     * @param  null|callable(Builder): Builder  $modifyQueryUsing
     * @return array<int, float>
     */
    protected function getTrendData(string $modelClass, int $agentId, ?callable $modifyQueryUsing = null): array
    {
        return collect(range(6, 0))
            ->map(function (int $offset) use ($agentId, $modelClass, $modifyQueryUsing): float {
                $query = $modelClass::query()
                    ->where('agent_id', $agentId)
                    ->whereDate('created_at', now()->subDays($offset)->toDateString());

                if ($modifyQueryUsing) {
                    $query = $modifyQueryUsing($query);
                }

                return (float) $query->count();
            })
            ->all();
    }
}
