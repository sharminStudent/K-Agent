<?php

namespace App\Filament\Resources\Agents;

use App\Filament\Resources\Agents\Pages\ManageAgents;
use App\Models\Agent;
use App\Services\AgentProviderConfigService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company Profile')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Company Logo')
                            ->disk('public')
                            ->directory('company-logos')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->columnSpanFull(),
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
                        TextInput::make('industry')
                            ->maxLength(255),
                        Textarea::make('company_description')
                            ->rows(4)
                            ->columnSpanFull(),
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
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->default(true),
                        Textarea::make('welcome_message')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('fallback_message')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('system_prompt')
                            ->rows(6)
                            ->columnSpanFull(),
                        KeyValue::make('settings')
                            ->label('Custom Settings')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Provider Credentials')
                    ->schema([
                        Toggle::make('provider_settings.openai.enabled')
                            ->label('Use Company OpenAI Credentials')
                            ->inline(false)
                            ->default(false)
                            ->columnSpanFull(),
                        Placeholder::make('openai_key_status')
                            ->label('OpenAI Key Status')
                            ->content(fn ($state, ?Agent $record): string => data_get(
                                app(AgentProviderConfigService::class)->sanitizedProviderSettings($record),
                                'openai.has_api_key'
                            ) ? 'API key saved' : 'No API key saved'),
                        TextInput::make('provider_settings.openai.api_key')
                            ->label('OpenAI API Key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder('Leave blank to keep the existing key')
                            ->dehydrated(fn ($state): bool => filled($state)),
                        TextInput::make('provider_settings.openai.base_url')
                            ->label('OpenAI Base URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://api.openai.com/v1'),
                        TextInput::make('provider_settings.openai.chat_model')
                            ->label('OpenAI Chat Model')
                            ->maxLength(255)
                            ->placeholder('gpt-5.3'),
                        TextInput::make('provider_settings.openai.embedding_model')
                            ->label('OpenAI Embedding Model')
                            ->maxLength(255)
                            ->placeholder('text-embedding-3-large'),
                        TextInput::make('provider_settings.openai.timeout')
                            ->label('OpenAI Timeout')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(300),
                        Toggle::make('provider_settings.qdrant.enabled')
                            ->label('Use Company Qdrant Credentials')
                            ->inline(false)
                            ->default(false)
                            ->columnSpanFull(),
                        Placeholder::make('qdrant_key_status')
                            ->label('Qdrant Key Status')
                            ->content(fn ($state, ?Agent $record): string => data_get(
                                app(AgentProviderConfigService::class)->sanitizedProviderSettings($record),
                                'qdrant.has_api_key'
                            ) ? 'API key saved' : 'No API key saved'),
                        TextInput::make('provider_settings.qdrant.api_key')
                            ->label('Qdrant API Key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder('Leave blank to keep the existing key')
                            ->dehydrated(fn ($state): bool => filled($state)),
                        TextInput::make('provider_settings.qdrant.base_url')
                            ->label('Qdrant URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('http://127.0.0.1:6333'),
                        TextInput::make('provider_settings.qdrant.collection')
                            ->label('Qdrant Collection')
                            ->maxLength(255),
                        TextInput::make('provider_settings.qdrant.distance')
                            ->label('Qdrant Distance')
                            ->maxLength(50)
                            ->placeholder('Cosine'),
                        TextInput::make('provider_settings.qdrant.timeout')
                            ->label('Qdrant Timeout')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(300),
                        Toggle::make('provider_settings.railway.enabled')
                            ->label('Use Company Railway Credentials')
                            ->inline(false)
                            ->default(false)
                            ->columnSpanFull(),
                        Placeholder::make('railway_key_status')
                            ->label('Railway Token Status')
                            ->content(fn ($state, ?Agent $record): string => data_get(
                                app(AgentProviderConfigService::class)->sanitizedProviderSettings($record),
                                'railway.has_api_key'
                            ) ? 'API token saved' : 'No API token saved'),
                        TextInput::make('provider_settings.railway.api_key')
                            ->label('Railway API Token')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder('Leave blank to keep the existing token')
                            ->dehydrated(fn ($state): bool => filled($state)),
                        TextInput::make('provider_settings.railway.project_id')
                            ->label('Railway Project ID')
                            ->maxLength(255),
                        TextInput::make('provider_settings.railway.environment_id')
                            ->label('Railway Environment ID')
                            ->maxLength(255),
                        TextInput::make('provider_settings.railway.service_id')
                            ->label('Railway Service ID')
                            ->maxLength(255),
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
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
            ])
            ->recordActions([
                EditAction::make()
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
