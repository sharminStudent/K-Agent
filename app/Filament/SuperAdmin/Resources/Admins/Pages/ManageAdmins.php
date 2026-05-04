<?php

namespace App\Filament\SuperAdmin\Resources\Admins\Pages;

use App\Filament\SuperAdmin\Resources\Admins\AdminResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAdmins extends ManageRecords
{
    protected static string $resource = AdminResource::class;

    protected ?string $heading = 'Admins';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
