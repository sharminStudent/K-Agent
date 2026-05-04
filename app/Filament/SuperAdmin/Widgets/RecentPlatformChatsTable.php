<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\ChatSession;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentPlatformChatsTable extends TableWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ChatSession::query()
                ->with(['agent'])
                ->withCount(['messages', 'leads'])
                ->latest())
            ->description('Recent chat activity across all client workspaces.')
            ->columns([
                TextColumn::make('agent.company_name')
                    ->label('Company')
                    ->searchable()
                    ->placeholder('Unknown company'),
                TextColumn::make('visitor_name')
                    ->label('Visitor')
                    ->placeholder('Unknown visitor'),
                TextColumn::make('messages_count')
                    ->label('Messages'),
                TextColumn::make('leads_count')
                    ->label('Leads'),
                TextColumn::make('last_message_at')
                    ->label('Last activity')
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5])
            ->emptyStateHeading('No platform chats yet')
            ->emptyStateDescription('Widget conversations from all companies will appear here.');
    }
}
