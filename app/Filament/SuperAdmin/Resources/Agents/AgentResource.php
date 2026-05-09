<?php

namespace App\Filament\SuperAdmin\Resources\Agents;

use App\Filament\SuperAdmin\Resources\Agents\Pages\EditAgent;
use App\Filament\SuperAdmin\Resources\Agents\Pages\ManageAgentPayments;
use App\Filament\SuperAdmin\Resources\Agents\Pages\ManageAgents;
use App\Filament\SuperAdmin\Resources\Agents\Pages\ViewAgent;
use App\Models\Agent;
use App\Support\BahrainPhone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Password;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $modelLabel = 'Client';

    protected static ?string $pluralModelLabel = 'Clients';

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Clients';

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    protected static ?string $slug = 'clients';

    public static function canViewAny(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Details')
                    ->schema([
                        TextInput::make('company_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label('Agent Name')
                            ->required(fn (string $operation): bool => $operation !== 'create')
                            ->hidden(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->maxLength(255),
                        TextInput::make('website_url')
                            ->url()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->inline(false)
                            ->default(true),
                        TextInput::make('contact_email')
                            ->label('Contact / Login Email')
                            ->email()
                            ->required(fn (string $operation, ?Agent $record): bool => $operation === 'create' || $record?->primaryUser === null)
                            ->hidden(fn (string $operation): bool => $operation === 'edit')
                            ->helperText('This email is used for the client login account.')
                            ->maxLength(255),
                        TextInput::make('support_email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('support_phone')
                            ->tel()
                            ->prefix('+973')
                            ->inputMode('numeric')
                            ->minLength(8)
                            ->maxLength(8)
                            ->placeholder('12345678')
                            ->helperText('Enter 8 digits. Bahrain country code `+973` is added automatically.')
                            ->rule('regex:/^\d{8}$/')
                            ->formatStateUsing(fn (?string $state): ?string => BahrainPhone::localDigits($state))
                            ->dehydrateStateUsing(fn (?string $state): ?string => BahrainPhone::normalizeForStorage($state)),
                        TextInput::make('password')
                            ->label('Client Password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->rule(Password::defaults())
                            ->helperText(fn (string $operation): string => match ($operation) {
                                'create' => 'Set the initial password for the client login.',
                                'edit' => 'Client passwords are reset from the dedicated reset action.',
                                default => 'Passwords are stored securely and cannot be viewed after saving.',
                            }),
                        TextInput::make('password_status')
                            ->label('Saved Password')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => in_array($operation, ['view', 'edit'], true))
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Client passwords are stored securely and cannot be viewed. Enter a new password above only if you want to reset it.'
                                : 'Client passwords are stored securely and cannot be viewed after saving.'),
                        Textarea::make('welcome_message')
                            ->rows(3)
                            ->columnSpan(4),
                        Textarea::make('fallback_message')
                            ->rows(3)
                            ->columnSpan(4),
                    ])
                    ->columnSpanFull()
                    ->columns(4),
                Section::make('Billing and Usage')
                    ->schema([
                        Select::make('payment_status')
                            ->options([
                                Agent::PAYMENT_STATUS_TRIAL => 'Trial',
                                Agent::PAYMENT_STATUS_ACTIVE => 'Active',
                                Agent::PAYMENT_STATUS_PAST_DUE => 'Past Due',
                                Agent::PAYMENT_STATUS_CANCELED => 'Canceled',
                                Agent::PAYMENT_STATUS_SUSPENDED => 'Suspended',
                            ])
                            ->helperText('Only Trial and Active companies can access the workspace and widget.')
                            ->placeholder('Select billing status'),
                        TextInput::make('monthly_token_limit')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('monthly_chat_count')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('monthly_lead_count')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('monthly_token_count')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columnSpanFull()
                    ->columns(4),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Summary')
                    ->schema([
                        TextEntry::make('company_name'),
                        TextEntry::make('name')
                            ->label('Agent Name'),
                        TextEntry::make('slug'),
                        TextEntry::make('widget_token')
                            ->copyable(),
                        TextEntry::make('contact_email'),
                        TextEntry::make('support_email'),
                        TextEntry::make('support_phone'),
                        TextEntry::make('website_url'),
                        TextEntry::make('is_active')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('welcome_message')
                            ->columnSpan(4),
                        TextEntry::make('fallback_message')
                            ->columnSpan(4),
                        TextEntry::make('payment_status')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->headline()->toString() : 'Unassigned')
                            ->color(fn (?string $state): string => match ($state) {
                                'trial' => 'warning',
                                'active' => 'success',
                                'past_due' => 'danger',
                                'canceled' => 'gray',
                                default => filled($state) ? 'info' : 'gray',
                            }),
                        TextEntry::make('monthly_token_limit'),
                        TextEntry::make('monthly_chat_count'),
                        TextEntry::make('monthly_lead_count'),
                        TextEntry::make('monthly_token_count'),
                    ])
                    ->columnSpanFull()
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->searchable()
                    ->description(fn (Agent $record): string => $record->name),
                TextColumn::make('chat_sessions_count')
                    ->counts('chatSessions')
                    ->label('Chats'),
                TextColumn::make('leads_count')
                    ->counts('leads')
                    ->label('Leads'),
                TextColumn::make('knowledge_files_count')
                    ->counts('knowledgeFiles')
                    ->label('Knowledge'),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
            ])
            ->filters([
                SelectFilter::make('created_range')
                    ->label('Created')
                    ->options([
                        'this_week' => 'This week',
                        'this_month' => 'This month',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'this_week' => $query->where('created_at', '>=', now()->startOfWeek()),
                            'this_month' => $query->where('created_at', '>=', now()->startOfMonth()),
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
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Agent $record): string => static::getUrl('view', ['record' => $record])),
                Action::make('edit')
                    ->label('Edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Agent $record): string => static::getUrl('edit', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activateSelected')
                        ->label('Set Active')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each(fn (Agent $record): bool => $record->update([
                                'is_active' => true,
                            ]));

                            Notification::make()
                                ->success()
                                ->title('Clients updated')
                                ->body('The selected clients were set to active.')
                                ->send();
                        }),
                    BulkAction::make('deactivateSelected')
                        ->label('Set Inactive')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each(fn (Agent $record): bool => $record->update([
                                'is_active' => false,
                            ]));

                            Notification::make()
                                ->success()
                                ->title('Clients updated')
                                ->body('The selected clients were set to inactive.')
                                ->send();
                        }),
                ])
                    ->label('Bulk action'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['users', 'chatSessions', 'leads', 'knowledgeFiles']);
    }

    public static function streamCsvExport()
    {
        $fileName = 'clients-'.now()->format('Y-m-d-H-i-s').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Company Name',
                'Agent Name',
                'Contact Email',
                'Support Email',
                'Support Phone',
                'Website URL',
                'Payment Status',
                'Monthly Token Limit',
                'Monthly Chats',
                'Monthly Leads',
                'Monthly Tokens',
                'Chat Sessions',
                'Leads',
                'Knowledge Files',
                'Status',
                'Updated At',
                'Created At',
            ]);

            static::getEloquentQuery()
                ->orderByDesc('updated_at')
                ->cursor()
                ->each(function (Agent $record) use ($handle): void {
                    fputcsv($handle, [
                        $record->company_name,
                        $record->name,
                        $record->contact_email,
                        $record->support_email,
                        $record->support_phone,
                        $record->website_url,
                        filled($record->payment_status) ? str($record->payment_status)->headline()->toString() : 'Unassigned',
                        $record->monthly_token_limit,
                        $record->monthly_chat_count,
                        $record->monthly_lead_count,
                        $record->monthly_token_count,
                        $record->chat_sessions_count,
                        $record->leads_count,
                        $record->knowledge_files_count,
                        $record->is_active ? 'Active' : 'Inactive',
                        $record->updated_at?->toDateTimeString(),
                        $record->created_at?->toDateTimeString(),
                    ]);
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateRecordDataForForm(array $data, ?Agent $record = null): array
    {
        if ($record?->relationLoaded('primaryUser') || $record?->primaryUser) {
            $data['contact_email'] = $data['contact_email'] ?? $record->primaryUser?->email;
        }

        $data['password'] = null;
        $data['password_status'] = $record?->primaryUser ? '•••••••• (stored securely)' : 'No password set yet';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateProviderDataBeforeSave(array $data, ?Agent $record = null): array
    {
        if (blank($data['name'] ?? null) && filled($data['company_name'] ?? null)) {
            $data['name'] = trim((string) $data['company_name']).' Agent';
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAgents::route('/'),
            'view' => ViewAgent::route('/{record}'),
            'edit' => EditAgent::route('/{record}/edit'),
            'billing' => ManageAgentPayments::route('/{record}/billing'),
        ];
    }
}
