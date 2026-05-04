<?php

namespace App\Filament\Resources\KnowledgeFiles;

use App\Filament\Resources\KnowledgeFiles\Pages\ManageKnowledgeFiles;
use App\Models\KnowledgeFile;
use App\Services\KnowledgeService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KnowledgeFileResource extends Resource
{
    protected static ?string $model = KnowledgeFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Knowledge';

    public static function canViewAny(): bool
    {
        return ! Filament::auth()->user()?->isSuperAdmin();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->label('File Type')
                    ->toggleable(),
                TextColumn::make('size')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ingested_at')
                    ->since()
                    ->label('Ingested'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'ready' => 'Ready',
                        'failed' => 'Failed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('process')
                    ->label('Process')
                    ->icon(Heroicon::OutlinedCog8Tooth)
                    ->color('primary')
                    ->visible(fn (KnowledgeFile $record): bool => in_array($record->status, ['pending', 'failed'], true))
                    ->requiresConfirmation()
                    ->action(function (KnowledgeFile $record, KnowledgeService $knowledgeService): void {
                        $agent = auth()->user()?->agent;

                        abort_unless($agent && $agent->id === $record->agent_id, 403);

                        $knowledgeService->processKnowledgeFile($record, [
                            'widget_token' => $agent->widget_token,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Knowledge file processed')
                            ->body('The knowledge file is now ready for retrieval.')
                            ->send();
                    }),
                Action::make('download')
                    ->label('Download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(function (KnowledgeFile $record) {
                        abort_unless(auth()->user()?->agent_id === $record->agent_id, 403);

                        return Storage::disk($record->disk)->download($record->path, $record->original_name);
                    }),
                DeleteAction::make()
                    ->label('Delete')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Knowledge file deleted')
                    ->action(function (KnowledgeFile $record, KnowledgeService $knowledgeService): void {
                        $agent = auth()->user()?->agent;

                        abort_unless($agent && $agent->id === $record->agent_id, 403);

                        $knowledgeService->deleteKnowledgeFile($record, $agent);

                        Notification::make()
                            ->success()
                            ->title('Knowledge file deleted')
                            ->body('The uploaded file and its processed artifacts were removed.')
                            ->send();
                    }),
                Action::make('editAdditionalInfo')
                    ->label('View')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->visible(fn (KnowledgeFile $record): bool => ($record->meta['source'] ?? null) === 'additional_info')
                    ->modalHeading('Additional Info')
                    ->modalWidth(Width::Medium)
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
                    ])
                    ->fillForm(fn (KnowledgeFile $record): array => [
                        'title' => data_get($record->meta, 'title') ?: pathinfo($record->original_name, PATHINFO_FILENAME),
                        'description' => data_get($record->meta, 'description') ?: '',
                    ])
                    ->modalSubmitActionLabel('Save')
                    ->action(function (KnowledgeFile $record, array $data, KnowledgeService $knowledgeService): void {
                        $agent = auth()->user()?->agent;

                        abort_unless($agent && $agent->id === $record->agent_id, 403);

                        $knowledgeService->updateTextKnowledge($record, $agent, $data['title'], $data['description']);

                        Notification::make()
                            ->success()
                            ->title('Additional info updated')
                            ->body('The changes were saved and reprocessed for chat retrieval.')
                            ->send();
                    }),
                Action::make('previewKnowledge')
                    ->label('View')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->visible(fn (KnowledgeFile $record): bool => ($record->meta['source'] ?? null) !== 'additional_info')
                    ->modalHeading('Document Preview')
                    ->modalWidth(Width::Medium)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(fn (KnowledgeFile $record, KnowledgeService $knowledgeService) => view('filament.infolists.knowledge-document-preview', [
                        'record' => $record,
                        'preview' => $knowledgeService->previewKnowledgeFile($record),
                    ])),
            ])
            ->toolbarActions([
                BulkAction::make('deleteSelected')
                    ->label('Delete Selected')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, KnowledgeService $knowledgeService): void {
                        $agent = auth()->user()?->agent;

                        abort_unless($agent, 403);

                        $records->each(function (KnowledgeFile $record) use ($agent, $knowledgeService): void {
                            abort_unless($record->agent_id === $agent->id, 403);

                            $knowledgeService->deleteKnowledgeFile($record, $agent);
                        });

                        Notification::make()
                            ->success()
                            ->title('Knowledge files deleted')
                            ->body('The selected files and their processed artifacts were removed.')
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->agent_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('agent_id', $user->agent_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageKnowledgeFiles::route('/'),
        ];
    }
}
