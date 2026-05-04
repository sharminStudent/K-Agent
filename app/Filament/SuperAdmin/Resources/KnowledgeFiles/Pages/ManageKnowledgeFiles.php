<?php

namespace App\Filament\SuperAdmin\Resources\KnowledgeFiles\Pages;

use App\Filament\SuperAdmin\Resources\KnowledgeFiles\KnowledgeFileResource;
use Filament\Resources\Pages\ManageRecords;

class ManageKnowledgeFiles extends ManageRecords
{
    protected static string $resource = KnowledgeFileResource::class;

    protected ?string $heading = 'Knowledge Files';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
