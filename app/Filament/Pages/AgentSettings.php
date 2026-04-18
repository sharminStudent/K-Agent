<?php

namespace App\Filament\Pages;

use App\Models\Agent;
use App\Services\AgentProviderConfigService;
use App\Services\AgentService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
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

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Agent Settings';

    protected static ?int $navigationSort = 2;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected ?Agent $agentRecord = null;

    public static function canAccess(): bool
    {
        return Filament::auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::auth()->check() && Filament::auth()->user()?->agent_id !== null;
    }

    public function mount(): void
    {
        $user = Filament::auth()->user()?->fresh();

        $this->agentRecord = $user?->agent()->first();

        if (! $this->agentRecord) {
            $this->redirect(AgentSetup::getUrl(), navigate: true);
            return;
        }

        $this->form->fill([
            'name' => $this->agentRecord->name,
            'company_name' => $this->agentRecord->company_name,
            'slug' => $this->agentRecord->slug,
            'website_url' => $this->agentRecord->website_url,
            'industry' => $this->agentRecord->industry,
            'company_description' => $this->agentRecord->company_description,
            'logo_path' => $this->agentRecord->logo_path,
            'contact_email' => $this->agentRecord->contact_email,
            'support_email' => $this->agentRecord->support_email,
            'support_phone' => $this->agentRecord->support_phone,
            'system_prompt' => $this->agentRecord->system_prompt,
            'welcome_message' => $this->agentRecord->welcome_message,
            'fallback_message' => $this->agentRecord->fallback_message,
            'settings' => $this->agentSettingsWithoutProviderCredentials(),
            'provider_settings' => app(AgentProviderConfigService::class)->sanitizedProviderSettings($this->agentRecord),
            'is_active' => $this->agentRecord->is_active,
        ]);
    }

    public function getTitle(): string | Htmlable
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
                Section::make('Company Profile')
                    ->schema([
                        \Filament\Forms\Components\FileUpload::make('logo_path')
                            ->label('Company Logo')
                            ->disk('public')
                            ->directory('company-logos')
                            ->image()
                            ->maxSize(2048)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\TextInput::make('name')
                            ->label('Agent Name')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('slug')
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('website_url')
                            ->url()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('industry')
                            ->maxLength(255),
                        \Filament\Forms\Components\Textarea::make('company_description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Support and Messaging')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('contact_email')
                            ->email()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('support_email')
                            ->email()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('support_phone')
                            ->tel()
                            ->maxLength(255),
                        \Filament\Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        \Filament\Forms\Components\Textarea::make('welcome_message')
                            ->rows(3)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Textarea::make('fallback_message')
                            ->rows(3)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Textarea::make('system_prompt')
                            ->rows(6)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\KeyValue::make('settings')
                            ->label('Custom Settings')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Provider Credentials')
                    ->schema([
                        \Filament\Forms\Components\Toggle::make('provider_settings.openai.enabled')
                            ->label('Use Company OpenAI Credentials')
                            ->inline(false)
                            ->default(false)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\TextInput::make('provider_settings.openai.api_key')
                            ->label('OpenAI API Key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder('Leave blank to keep the existing key'),
                        \Filament\Forms\Components\TextInput::make('provider_settings.openai.base_url')
                            ->label('OpenAI Base URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://api.openai.com/v1'),
                        \Filament\Forms\Components\TextInput::make('provider_settings.openai.chat_model')
                            ->label('OpenAI Chat Model')
                            ->maxLength(255)
                            ->placeholder('gpt-5.3'),
                        \Filament\Forms\Components\TextInput::make('provider_settings.openai.embedding_model')
                            ->label('OpenAI Embedding Model')
                            ->maxLength(255)
                            ->placeholder('text-embedding-3-large'),
                        \Filament\Forms\Components\TextInput::make('provider_settings.openai.timeout')
                            ->label('OpenAI Timeout')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(300),
                        \Filament\Forms\Components\Toggle::make('provider_settings.qdrant.enabled')
                            ->label('Use Company Qdrant Credentials')
                            ->inline(false)
                            ->default(false)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\TextInput::make('provider_settings.qdrant.api_key')
                            ->label('Qdrant API Key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder('Leave blank to keep the existing key'),
                        \Filament\Forms\Components\TextInput::make('provider_settings.qdrant.base_url')
                            ->label('Qdrant URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('http://127.0.0.1:6333'),
                        \Filament\Forms\Components\TextInput::make('provider_settings.qdrant.collection')
                            ->label('Qdrant Collection')
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('provider_settings.qdrant.distance')
                            ->label('Qdrant Distance')
                            ->maxLength(50)
                            ->placeholder('Cosine'),
                        \Filament\Forms\Components\TextInput::make('provider_settings.qdrant.timeout')
                            ->label('Qdrant Timeout')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(300),
                        \Filament\Forms\Components\Toggle::make('provider_settings.railway.enabled')
                            ->label('Use Company Railway Credentials')
                            ->inline(false)
                            ->default(false)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\TextInput::make('provider_settings.railway.api_key')
                            ->label('Railway API Token')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder('Leave blank to keep the existing token'),
                        \Filament\Forms\Components\TextInput::make('provider_settings.railway.project_id')
                            ->label('Railway Project ID')
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('provider_settings.railway.environment_id')
                            ->label('Railway Environment ID')
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('provider_settings.railway.service_id')
                            ->label('Railway Service ID')
                            ->maxLength(255),
                    ])
                    ->columns(2),
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
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(AgentService $agentService): void
    {
        $agent = Filament::auth()->user()?->fresh()?->agent()->first();

        abort_unless($agent, 404);

        /** @var array<string, mixed> $state */
        $state = $this->form->getState();

        $agentService->updateAgent($agent, $state);

        Notification::make()
            ->success()
            ->title('Agent settings saved')
            ->body('Your company agent settings have been updated.')
            ->send();

        $this->agentRecord = Filament::auth()->user()?->fresh()?->agent()->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function agentSettingsWithoutProviderCredentials(): array
    {
        $settings = $this->agentRecord?->settings ?? [];

        unset($settings['provider_credentials']);

        return is_array($settings) ? $settings : [];
    }
}
