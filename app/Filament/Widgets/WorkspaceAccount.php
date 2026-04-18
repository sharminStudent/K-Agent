<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WorkspaceAccount extends Widget
{
    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = [
        'md' => 4,
        'xl' => 4,
    ];

    protected string $view = 'filament.widgets.workspace-account';

    public static function canView(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $agent = $user?->agent;

        return [
            'user' => $user,
            'agentName' => $agent?->name,
            'companyName' => $agent?->company_name,
            'statusLabel' => $agent?->is_active ? 'Workspace live' : 'Workspace draft',
            'knowledgeCount' => $agent?->knowledgeFiles()->count() ?? 0,
            'leadCount' => $agent?->leads()->count() ?? 0,
            'chatCount' => $agent?->chatSessions()->count() ?? 0,
        ];
    }
}
