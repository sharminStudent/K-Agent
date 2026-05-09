<?php

namespace App\Filament\Pages;

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

class CompanyActivityLogs extends Page implements HasTable
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
        return Filament::auth()->check() && ! Filament::auth()->user()?->isSuperAdmin();
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
                    $user = Filament::auth()->user();

                    abort_unless($user?->agent_id !== null, 403);

                    $filename = 'activity-logs-'.now()->format('Y-m-d-His').'.csv';
                    $logs = $this->getFilteredSortedTableQuery()
                        ?->with(['user'])
                        ->get() ?? collect();

                    return response()->streamDownload(function () use ($logs): void {
                        $handle = fopen('php://output', 'w');
                        fwrite($handle, "\xEF\xBB\xBF");

                        fputcsv($handle, [
                            'Time',
                            'Severity',
                            'Status',
                            'Description',
                            'Event',
                            'Actor',
                            'IP Address',
                            'Details',
                        ]);

                        foreach ($logs as $log) {
                            fputcsv($handle, [
                                $log->created_at?->format('m/d/Y h:i:s A'),
                                str($log->severity)->headline()->toString(),
                                str($log->status)->headline()->toString(),
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
        $user = Filament::auth()->user();

        return $table
            ->query(
                ActivityLog::query()
                    ->with(['user'])
                    ->when(
                        $user?->agent_id,
                        fn (Builder $query, int $agentId): Builder => $query->where('agent_id', $agentId),
                        fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
                    )
            )
            ->description('Review recent admin actions and important workspace events for this company.')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                TextColumn::make('severity')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : 'Normal')
                    ->color(fn (?string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : 'Success')
                    ->color(fn (?string $state): string => match ($state) {
                        'failed' => 'danger',
                        default => 'success',
                    }),
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
                SelectFilter::make('severity')
                    ->options([
                        'normal' => 'Normal',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No activity has been recorded yet')
            ->emptyStateDescription('Admin actions and workspace events will appear here.');
    }
}
