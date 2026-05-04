<?php

namespace App\Filament\SuperAdmin\Resources\Leads;

use App\Filament\SuperAdmin\Resources\Leads\Pages\ListClientLeads;
use App\Filament\SuperAdmin\Resources\Leads\Pages\ManageLeads;
use App\Models\Agent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?string $navigationLabel = 'Leads';

    protected static string|\UnitEnum|null $navigationGroup = 'Conversations';

    protected static ?string $slug = 'all-leads';

    public static function canViewAny(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Clients')
            ->description('Select a client to view its leads.')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Client')
                    ->searchable()
                    ->description(fn (Agent $record): string => $record->name),
                TextColumn::make('leads_count')
                    ->label('Leads')
                    ->sortable(),
                TextColumn::make('new_leads_count')
                    ->label('New Leads')
                    ->sortable(),
                TextColumn::make('last_lead_created_at')
                    ->label('Last Lead')
                    ->since()
                    ->placeholder('No leads yet')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('lead_range')
                    ->label('Leads')
                    ->options([
                        'today' => 'Today',
                        'this_week' => 'This week',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereHas('leads', fn (Builder $leadQuery): Builder => $leadQuery->whereDate('created_at', today())),
                            'this_week' => $query->whereHas('leads', fn (Builder $leadQuery): Builder => $leadQuery->where('created_at', '>=', now()->startOfWeek())),
                            default => $query,
                        };
                    }),
                SelectFilter::make('sort_by')
                    ->label('Sort')
                    ->options([
                        'company_name_asc' => 'A-Z',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'company_name_asc' => $query->reorder('company_name', 'asc'),
                            default => $query,
                        };
                    }),
            ])
            ->defaultSort('last_lead_created_at', 'desc')
            ->recordActions([
                Action::make('viewLeads')
                    ->label('View Leads')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Agent $record): string => static::getUrl('client-leads', ['record' => $record])),
            ])
            ->emptyStateHeading('No clients available')
            ->emptyStateDescription('Client lead activity will appear here once companies are created.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('leads')
            ->withCount([
                'leads as new_leads_count' => fn (Builder $query): Builder => $query->where('status', 'new'),
            ])
            ->withMax(['leads as last_lead_created_at'], 'created_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLeads::route('/'),
            'client-leads' => ListClientLeads::route('/{record}/client-leads'),
        ];
    }
}
