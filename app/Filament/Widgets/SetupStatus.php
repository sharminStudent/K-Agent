<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AgentSettings;
use App\Filament\Pages\AgentSetup;
use App\Filament\Resources\ChatSessions\ChatSessionResource;
use App\Filament\Resources\KnowledgeFiles\KnowledgeFileResource;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Widgets\Widget;

class SetupStatus extends Widget
{
    protected string $view = 'filament.widgets.setup-status';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ! auth()->user()?->isSuperAdmin();
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $agent = $user?->agent;

        return [
            'hasAgent' => $agent !== null,
            'agentName' => $agent?->name,
            'companyName' => $agent?->company_name,
            'knowledgeCount' => $agent?->knowledgeFiles()->count() ?? 0,
            'readyKnowledgeCount' => $agent?->knowledgeFiles()->where('status', 'ready')->count() ?? 0,
            'leadCount' => $agent?->leads()->count() ?? 0,
            'chatCount' => $agent?->chatSessions()->count() ?? 0,
            'agentUrl' => $agent !== null ? AgentSettings::getUrl() : AgentSetup::getUrl(),
            'knowledgeUrl' => KnowledgeFileResource::getUrl(),
            'leadUrl' => LeadResource::getUrl(),
            'chatUrl' => ChatSessionResource::getUrl(),
            'widgetPreviewUrl' => $agent !== null ? url('/widget/'.$agent->widget_token.'/preview') : null,
        ];
    }
}
