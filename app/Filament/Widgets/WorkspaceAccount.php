<?php

namespace App\Filament\Widgets;

use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class WorkspaceAccount extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'md' => 4,
        'xl' => 4,
    ];

    protected string $view = 'filament.widgets.workspace-account';

    public static function canView(): bool
    {
        return auth()->check() && ! auth()->user()?->isSuperAdmin();
    }

    public function toggleWorkspaceActive(): void
    {
        $agent = auth()->user()?->agent;

        if (! $agent) {
            return;
        }

        $agent->update([
            'is_active' => ! $agent->is_active,
        ]);

        Notification::make()
            ->success()
            ->title($agent->fresh()->is_active ? 'Workspace activated' : 'Workspace deactivated')
            ->send();
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
            'supportContact' => config('mail.from.address', 'support@k-agent.test'),
            'widgetPreviewUrl' => $agent ? url('/widget/'.$agent->widget_token.'/preview') : null,
        ];
    }
}
