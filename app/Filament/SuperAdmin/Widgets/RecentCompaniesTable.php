<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Agent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentCompaniesTable extends TableWidget
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
            ->query(fn (): Builder => Agent::query()
                ->withCount(['users', 'chatSessions', 'leads', 'knowledgeFiles'])
                ->latest('updated_at'))
            ->description('Recently updated company workspaces across the platform.')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->description(fn (Agent $record): string => $record->name),
                TextColumn::make('users_count')
                    ->label('Clients'),
                TextColumn::make('chat_sessions_count')
                    ->label('Chats'),
                TextColumn::make('knowledge_files_count')
                    ->label('Knowledge'),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([5])
            ->emptyStateHeading('No companies yet')
            ->emptyStateDescription('New workspaces will appear here once companies are created.');
    }
}
