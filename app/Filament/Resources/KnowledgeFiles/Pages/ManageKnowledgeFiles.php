<?php

namespace App\Filament\Resources\KnowledgeFiles\Pages;

use App\Filament\Resources\KnowledgeFiles\KnowledgeFileResource;
use App\Services\KnowledgeService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManageKnowledgeFiles extends ManageRecords
{
    protected static string $resource = KnowledgeFileResource::class;

    protected ?string $heading = 'Knowledge';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadKnowledge')
                ->label('Upload Knowledge')
                ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowUpTray)
                ->schema([
                    FileUpload::make('file')
                        ->label('Knowledge File')
                        ->storeFiles(false)
                        ->required()
                        ->acceptedFileTypes([
                            'application/pdf',
                            'text/plain',
                            'text/csv',
                            'application/json',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/msword',
                        ])
                        ->maxSize(10240),
                    Toggle::make('process_now')
                        ->label('Process immediately after upload')
                        ->default(true),
                ])
                ->action(function (array $data, KnowledgeService $knowledgeService): void {
                    $agent = auth()->user()?->agent;

                    abort_unless($agent, 403);

                    /** @var TemporaryUploadedFile $uploadedFile */
                    $uploadedFile = $data['file'];

                    $knowledgeFile = $knowledgeService->storeUploadedFile([
                        'widget_token' => $agent->widget_token,
                        'meta' => [
                            'source' => 'filament',
                        ],
                    ], $uploadedFile);

                    if (($data['process_now'] ?? true) === true) {
                        $knowledgeService->processKnowledgeFile($knowledgeFile, [
                            'widget_token' => $agent->widget_token,
                        ]);
                    }

                    Notification::make()
                        ->success()
                        ->title('Knowledge file uploaded')
                        ->body(($data['process_now'] ?? true) ? 'The file was uploaded and processed.' : 'The file was uploaded successfully.')
                        ->send();
                }),
        ];
    }
}
