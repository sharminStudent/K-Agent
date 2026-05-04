<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityLogs extends Page implements HasTable
{
    use HasMaxWidth;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static string|UnitEnum|null $navigationGroup = 'General Settings';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.company-activity-logs';

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Activity Logs';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(function () {
                    $filename = 'platform-activity-logs-'.now()->format('Y-m-d-His').'.csv';
                    $logs = $this->getFilteredSortedTableQuery()
                        ?->with(['user', 'agent'])
                        ->get() ?? collect();

                    return response()->streamDownload(function () use ($logs): void {
                        $handle = fopen('php://output', 'w');
                        fwrite($handle, "\xEF\xBB\xBF");

                        fputcsv($handle, [
                            'Time',
                            'Client',
                            'Description',
                            'Event',
                            'Actor',
                            'IP Address',
                            'Details',
                        ]);

                        foreach ($logs as $log) {
                            fputcsv($handle, [
                                $log->created_at?->format('m/d/Y h:i:s A'),
                                $log->agent?->company_name,
                                $log->description,
                                $log->event,
                                $log->user?->name ?? 'System',
                                $log->ip_address,
                                data_get($log->meta, 'summary'),
                            ]);
                        }

                        fclose($handle);
                    }, $filename, [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityLog::query()->with(['user', 'agent']))
            ->description('Review platform-wide activity, including client workspace actions, super admin updates, and system events.')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                TextColumn::make('agent.company_name')
                    ->label('Client')
                    ->placeholder('Platform')
                    ->searchable(),
                TextColumn::make('description')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('event')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Actor')
                    ->default('System')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('meta.summary')
                    ->label('Details')
                    ->wrap()
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('agent_id')
                    ->label('Client')
                    ->relationship('agent', 'company_name'),
                SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'admin' => 'Admin',
                        'security' => 'Security',
                        'system' => 'System',
                        'notification' => 'Notification',
                    ]),
                SelectFilter::make('recent_range')
                    ->label('Recent')
                    ->options([
                        'today' => 'Today',
                        'this_week' => 'This week',
                        'this_month' => 'This month',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereDate('created_at', today()),
                            'this_week' => $query->where('created_at', '>=', now()->startOfWeek()),
                            'this_month' => $query->where('created_at', '>=', now()->startOfMonth()),
                            default => $query,
                        };
                    }),
                SelectFilter::make('actor_type')
                    ->label('Actor')
                    ->options([
                        'user' => 'User Actions',
                        'system' => 'System Events',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'user' => $query->whereNotNull('user_id'),
                            'system' => $query->whereNull('user_id'),
                            default => $query,
                        };
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No activity has been recorded yet')
            ->emptyStateDescription('Super admin actions, workspace changes, and system events will appear here.');
    }
}
