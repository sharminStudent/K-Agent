<?php

namespace App\Filament\SuperAdmin\Resources\Users\Pages;

use App\Filament\SuperAdmin\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected ?string $heading = 'Workspace Users';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
