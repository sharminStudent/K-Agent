<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Agent;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CompanyHealthTable extends TableWidget
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
            ->query(fn (): Builder => Agent::query()
                ->withCount([
                    'users',
                    'chatSessions',
                    'leads',
                    'knowledgeFiles',
                    'knowledgeFiles as ready_knowledge_files_count' => fn (Builder $query) => $query->where('status', 'ready'),
                ])
                ->latest('updated_at'))
            ->description('Company readiness, usage, and health indicators for the platform.')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->description(fn (Agent $record): string => $record->name),
                SelectColumn::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
                TextColumn::make('subscription_plan')
                    ->label('Plan')
                    ->placeholder('Unassigned')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : 'Unassigned')
                    ->color(fn (?string $state): string => filled($state) ? 'info' : 'gray'),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->placeholder('Unassigned')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : 'Unassigned')
                    ->color(fn (?string $state): string => match ($state) {
                        'trial' => 'warning',
                        'active' => 'success',
                        'past_due' => 'danger',
                        'canceled' => 'gray',
                        default => filled($state) ? 'info' : 'gray',
                    }),
                TextColumn::make('ready_knowledge_files_count')
                    ->label('Knowledge Ready')
                    ->formatStateUsing(fn ($state, Agent $record): string => $state.'/'.$record->knowledge_files_count),
                TextColumn::make('monthly_chat_count')
                    ->label('Chats Used')
                    ->formatStateUsing(fn ($state, Agent $record): string => $record->chat_limit ? $state.'/'.$record->chat_limit : (string) $state),
                TextColumn::make('monthly_lead_count')
                    ->label('Leads Used')
                    ->formatStateUsing(fn ($state, Agent $record): string => $record->lead_limit ? $state.'/'.$record->lead_limit : (string) $state),
                TextColumn::make('api_request_count')
                    ->label('API Requests'),
                TextColumn::make('last_error_at')
                    ->label('Last Error')
                    ->since()
                    ->placeholder('No errors'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([8])
            ->emptyStateHeading('No company health data yet')
            ->emptyStateDescription('Company readiness and usage metrics will appear here as workspaces are used.');
    }
}
