<?php

namespace App\Filament\Widgets;

use App\Models\ChatSession;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentChatSessionsTable extends TableWidget
{
    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ChatSession::query()
                ->where('agent_id', auth()->user()?->agent_id ?? 0)
                ->withCount(['messages', 'leads'])
                ->latest())
            ->description('Recent visitor conversations for this company workspace.')
            ->columns([
                TextColumn::make('public_id')
                    ->label('Session')
                    ->copyable()
                    ->searchable()
                    ->limit(14),
                TextColumn::make('visitor_name')
                    ->label('Visitor')
                    ->placeholder('Unknown visitor')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('messages_count')
                    ->label('Messages'),
                TextColumn::make('leads_count')
                    ->label('Leads'),
                TextColumn::make('last_message_at')
                    ->since()
                    ->label('Last activity'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5])
            ->emptyStateHeading('No chat sessions yet')
            ->emptyStateDescription('New widget conversations will appear here.');
    }
}
