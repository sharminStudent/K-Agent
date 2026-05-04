<?php

namespace App\Filament\SuperAdmin\Resources\KnowledgeFiles;

use App\Filament\SuperAdmin\Resources\KnowledgeFiles\Pages\ListClientKnowledgeFiles;
use App\Filament\SuperAdmin\Resources\KnowledgeFiles\Pages\ManageKnowledgeFiles;
use App\Models\Agent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KnowledgeFileResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Knowledge Files';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace Content';

    protected static ?string $slug = 'all-knowledge-files';

    public static function canViewAny(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Clients')
            ->description('Select a client to view its knowledge files.')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Client')
                    ->searchable()
                    ->description(fn (Agent $record): string => $record->name),
                TextColumn::make('knowledge_files_count')
                    ->label('Files')
                    ->sortable(),
                TextColumn::make('ready_knowledge_files_count')
                    ->label('Ready')
                    ->sortable(),
                TextColumn::make('last_knowledge_uploaded_at')
                    ->label('Last Upload')
                    ->since()
                    ->placeholder('No files yet')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('knowledge_range')
                    ->label('Knowledge')
                    ->options([
                        'today' => 'Today',
                        'this_week' => 'This week',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereHas('knowledgeFiles', fn (Builder $knowledgeQuery): Builder => $knowledgeQuery->whereDate('created_at', today())),
                            'this_week' => $query->whereHas('knowledgeFiles', fn (Builder $knowledgeQuery): Builder => $knowledgeQuery->where('created_at', '>=', now()->startOfWeek())),
                            default => $query,
                        };
                    }),
                SelectFilter::make('sort_by')
                    ->label('Sort')
                    ->options([
                        'company_name_asc' => 'A-Z',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'company_name_asc' => $query->reorder('company_name', 'asc'),
                            default => $query,
                        };
                    }),
            ])
            ->defaultSort('last_knowledge_uploaded_at', 'desc')
            ->recordActions([
                Action::make('viewKnowledgeFiles')
                    ->label('View Files')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Agent $record): string => static::getUrl('client-knowledge-files', ['record' => $record])),
            ])
            ->emptyStateHeading('No clients available')
            ->emptyStateDescription('Client knowledge files will appear here once companies are created.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('knowledgeFiles')
            ->withCount([
                'knowledgeFiles as ready_knowledge_files_count' => fn (Builder $query): Builder => $query->where('status', 'ready'),
            ])
            ->withMax(['knowledgeFiles as last_knowledge_uploaded_at'], 'created_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageKnowledgeFiles::route('/'),
            'client-knowledge-files' => ListClientKnowledgeFiles::route('/{record}/client-knowledge-files'),
        ];
    }
}
