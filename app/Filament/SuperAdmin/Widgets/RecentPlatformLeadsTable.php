<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Lead;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentPlatformLeadsTable extends TableWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Lead::query()
                ->with(['agent', 'chatSession'])
                ->latest())
            ->description('Recently captured leads across all company workspaces.')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('agent.company_name')
                    ->label('Company')
                    ->searchable()
                    ->placeholder('Unknown company'),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : 'Unknown')
                    ->color(fn (?string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted' => 'warning',
                        'qualified' => 'success',
                        'closed' => 'gray',
                        default => 'danger',
                    }),
                TextColumn::make('chatSession.public_id')
                    ->label('Session')
                    ->placeholder('No session')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Captured')
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5])
            ->emptyStateHeading('No platform leads yet')
            ->emptyStateDescription('Leads captured from client chat flows will appear here.');
    }
}
