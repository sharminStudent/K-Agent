<?php

namespace App\Filament\Resources\KnowledgeFiles;

use App\Filament\Resources\KnowledgeFiles\Pages\ManageKnowledgeFiles;
use App\Models\KnowledgeFile;
use App\Services\KnowledgeService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KnowledgeFileResource extends Resource
{
    protected static ?string $model = KnowledgeFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Knowledge';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Knowledge File')
                    ->schema([
                        TextEntry::make('original_name')
                            ->label('File Name'),
                        TextEntry::make('mime_type'),
                        TextEntry::make('size')
                            ->numeric(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('disk'),
                        TextEntry::make('path')
                            ->columnSpanFull(),
                        TextEntry::make('ingested_at')
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        KeyValueEntry::make('meta')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge(),
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
                ViewAction::make(),
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
