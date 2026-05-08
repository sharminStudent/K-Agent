<?php

namespace App\Filament\Resources\KnowledgeFiles\Pages;

use App\Filament\Resources\KnowledgeFiles\KnowledgeFileResource;
use App\Services\KnowledgeService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ManageKnowledgeFiles extends ManageRecords
{
    protected static string $resource = KnowledgeFileResource::class;

    protected ?string $heading = 'Knowledge';

    protected ?string $subheading = 'Review knowledge or upload documents for more detailed responses.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadKnowledge')
                ->label('Upload Knowledge')
                ->icon(Heroicon::OutlinedArrowUpTray)
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

                    $processingError = null;

                    if (($data['process_now'] ?? true) === true) {
                        try {
                            $knowledgeService->processKnowledgeFile($knowledgeFile, [
                                'widget_token' => $agent->widget_token,
                            ]);
                        } catch (Throwable $exception) {
                            report($exception);

                            $processingError = $exception->getMessage();
                        }
                    }

                    $notification = Notification::make();

                    if ($processingError !== null) {
                        $notification
                            ->warning()
                            ->title('Knowledge file uploaded, but processing failed')
                            ->body("The file was saved, but processing failed: {$processingError}");
                    } else {
                        $notification
                            ->success()
                            ->title('Knowledge file uploaded')
                            ->body(($data['process_now'] ?? true) ? 'The file was uploaded and processed.' : 'The file was uploaded successfully.');
                    }

                    $notification->send();
                }),
            Action::make('additionalInfo')
                ->label('Additional Info')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->rows(8)
                        ->maxLength(20000),
                    Toggle::make('process_now')
                        ->label('Process immediately after saving')
                        ->default(true),
                ])
                ->action(function (array $data, KnowledgeService $knowledgeService): void {
                    $agent = auth()->user()?->agent;

                    abort_unless($agent, 403);

                    $knowledgeFile = $knowledgeService->storeTextKnowledge([
                        'widget_token' => $agent->widget_token,
                        'meta' => [
                            'source' => 'filament',
                        ],
                    ], $data['title'], $data['description']);

                    if (($data['process_now'] ?? true) === true) {
                        $knowledgeService->processKnowledgeFile($knowledgeFile, [
                            'widget_token' => $agent->widget_token,
                        ]);
                    }

                    Notification::make()
                        ->success()
                        ->title('Additional info saved')
                        ->body(($data['process_now'] ?? true) ? 'The additional info was saved and processed.' : 'The additional info was saved successfully.')
                        ->send();
                }),
        ];
    }
}
