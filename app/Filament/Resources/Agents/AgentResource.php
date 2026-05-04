<?php

namespace App\Filament\Resources\Agents;

use App\Filament\Resources\Agents\Pages\ManageAgents;
use App\Models\Agent;
use App\Services\AgentProviderConfigService;
use App\Support\BahrainPhone;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AgentResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Agent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Agent Settings';

    protected static ?string $modelLabel = 'Agent';

    protected static ?string $pluralModelLabel = 'Agent Settings';

    public static function canViewAny(): bool
    {
        return ! Filament::auth()->user()?->isSuperAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company Profile')
                    ->schema([
                        TextInput::make('name')
                            ->label('Agent Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->maxLength(255),
                        TextInput::make('website_url')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Support and Messaging')
                    ->schema([
                        TextInput::make('contact_email')
                            ->email()
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
                        Toggle::make('is_active')
                            ->default(true),
                        Textarea::make('welcome_message')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('fallback_message')
                            ->rows(3)
                            ->columnSpanFull(),
                        KeyValue::make('settings')
                            ->label('Custom Settings')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Agent')
                    ->searchable(),
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable(),
                TextColumn::make('widget_token')
                    ->label('Widget Token')
                    ->copyable()
                    ->limit(16),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->disabled(fn (?Agent $record): bool => $record === null || $record->id !== auth()->user()?->agent_id),
                TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->mutateRecordDataUsing(fn (array $data, Agent $record): array => static::mutateRecordDataForForm($data, $record))
                    ->mutateDataUsing(fn (array $data, Agent $record): array => static::mutateProviderDataBeforeSave($data, $record)),
            ])
            ->emptyStateHeading('No company agent configured yet')
            ->emptyStateDescription('Create your company agent first, then use this dashboard to manage settings, knowledge, leads, and chat activity.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateRecordDataForForm(array $data, ?Agent $record = null): array
    {
        $settings = $data['settings'] ?? [];

        if (is_array($settings)) {
            unset($settings['provider_credentials']);
        }

        $data['settings'] = $settings;
        $data['provider_settings'] = app(AgentProviderConfigService::class)->sanitizedProviderSettings($record);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateProviderDataBeforeSave(array $data, ?Agent $record = null): array
    {
        $existingSettings = $record?->settings ?? [];
        $providerSettings = $data['provider_settings'] ?? null;

        $data['settings'] = app(AgentProviderConfigService::class)->mergeProviderSettings(
            is_array($data['settings'] ?? null) ? $data['settings'] : $existingSettings,
            is_array($providerSettings) ? $providerSettings : null,
        );

        unset($data['provider_settings']);

        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->agent_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereKey($user->agent_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAgents::route('/'),
        ];
    }
}
