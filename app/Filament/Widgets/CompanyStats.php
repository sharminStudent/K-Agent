<?php

namespace App\Filament\Widgets;

use App\Models\ChatSession;
use App\Models\KnowledgeFile;
use App\Models\Lead;
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

        $lastSevenDays = Carbon::now()->subDays(7);
        $sessionCount = (clone $chatSessions)->count();
        $leadCount = (clone $leads)->count();
        $leadRate = $sessionCount > 0 ? round(($leadCount / $sessionCount) * 100, 1) : 0;
        $sessionTrend = $this->getTrendData(ChatSession::class, $agentId);
        $leadTrend = $this->getTrendData(Lead::class, $agentId);
        $activeChatTrend = $this->getTrendData(ChatSession::class, $agentId, fn (Builder $query): Builder => $query->where('status', 'active'));
        $qualifiedLeadTrend = $this->getTrendData(Lead::class, $agentId, fn (Builder $query): Builder => $query->where('status', 'qualified'));
        $knowledgeTrend = $this->getTrendData(KnowledgeFile::class, $agentId);

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
            Stat::make('Qualified Leads', (string) (clone $leads)->where('status', 'qualified')->count())
                ->description((clone $leads)->where('status', 'closed')->count().' closed so far')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->chart($qualifiedLeadTrend)
                ->color('success'),
            Stat::make('Knowledge Files', (string) (clone $knowledgeFiles)->count())
                ->description((clone $knowledgeFiles)->where('status', 'ready')->count().' files processed and ready')
                ->icon(Heroicon::OutlinedDocumentText)
                ->chart($knowledgeTrend)
                ->color('info'),
        ];
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
