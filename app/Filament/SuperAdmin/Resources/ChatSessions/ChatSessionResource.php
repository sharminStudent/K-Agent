<?php

namespace App\Filament\SuperAdmin\Resources\ChatSessions;

use App\Filament\SuperAdmin\Resources\ChatSessions\Pages\ListClientChatSessions;
use App\Filament\SuperAdmin\Resources\ChatSessions\Pages\ManageChatSessions;
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

class ChatSessionResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Chat Sessions';

    protected static string|\UnitEnum|null $navigationGroup = 'Conversations';

    protected static ?string $slug = 'all-chat-sessions';

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
            ->description('Select a client to view its chat sessions.')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Client')
                    ->searchable()
                    ->description(fn (Agent $record): string => $record->name),
                TextColumn::make('chat_sessions_count')
                    ->label('Chats')
                    ->sortable(),
                TextColumn::make('active_chat_sessions_count')
                    ->label('Active Chats')
                    ->sortable(),
                TextColumn::make('last_chat_message_at')
                    ->label('Last Chat')
                    ->since()
                    ->placeholder('No chats yet')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('chat_range')
                    ->label('Chats')
                    ->options([
                        'today' => 'Today',
                        'this_week' => 'This week',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereHas('chatSessions', fn (Builder $chatQuery): Builder => $chatQuery->whereDate('created_at', today())),
                            'this_week' => $query->whereHas('chatSessions', fn (Builder $chatQuery): Builder => $chatQuery->where('created_at', '>=', now()->startOfWeek())),
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
            ->defaultSort('last_chat_message_at', 'desc')
            ->recordActions([
                Action::make('viewChats')
                    ->label('View Chats')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Agent $record): string => static::getUrl('client-chats', ['record' => $record])),
            ])
            ->emptyStateHeading('No clients available')
            ->emptyStateDescription('Client chat session activity will appear here once companies are created.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('chatSessions')
            ->withCount([
                'chatSessions as active_chat_sessions_count' => fn (Builder $query): Builder => $query->where('status', 'active'),
            ])
            ->withMax(['chatSessions as last_chat_message_at'], 'last_message_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageChatSessions::route('/'),
            'client-chats' => ListClientChatSessions::route('/{record}/client-chats'),
        ];
    }
}
