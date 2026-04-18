<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class WorkspaceUsersTable extends TableWidget
{
    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()
                ->where('agent_id', auth()->user()?->agent_id ?? 0)
                ->latest())
            ->description('Users currently attached to this company workspace.')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->since()
                    ->placeholder('Not verified'),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5])
            ->emptyStateHeading('No workspace users yet')
            ->emptyStateDescription('Users assigned to this company will appear here.');
    }
}
