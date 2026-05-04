<?php

namespace App\Filament\SuperAdmin\Resources\ChatSessions\Pages;

use App\Filament\SuperAdmin\Resources\ChatSessions\ChatSessionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageChatSessions extends ManageRecords
{
    protected static string $resource = ChatSessionResource::class;

    protected ?string $heading = 'Chat Sessions';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
