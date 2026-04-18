<?php

namespace App\Filament\Widgets;

use App\Models\ChatSession;
use App\Models\Lead;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ConversationTrends extends ChartWidget
{
    protected ?string $heading = 'Conversation Trends';

    protected ?string $description = 'Last 7 days of sessions and leads for this company.';

    protected string $color = 'primary';

    protected int | string | array $columnSpan = [
        'md' => 8,
        'xl' => 8,
    ];

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $agentId = auth()->user()?->agent_id;

        if ($agentId === null) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $period = CarbonPeriod::create(now()->subDays(6)->startOfDay(), '1 day', now()->startOfDay());
        $labels = [];
        $sessionData = [];
        $leadData = [];

        foreach ($period as $date) {
            $labels[] = $date->format('M j');
            $sessionData[] = ChatSession::query()
                ->where('agent_id', $agentId)
                ->whereDate('created_at', $date)
                ->count();
            $leadData[] = Lead::query()
                ->where('agent_id', $agentId)
                ->whereDate('created_at', $date)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Chat Sessions',
                    'data' => $sessionData,
                    'borderColor' => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.15)',
                ],
                [
                    'label' => 'Leads',
                    'data' => $leadData,
                    'borderColor' => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.15)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array | RawJs | null
    {
        return [
            'animation' => [
                'duration' => 650,
                'easing' => 'easeOutQuart',
            ],
            'elements' => [
                'line' => [
                    'borderWidth' => 3,
                    'tension' => 0.35,
                ],
                'point' => [
                    'radius' => 0,
                    'hoverRadius' => 5,
                ],
            ],
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'align' => 'start',
                    'labels' => [
                        'boxWidth' => 10,
                        'boxHeight' => 10,
                        'useBorderRadius' => true,
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.12)',
                    ],
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
