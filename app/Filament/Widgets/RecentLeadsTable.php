<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentLeadsTable extends TableWidget
{
    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Lead::query()
                ->where('agent_id', auth()->user()?->agent_id ?? 0)
                ->with('chatSession')
                ->latest())
            ->description('Recent company leads linked to this workspace.')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('chatSession.public_id')
                    ->label('Session')
                    ->placeholder('No session')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->since()
                    ->label('Captured'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5])
            ->emptyStateHeading('No leads yet')
            ->emptyStateDescription('Leads captured from chat sessions will appear here.');
    }
}
