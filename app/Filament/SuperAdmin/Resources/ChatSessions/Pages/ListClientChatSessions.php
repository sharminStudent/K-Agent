<?php

namespace App\Filament\SuperAdmin\Resources\ChatSessions\Pages;

use App\Filament\SuperAdmin\Resources\ChatSessions\ChatSessionResource;
use App\Models\ChatSession;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
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

class ListClientChatSessions extends Page implements HasTable
{
    use HasMaxWidth;
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = ChatSessionResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Client Chats';
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->company_name.' Chats';
    }

    public function getBreadcrumb(): string
    {
        return 'Client Chats';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToClients')
                ->label('Back to Clients')
                ->url(ChatSessionResource::getUrl()),
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
                ChatSession::query()
                    ->where('agent_id', $this->getRecord()->getKey())
                    ->with(['agent', 'messages'])
                    ->withCount('messages')
            )
            ->description('Review chat sessions for this client, including visitor details and transcript access.')
            ->columns([
                TextColumn::make('public_id')
                    ->label('Session')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('visitor_name')
                    ->label('Visitor Name')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('visitor_email')
                    ->label('Visitor Email')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('visitor_phone')
                    ->label('Visitor Phone')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : 'Unknown')
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'gray',
                        default => 'danger',
                    }),
                TextColumn::make('messages_count')
                    ->label('Messages')
                    ->sortable(),
                TextColumn::make('last_message_at')
                    ->since()
                    ->label('Last Message'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->color('gray')
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->modalHeading('Chat Transcript')
                    ->modalSubmitAction(false)
                    ->modalContent(fn (ChatSession $record) => view('filament.modals.chat-session-view', [
                        'record' => $record,
                    ]))
                    ->extraModalFooterActions([
                        Action::make('downloadTranscriptFromModal')
                            ->label('Download Transcript')
                            ->icon(Heroicon::OutlinedArrowDownTray)
                            ->color('primary')
                            ->url(fn (ChatSession $record): string => route('admin.chat-sessions.transcript', $record), shouldOpenInNewTab: true),
                    ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Delete Selected')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No chat sessions yet')
            ->emptyStateDescription('This client has not received any widget conversations yet.');
    }
}
