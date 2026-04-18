<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AgentSettings;
use App\Filament\Pages\AgentSetup;
use Filament\Widgets\Widget;

class SetupStatus extends Widget
{
    protected string $view = 'filament.widgets.setup-status';

    protected int | string | array $columnSpan = 'full';

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
            'knowledgeUrl' => \App\Filament\Resources\KnowledgeFiles\KnowledgeFileResource::getUrl(),
            'leadUrl' => \App\Filament\Resources\Leads\LeadResource::getUrl(),
            'chatUrl' => \App\Filament\Resources\ChatSessions\ChatSessionResource::getUrl(),
        ];
    }
}
