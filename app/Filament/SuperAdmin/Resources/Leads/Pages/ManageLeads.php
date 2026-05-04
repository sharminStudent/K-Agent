<?php

namespace App\Filament\SuperAdmin\Resources\Leads\Pages;

use App\Filament\SuperAdmin\Resources\Leads\LeadResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLeads extends ManageRecords
{
    protected static string $resource = LeadResource::class;

    protected ?string $heading = 'Leads';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
