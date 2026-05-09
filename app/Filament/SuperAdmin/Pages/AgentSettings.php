<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\Agent;
use App\Services\AgentProviderConfigService;
use App\Services\AgentService;
use App\Support\BahrainPhone;
use App\Support\WorkspaceBranding;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property-read Schema $form
 */
class AgentSettings extends Page
{
    use HasMaxWidth;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Agent Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'General Settings';

    protected static ?int $navigationSort = 1;

    protected ?Agent $agentRecord = null;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public function mount(): void
    {
        $this->loadAgent((int) (Agent::query()->orderBy('company_name')->value('id') ?? 0));
    }

    public function getTitle(): string|Htmlable
    {
        return 'Agent Settings';
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Selection')
                    ->description('Choose a client workspace to inspect its branding, runtime settings, and provider connections.')
                    ->schema([
                        Select::make('selected_agent_id')
                            ->label('Client')
                            ->options(fn (): array => Agent::query()
                                ->orderBy('company_name')
                                ->pluck('company_name', 'id')
                                ->all())
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state): void {
                                $this->loadAgent((int) $state);
                            })
                            ->required(),
                        Placeholder::make('workspace_status')
                            ->label('Workspace Status')
                            ->content(fn (): string => $this->agentRecord
                                ? ($this->agentRecord->is_active ? 'Active' : 'Inactive')
                                : 'No client selected'),
                        Placeholder::make('login_user')
                            ->label('Client Login')
                            ->content(fn (): string => $this->agentRecord?->primaryUser?->email ?? 'No login user linked'),
                        Placeholder::make('widget_embed')
                            ->label('Widget Embed')
                            ->content(fn (): string => $this->agentRecord
                                ? sprintf('<script src="%s"></script>', url('/widget/'.$this->agentRecord->widget_token.'/embed.js'))
                                : '-'),
                    ])
                    ->columns(2),
                Section::make('Branding Assets')
                    ->description('Upload the shared K-Agent logos used across every client dashboard, login view, and widget surface.')
                    ->schema([
                        FileUpload::make('platform_branding.light_logo_path')
                            ->label('Light Mode Logo')
                            ->disk('public')
                            ->directory('platform-branding')
                            ->image()
                            ->imageEditor()
                            ->helperText('Shown across all client dashboards and widget light mode.'),
                        FileUpload::make('platform_branding.dark_logo_path')
                            ->label('Dark Mode Logo')
                            ->disk('public')
                            ->directory('platform-branding')
                            ->image()
                            ->imageEditor()
                            ->helperText('Shown across all client dashboards and widget dark mode.'),
                        FileUpload::make('platform_branding.login_logo_path')
                            ->label('Login Logo')
                            ->disk('public')
                            ->directory('platform-branding')
                            ->image()
                            ->imageEditor()
                            ->helperText('Shown on shared admin and super admin login screens.'),
                        Placeholder::make('branding_scope')
                            ->label('Branding Scope')
                            ->content('Any upload here applies to all clients'),
                    ])
                    ->columns(2),
                Section::make('Agent Profile')
                    ->schema([
                        TextInput::make('company_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label('Agent Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->maxLength(255),
                        TextInput::make('website_url')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('industry')
                            ->maxLength(255),
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
                            ->rule('regex:/^\d{8}$/')
                            ->formatStateUsing(fn (?string $state): ?string => BahrainPhone::localDigits($state))
                            ->dehydrateStateUsing(fn (?string $state): ?string => BahrainPhone::normalizeForStorage($state)),
                        Toggle::make('is_active')
                            ->inline(false),
                        Textarea::make('company_description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('system_prompt')
                            ->rows(6)
                            ->columnSpanFull(),
                        Textarea::make('welcome_message')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('fallback_message')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
                Section::make('Provider Connections')
                    ->description('Review and override the connection settings used by this client workspace.')
                    ->schema([
                        Toggle::make('provider_settings.openai.enabled')
                            ->label('Use Client OpenAI Override'),
                        TextInput::make('provider_settings.openai.api_key')
                            ->label('OpenAI API Key')
                            ->dehydrateStateUsing(fn (?string $state): string => filled($state) ? $state : '__keep__')
                            ->helperText('Visible to super admin. Leave blank only if you want to keep the current key unchanged.'),
                        TextInput::make('provider_settings.openai.base_url')
                            ->label('OpenAI Base URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('provider_settings.openai.chat_model')
                            ->label('Chat Model')
                            ->maxLength(255),
                        TextInput::make('provider_settings.openai.embedding_model')
                            ->label('Embedding Model')
                            ->maxLength(255),
                        TextInput::make('provider_settings.openai.timeout')
                            ->numeric()
                            ->minValue(1),
                        Toggle::make('provider_settings.qdrant.enabled')
                            ->label('Use Client Qdrant Override'),
                        TextInput::make('provider_settings.qdrant.api_key')
                            ->label('Qdrant API Key')
                            ->dehydrateStateUsing(fn (?string $state): string => filled($state) ? $state : '__keep__')
                            ->helperText('Visible to super admin. Leave blank only if you want to keep the current key unchanged.'),
                        TextInput::make('provider_settings.qdrant.base_url')
                            ->label('Qdrant URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('provider_settings.qdrant.collection')
                            ->label('Collection')
                            ->maxLength(255),
                        TextInput::make('provider_settings.qdrant.distance')
                            ->label('Distance Metric')
                            ->maxLength(50),
                        TextInput::make('provider_settings.qdrant.timeout')
                            ->numeric()
                            ->minValue(1),
                        Toggle::make('provider_settings.railway.enabled')
                            ->label('Use Client Railway Override'),
                        TextInput::make('provider_settings.railway.api_key')
                            ->label('Railway API Key')
                            ->dehydrateStateUsing(fn (?string $state): string => filled($state) ? $state : '__keep__')
                            ->helperText('Visible to super admin. Leave blank only if you want to keep the current key unchanged.'),
                        TextInput::make('provider_settings.railway.project_id')
                            ->label('Project ID')
                            ->maxLength(255),
                        TextInput::make('provider_settings.railway.environment_id')
                            ->label('Environment ID')
                            ->maxLength(255),
                        TextInput::make('provider_settings.railway.service_id')
                            ->label('Service ID')
                            ->maxLength(255),
                    ])
                    ->columns(3),
                Section::make('Performance Analytics')
                    ->description('Workspace activity and health signals for this client agent.')
                    ->schema([
                        Placeholder::make('analytics_total_chats')
                            ->label('Chat Sessions')
                            ->content(fn (): string => (string) ($this->agentRecord?->chatSessions()->count() ?? 0)),
                        Placeholder::make('analytics_total_leads')
                            ->label('Leads')
                            ->content(fn (): string => (string) ($this->agentRecord?->leads()->count() ?? 0)),
                        Placeholder::make('analytics_total_knowledge')
                            ->label('Knowledge Files')
                            ->content(fn (): string => (string) ($this->agentRecord?->knowledgeFiles()->count() ?? 0)),
                        Placeholder::make('analytics_total_users')
                            ->label('Workspace Users')
                            ->content(fn (): string => (string) ($this->agentRecord?->users()->count() ?? 0)),
                        Placeholder::make('analytics_monthly_chats')
                            ->label('Monthly Chats')
                            ->content(fn (): string => (string) ($this->agentRecord?->monthly_chat_count ?? 0)),
                        Placeholder::make('analytics_monthly_leads')
                            ->label('Monthly Leads')
                            ->content(fn (): string => (string) ($this->agentRecord?->monthly_lead_count ?? 0)),
                        Placeholder::make('analytics_monthly_tokens')
                            ->label('Monthly Tokens')
                            ->content(fn (): string => (string) ($this->agentRecord?->monthly_token_count ?? 0)),
                        Placeholder::make('analytics_request_count')
                            ->label('API Requests')
                            ->content(fn (): string => (string) ($this->agentRecord?->api_request_count ?? 0)),
                        Placeholder::make('analytics_lead_conversion')
                            ->label('Lead Conversion')
                            ->content(fn (): string => $this->leadConversionRate()),
                        Placeholder::make('analytics_last_api')
                            ->label('Last API Request')
                            ->content(fn (): string => $this->agentRecord?->last_api_request_at?->diffForHumans() ?? 'Never'),
                        Placeholder::make('analytics_last_error')
                            ->label('Last Error')
                            ->content(fn (): string => $this->agentRecord?->last_error_at?->diffForHumans() ?? 'No recent errors'),
                        Placeholder::make('analytics_payment')
                            ->label('Billing Status')
                            ->content(fn (): string => filled($this->agentRecord?->payment_status)
                                ? str((string) $this->agentRecord?->payment_status)->headline()->toString()
                                : 'Unassigned'),
                    ])
                    ->columns(4),
                Section::make('Connection Diagnostics')
                    ->description('Resolved runtime configuration so super admin can verify how this agent is connected.')
                    ->schema([
                        Placeholder::make('diag_openai_source')
                            ->label('OpenAI Source')
                            ->content(fn (): string => $this->providerEnabled('openai') ? 'Client override + platform fallback' : 'Platform default'),
                        Placeholder::make('diag_openai_base_url')
                            ->label('Resolved OpenAI Base URL')
                            ->content(fn (): string => app(AgentProviderConfigService::class)->openAiConfig($this->agentRecord)['base_url'] ?? '-'),
                        Placeholder::make('diag_openai_chat_model')
                            ->label('Resolved Chat Model')
                            ->content(fn (): string => app(AgentProviderConfigService::class)->openAiConfig($this->agentRecord)['chat_model'] ?? '-'),
                        Placeholder::make('diag_openai_embedding')
                            ->label('Resolved Embedding Model')
                            ->content(fn (): string => app(AgentProviderConfigService::class)->openAiConfig($this->agentRecord)['embedding_model'] ?? '-'),
                        Placeholder::make('diag_openai_key')
                            ->label('OpenAI Key Configured')
                            ->content(fn (): string => filled(app(AgentProviderConfigService::class)->openAiConfig($this->agentRecord)['api_key'] ?? null) ? 'Yes' : 'No'),
                        Placeholder::make('diag_qdrant_source')
                            ->label('Vector Store Source')
                            ->content(fn (): string => $this->providerEnabled('qdrant') ? 'Client override + platform fallback' : 'Platform default'),
                        Placeholder::make('diag_qdrant_url')
                            ->label('Resolved Qdrant URL')
                            ->content(fn (): string => app(AgentProviderConfigService::class)->qdrantConfig($this->agentRecord)['url'] ?? 'Not configured'),
                        Placeholder::make('diag_qdrant_collection')
                            ->label('Resolved Collection')
                            ->content(fn (): string => app(AgentProviderConfigService::class)->qdrantConfig($this->agentRecord)['collection'] ?? 'agent_knowledge'),
                        Placeholder::make('diag_vector_backend')
                            ->label('Effective Vector Backend')
                            ->content(fn (): string => filled(app(AgentProviderConfigService::class)->qdrantConfig($this->agentRecord)['url'] ?? null) ? 'Qdrant' : 'File fallback'),
                        Placeholder::make('diag_reverb_driver')
                            ->label('Broadcast Driver')
                            ->content(fn (): string => (string) config('broadcasting.default', 'null')),
                        Placeholder::make('diag_reverb_key')
                            ->label('Reverb App Key')
                            ->content(fn (): string => filled(config('broadcasting.connections.reverb.key')) ? 'Configured' : 'Missing'),
                        Placeholder::make('diag_reverb_host')
                            ->label('Reverb Host')
                            ->content(fn (): string => (string) (config('broadcasting.connections.reverb.options.host') ?: request()->getHost())),
                        Placeholder::make('diag_reverb_port')
                            ->label('Reverb Port')
                            ->content(fn (): string => (string) (config('broadcasting.connections.reverb.options.port') ?: 8080)),
                        Placeholder::make('diag_realtime_scheme')
                            ->label('Realtime Scheme')
                            ->content(fn (): string => (string) (config('broadcasting.connections.reverb.options.scheme') ?: 'http')),
                        Placeholder::make('diag_railway_enabled')
                            ->label('Railway Override')
                            ->content(fn (): string => $this->providerEnabled('railway') ? 'Enabled' : 'Disabled'),
                        Placeholder::make('diag_railway_project')
                            ->label('Railway Project ID')
                            ->content(fn (): string => (string) data_get($this->data, 'provider_settings.railway.project_id', '-')),
                        Placeholder::make('diag_railway_environment')
                            ->label('Railway Environment ID')
                            ->content(fn (): string => (string) data_get($this->data, 'provider_settings.railway.environment_id', '-')),
                        Placeholder::make('diag_railway_service')
                            ->label('Railway Service ID')
                            ->content(fn (): string => (string) data_get($this->data, 'provider_settings.railway.service_id', '-')),
                        Placeholder::make('diag_rag_top_k')
                            ->label('RAG Top K')
                            ->content(fn (): string => (string) config('services.rag.top_k')),
                        Placeholder::make('diag_history_messages')
                            ->label('Max History Messages')
                            ->content(fn (): string => (string) config('services.rag.max_history_messages')),
                    ])
                    ->columns(4),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    EmbeddedSchema::make('form'),
                ])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save Settings')
                                ->submit('save')
                                ->disabled(fn (): bool => $this->agentRecord === null),
                        ]),
                    ]),
            ]);
    }

    public function save(AgentService $agentService): void
    {
        /** @var array<string, mixed> $state */
        $state = $this->form->getState();

        $agentId = (int) ($state['selected_agent_id'] ?? 0);
        $agent = Agent::query()->findOrFail($agentId);

        WorkspaceBranding::updateSetting(
            WorkspaceBranding::LIGHT_LOGO_KEY,
            data_get($state, 'platform_branding.light_logo_path'),
        );
        WorkspaceBranding::updateSetting(
            WorkspaceBranding::DARK_LOGO_KEY,
            data_get($state, 'platform_branding.dark_logo_path'),
        );
        WorkspaceBranding::updateSetting(
            WorkspaceBranding::LOGIN_LOGO_KEY,
            data_get($state, 'platform_branding.login_logo_path'),
        );

        $agentService->updateAgent($agent, [
            'company_name' => $state['company_name'] ?? null,
            'name' => $state['name'] ?? null,
            'slug' => $state['slug'] ?? null,
            'website_url' => $state['website_url'] ?? null,
            'industry' => $state['industry'] ?? null,
            'company_description' => $state['company_description'] ?? null,
            'contact_email' => $state['contact_email'] ?? null,
            'support_email' => $state['support_email'] ?? null,
            'support_phone' => $state['support_phone'] ?? null,
            'system_prompt' => $state['system_prompt'] ?? null,
            'welcome_message' => $state['welcome_message'] ?? null,
            'fallback_message' => $state['fallback_message'] ?? null,
            'is_active' => (bool) ($state['is_active'] ?? false),
            'settings' => array_filter([
                'privacy_url' => $state['privacy_url'] ?? null,
            ], fn (mixed $value): bool => filled($value)),
            'provider_settings' => $state['provider_settings'] ?? [],
        ]);

        $this->loadAgent($agent->getKey());

        Notification::make()
            ->success()
            ->title('Agent settings saved')
            ->body('The selected client agent settings were updated.')
            ->send();
    }

    protected function loadAgent(int $agentId): void
    {
        $this->agentRecord = Agent::query()
            ->with('primaryUser')
            ->find($agentId);

        if (! $this->agentRecord) {
            $this->form->fill([
                'selected_agent_id' => null,
            ]);

            return;
        }

        $providerConfigService = app(AgentProviderConfigService::class);
        $providerSettings = $providerConfigService->sanitizedProviderSettings($this->agentRecord);
        $resolvedOpenAi = $providerConfigService->openAiConfig($this->agentRecord);
        $resolvedQdrant = $providerConfigService->qdrantConfig($this->agentRecord);
        $resolvedRailway = $providerConfigService->railwayConfig($this->agentRecord);

        $this->form->fill([
            'selected_agent_id' => $this->agentRecord->getKey(),
            'platform_branding' => [
                'light_logo_path' => WorkspaceBranding::setting(WorkspaceBranding::LIGHT_LOGO_KEY),
                'dark_logo_path' => WorkspaceBranding::setting(WorkspaceBranding::DARK_LOGO_KEY),
                'login_logo_path' => WorkspaceBranding::setting(WorkspaceBranding::LOGIN_LOGO_KEY),
            ],
            'company_name' => $this->agentRecord->company_name,
            'name' => $this->agentRecord->name,
            'slug' => $this->agentRecord->slug,
            'website_url' => $this->agentRecord->website_url,
            'industry' => $this->agentRecord->industry,
            'company_description' => $this->agentRecord->company_description,
            'contact_email' => $this->agentRecord->contact_email,
            'support_email' => $this->agentRecord->support_email,
            'support_phone' => BahrainPhone::localDigits($this->agentRecord->support_phone),
            'system_prompt' => $this->agentRecord->system_prompt,
            'welcome_message' => $this->agentRecord->welcome_message,
            'fallback_message' => $this->agentRecord->fallback_message,
            'is_active' => $this->agentRecord->is_active,
            'provider_settings' => [
                'openai' => [
                    'enabled' => $providerSettings['openai']['enabled'] ?? false,
                    'api_key' => $resolvedOpenAi['api_key'] ?? null,
                    'base_url' => $resolvedOpenAi['base_url'] ?? null,
                    'chat_model' => $resolvedOpenAi['chat_model'] ?? null,
                    'embedding_model' => $resolvedOpenAi['embedding_model'] ?? null,
                    'timeout' => $resolvedOpenAi['timeout'] ?? null,
                ],
                'qdrant' => [
                    'enabled' => $providerSettings['qdrant']['enabled'] ?? false,
                    'api_key' => $resolvedQdrant['api_key'] ?? null,
                    'base_url' => $resolvedQdrant['url'] ?? null,
                    'collection' => $resolvedQdrant['collection'] ?? null,
                    'distance' => $resolvedQdrant['distance'] ?? null,
                    'timeout' => $resolvedQdrant['timeout'] ?? null,
                ],
                'railway' => [
                    'enabled' => $providerSettings['railway']['enabled'] ?? false,
                    'api_key' => $resolvedRailway['api_key'] ?? null,
                    'project_id' => $resolvedRailway['project_id'] ?? null,
                    'environment_id' => $resolvedRailway['environment_id'] ?? null,
                    'service_id' => $resolvedRailway['service_id'] ?? null,
                ],
            ],
        ]);
    }

    protected function providerEnabled(string $provider): bool
    {
        return (bool) data_get($this->data, 'provider_settings.'.$provider.'.enabled', false);
    }

    protected function leadConversionRate(): string
    {
        $chatCount = $this->agentRecord?->chatSessions()->count() ?? 0;
        $leadCount = $this->agentRecord?->leads()->count() ?? 0;

        if ($chatCount === 0) {
            return '0%';
        }

        return round(($leadCount / $chatCount) * 100, 1).'%';
    }
}
