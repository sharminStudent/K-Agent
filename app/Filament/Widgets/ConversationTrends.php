<?php

namespace App\Filament\Widgets;

use App\Models\ChatSession;
use App\Models\Lead;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ConversationTrends extends ChartWidget
{
    protected string $view = 'filament.widgets.conversation-trends';

    protected ?string $heading = 'Conversation Trends';

    protected ?string $description = 'Last 7 days of sessions and leads for this company.';

    protected string $color = 'primary';

    protected int|string|array $columnSpan = [
        'md' => 8,
        'xl' => 8,
    ];

    protected ?string $maxHeight = '320px';

    public static function canView(): bool
    {
        return ! auth()->user()?->isSuperAdmin();
    }

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
                    'borderColor' => '#d3033d',
                    'backgroundColor' => 'rgba(211, 3, 61, 0.24)',
                    'pointBackgroundColor' => '#d3033d',
                    'pointBorderColor' => '#ffffff',
                    'pointHoverBackgroundColor' => '#d3033d',
                    'pointHoverBorderColor' => '#ffffff',
                    'fill' => true,
                ],
                [
                    'label' => 'Leads',
                    'data' => $leadData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.24)',
                    'pointBackgroundColor' => '#f59e0b',
                    'pointBorderColor' => '#ffffff',
                    'pointHoverBackgroundColor' => '#f59e0b',
                    'pointHoverBorderColor' => '#ffffff',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, tone: string}>
     */
    public function getTrendSummary(): array
    {
        $data = $this->getCachedData();
        $labels = $data['labels'] ?? [];
        $sessionData = $data['datasets'][0]['data'] ?? [];
        $leadData = $data['datasets'][1]['data'] ?? [];

        $sessionTotal = array_sum($sessionData);
        $leadTotal = array_sum($leadData);
        $conversionRate = $sessionTotal > 0 ? round(($leadTotal / $sessionTotal) * 100, 1) : 0;

        $busiestIndex = 0;
        $busiestValue = -1;

        foreach ($labels as $index => $label) {
            $combined = ($sessionData[$index] ?? 0) + ($leadData[$index] ?? 0);

            if ($combined > $busiestValue) {
                $busiestValue = $combined;
                $busiestIndex = $index;
            }
        }

        return [
            [
                'label' => '7-Day Sessions',
                'value' => (string) $sessionTotal,
                'tone' => 'amber',
            ],
            [
                'label' => '7-Day Leads',
                'value' => (string) $leadTotal,
                'tone' => 'teal',
            ],
            [
                'label' => 'Conversion',
                'value' => $conversionRate.'%',
                'tone' => $conversionRate >= 10 ? 'rose' : 'slate',
            ],
            [
                'label' => 'Busiest Day',
                'value' => $labels[$busiestIndex] ?? 'No data',
                'tone' => 'slate',
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'animation' => [
                'duration' => 650,
                'easing' => 'easeOutQuart',
            ],
            'elements' => [
                'line' => [
                    'borderWidth' => 4,
                    'tension' => 0.44,
                ],
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 7,
                    'hoverBorderWidth' => 3,
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'align' => 'start',
                    'labels' => [
                        'boxWidth' => 10,
                        'boxHeight' => 10,
                        'padding' => 18,
                        'useBorderRadius' => true,
                        'borderRadius' => 999,
                        'font' => [
                            'size' => 12,
                            'weight' => 600,
                        ],
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => '#0f172a',
                    'bodySpacing' => 6,
                    'cornerRadius' => 14,
                    'displayColors' => true,
                    'padding' => 12,
                    'titleMarginBottom' => 8,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'padding' => 10,
                    ],
                    'border' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(211, 3, 61, 0.08)',
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'padding' => 10,
                        'precision' => 0,
                    ],
                    'border' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
