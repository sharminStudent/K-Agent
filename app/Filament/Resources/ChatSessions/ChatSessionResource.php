<?php

namespace App\Filament\Resources\ChatSessions;

use App\Filament\Resources\ChatSessions\Pages\ManageChatSessions;
use App\Models\ChatSession;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ChatSessionResource extends Resource
{
    protected static ?string $model = ChatSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Chat Logs';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session Summary')
                    ->schema([
                        TextEntry::make('public_id')
                            ->label('Session ID')
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('visitor_name')
                            ->label('Visitor Name'),
                        TextEntry::make('visitor_email')
                            ->label('Visitor Email'),
                        TextEntry::make('visitor_phone')
                            ->label('Visitor Phone'),
                        TextEntry::make('last_message_at')
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Transcript')
                    ->schema([
                        RepeatableEntry::make('messages')
                            ->schema([
                                TextEntry::make('role')
                                    ->badge(),
                                TextEntry::make('content')
                                    ->prose()
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_id')
                    ->label('Session')
                    ->searchable()
                    ->copyable()
                    ->description(fn (ChatSession $record): string => trim(implode(' | ', array_filter([
                        $record->visitor_name ?: 'Unknown visitor',
                        $record->visitor_email,
                        $record->visitor_phone,
                    ])))),
                TextColumn::make('visitor_name')
                    ->label('Visitor')
                    ->searchable(),
                TextColumn::make('visitor_email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('visitor_phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('messages_count')
                    ->counts('messages')
                    ->label('Messages'),
                TextColumn::make('leads_count')
                    ->counts('leads')
                    ->label('Leads'),
                TextColumn::make('last_message_at')
                    ->since()
                    ->label('Last Message'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'closed' => 'Closed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('downloadTranscript')
                    ->label('Download Transcript')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (ChatSession $record): string => route('admin.chat-sessions.transcript', $record), shouldOpenInNewTab: true),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['messages'])->withCount(['messages', 'leads']);
        $user = Auth::user();

        if (! $user || $user->agent_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('agent_id', $user->agent_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageChatSessions::route('/'),
        ];
    }
}
