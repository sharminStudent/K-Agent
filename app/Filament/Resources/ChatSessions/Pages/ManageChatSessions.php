<?php

namespace App\Filament\Resources\ChatSessions\Pages;

use App\Filament\Resources\ChatSessions\ChatSessionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageChatSessions extends ManageRecords
{
    protected static string $resource = ChatSessionResource::class;

    protected ?string $heading = 'Chat Logs';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
