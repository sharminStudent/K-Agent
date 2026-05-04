<?php

namespace App\Filament\SuperAdmin\Resources\Admins\Pages;

use App\Filament\SuperAdmin\Resources\Admins\AdminResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAdmin extends ViewRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
