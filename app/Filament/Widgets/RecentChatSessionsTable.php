<?php

namespace App\Filament\Widgets;

use App\Models\ChatSession;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentChatSessionsTable extends TableWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public static function canView(): bool
    {
        return ! auth()->user()?->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ChatSession::query()
                ->where('agent_id', auth()->user()?->agent_id ?? 0)
                ->withCount(['messages', 'leads'])
                ->latest()
                ->limit(5))
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
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : 'Unknown')
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'gray',
                        default => 'danger',
                    }),
                TextColumn::make('messages_count')
                    ->label('Messages'),
                TextColumn::make('leads_count')
                    ->label('Leads'),
                TextColumn::make('last_message_at')
                    ->since()
                    ->label('Last activity'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'closed' => 'Closed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->emptyStateHeading('No chat sessions yet')
            ->emptyStateDescription('New widget conversations will appear here.');
    }
}
