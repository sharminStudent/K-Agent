<?php

namespace App\Filament\SuperAdmin\Resources\Leads\Pages;

use App\Filament\SuperAdmin\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ListClientLeads extends Page implements HasTable
{
    use HasMaxWidth;
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = LeadResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Client Leads';
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->company_name.' Leads';
    }

    public function getBreadcrumb(): string
    {
        return 'Client Leads';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToClients')
                ->label('Back to Clients')
                ->url(LeadResource::getUrl()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lead::query()
                    ->where('agent_id', $this->getRecord()->getKey())
                    ->with(['agent', 'chatSession'])
            )
            ->heading('Leads')
            ->description('Review leads captured for this client, including visitor contact details and source session.')
            ->columns([
                TextColumn::make('name')
                    ->label('Visitor Name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Visitor Email')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('phone')
                    ->label('Visitor Phone')
                    ->placeholder('-')
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
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->since()
                    ->label('Created'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->color('gray')
                    ->modalHeading('Lead Details')
                    ->modalWidth(Width::Medium)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Close'))
                    ->modalContent(fn (Lead $record) => view('filament.modals.lead-view', [
                        'record' => $record,
                    ])),
                EditAction::make()
                    ->label('Edit')
                    ->modalWidth(Width::Medium)
                    ->modalAlignment(Alignment::Start)
                    ->modalHeading('Edit Lead')
                    ->modalCancelAction(fn ($action) => $action->label('Close')),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No leads yet')
            ->emptyStateDescription('This client has not captured any leads yet.');
    }
}
