<?php

namespace App\Filament\Pages;

use App\Services\AgentService;
use App\Support\BahrainPhone;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
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
class AgentSetup extends Page
{
    use HasMaxWidth;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $navigationLabel = 'Agent Setup';

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Filament::auth()->check() && ! Filament::auth()->user()?->isSuperAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::auth()->check() && Filament::auth()->user()?->agent_id === null;
    }

    public function mount(): void
    {
        if (Filament::auth()->user()?->agent_id !== null) {
            $this->redirect(AgentSettings::getUrl(), navigate: true);

            return;
        }

        $this->form->fill([
            'name' => 'Support Agent',
            'company_name' => Filament::auth()->user()?->name,
            'is_active' => true,
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'One-Time Agent Setup';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Create Your Company Agent';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'This runs once for each company workspace. After setup, you will manage the same agent from Agent Settings.';
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('create')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company Agent')
                    ->description('Set up the single agent used by this company workspace.')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('website_url')
                            ->label('Website URL (optional)')
                            ->maxLength(255)
                            ->placeholder('Add later if needed'),
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
                            ->dehydrateStateUsing(fn (?string $state): ?string => BahrainPhone::normalizeForStorage($state)),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Assistant Persona')
                    ->description('Define how the assistant introduces itself and how it should sound for this company.')
                    ->schema([
                        Placeholder::make('assistant_persona_help')
                            ->hiddenLabel()
                            ->content('Use this section for brand voice and assistant identity. Put company facts, pricing, FAQs, and policies into Knowledge instead of the system prompt.')
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Assistant Display Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Example: K-Agent')
                            ->helperText('This is the assistant name shown to visitors in chat.'),
                        Textarea::make('welcome_message')
                            ->label('Welcome Message')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Example: Hi, I am K-Agent. How can I help you today?'),
                        Textarea::make('fallback_message')
                            ->label('Fallback Message')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Example: I do not have enough confirmed information to answer that yet.'),
                    ]),
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
                                ->label('Create Agent')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(AgentService $agentService): void
    {
        /** @var array<string, mixed> $state */
        $state = $this->form->getState();

        $user = Filament::auth()->user();

        $agentService->createAgent($state, $user);

        $refreshedUser = $user->fresh();

        if ($refreshedUser) {
            Filament::auth()->login($refreshedUser);
            request()->setUserResolver(fn () => $refreshedUser);
        }

        Notification::make()
            ->success()
            ->title('Agent created')
            ->body('Your company agent has been created.')
            ->send();

        $this->redirect(AgentSettings::getUrl(), navigate: true);
    }
}
