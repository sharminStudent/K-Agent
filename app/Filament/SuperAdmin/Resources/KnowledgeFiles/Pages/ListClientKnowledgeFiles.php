<?php

namespace App\Filament\SuperAdmin\Resources\KnowledgeFiles\Pages;

use App\Filament\SuperAdmin\Resources\KnowledgeFiles\KnowledgeFileResource;
use App\Models\KnowledgeFile;
use App\Services\KnowledgeService;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;

class ListClientKnowledgeFiles extends Page implements HasTable
{
    use HasMaxWidth;
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = KnowledgeFileResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Client Knowledge Files';
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->company_name.' Knowledge Files';
    }

    public function getBreadcrumb(): string
    {
        return 'Client Knowledge Files';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToClients')
                ->label('Back to Clients')
                ->url(KnowledgeFileResource::getUrl()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                KnowledgeFile::query()
                    ->where('agent_id', $this->getRecord()->getKey())
                    ->with('agent')
            )
            ->heading('Knowledge Files')
            ->description('Review uploaded knowledge files for this client, including file status and preview access.')
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : 'Unknown')
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'ready' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('size')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->since()
                    ->label('Uploaded'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
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
                        $agent = $record->agent;

                        abort_unless($agent, 404);

                        $knowledgeService->updateTextKnowledge($record, $agent, $data['title'], $data['description']);
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
                Action::make('download')
                    ->label('Download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn (KnowledgeFile $record) => Storage::disk($record->disk)->download($record->path, $record->original_name)),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Delete Selected')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No knowledge files yet')
            ->emptyStateDescription('This client has not uploaded any knowledge files yet.');
    }
}
