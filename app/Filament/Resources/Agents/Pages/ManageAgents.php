<?php

namespace App\Filament\Resources\Agents\Pages;

use App\Filament\Pages\AgentSettings;
use App\Filament\Pages\AgentSetup;
use App\Filament\Resources\Agents\AgentResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAgents extends ManageRecords
{
    protected static string $resource = AgentResource::class;

    protected ?string $heading = 'Agent Settings';

    public function mount(): void
    {
        parent::mount();

        $targetUrl = auth()->user()?->agent_id === null
            ? AgentSetup::getUrl()
            : AgentSettings::getUrl();

        $this->redirect($targetUrl, navigate: true);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
