<?php

namespace App\Filament\SuperAdmin\Resources\Agents\Pages;

use App\Filament\SuperAdmin\Resources\Agents\AgentResource;
use App\Models\PaymentRecord;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ManageAgentPayments extends Page implements HasTable
{
    use HasMaxWidth;
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = AgentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Billing History';
    }

    public function getBreadcrumb(): string
    {
        return 'Billing History';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewCompany')
                ->label('View Client')
                ->url(AgentResource::getUrl('view', ['record' => $this->getRecord()])),
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
                PaymentRecord::query()
                    ->where('agent_id', $this->getRecord()->getKey())
            )
            ->description('Track manual invoices, payments, and account billing notes for this company.')
            ->columns([
                TextColumn::make('paid_at')
                    ->label('Payment Date')
                    ->dateTime('M j, Y g:i A')
                    ->placeholder('Unpaid')
                    ->sortable(),
                TextColumn::make('amount')
                    ->money(fn (PaymentRecord $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : 'Unassigned')
                    ->color(fn (?string $state): string => match ($state) {
                        PaymentRecord::STATUS_PAID => 'success',
                        PaymentRecord::STATUS_PENDING => 'warning',
                        PaymentRecord::STATUS_FAILED => 'danger',
                        PaymentRecord::STATUS_REFUNDED => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('reference')
                    ->placeholder('-')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('billing_period_start')
                    ->label('Billing Start')
                    ->date('M j, Y')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('billing_period_end')
                    ->label('Billing End')
                    ->date('M j, Y')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('notes')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Payment Record')
                    ->model(PaymentRecord::class)
                    ->schema($this->getPaymentFormSchema())
                    ->mutateDataUsing(function (array $data): array {
                        $data['agent_id'] = $this->getRecord()->getKey();

                        return $data;
                    })
                    ->createAnother(false),
            ])
            ->recordActions([
                Action::make('invoice')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(
                        fn (PaymentRecord $record): string => route('super-admin.payment-records.invoice', $record),
                        shouldOpenInNewTab: true
                    ),
                EditAction::make()
                    ->color('gray')
                    ->schema($this->getPaymentFormSchema()),
                DeleteAction::make(),
            ])
            ->defaultSort('paid_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No payment records yet')
            ->emptyStateDescription('Add the company invoices or manual payments here so billing history stays traceable.');
    }

    /**
     * @return array<int, Component>
     */
    protected function getPaymentFormSchema(): array
    {
        return [
            TextInput::make('reference')
                ->label('Reference ID')
                ->disabled()
                ->dehydrated(false)
                ->placeholder('Auto-generated on save'),
            TextInput::make('amount')
                ->numeric()
                ->required()
                ->minValue(0),
            TextInput::make('currency')
                ->default('BHD')
                ->required()
                ->maxLength(3)
                ->minLength(3),
            Select::make('status')
                ->options([
                    PaymentRecord::STATUS_PAID => 'Paid',
                    PaymentRecord::STATUS_PENDING => 'Pending',
                    PaymentRecord::STATUS_FAILED => 'Failed',
                    PaymentRecord::STATUS_REFUNDED => 'Refunded',
                ])
                ->default(PaymentRecord::STATUS_PAID)
                ->required(),
            DatePicker::make('billing_period_start')
                ->label('Billing Period Start'),
            DatePicker::make('billing_period_end')
                ->label('Billing Period End'),
            DateTimePicker::make('paid_at')
                ->label('Payment Date'),
            Textarea::make('notes')
                ->rows(4)
                ->columnSpanFull(),
        ];
    }
}
