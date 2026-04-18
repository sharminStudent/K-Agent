<?php

namespace App\Filament\Pages;

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
class AgentSetup extends Page
{
    use HasMaxWidth;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $navigationLabel = 'Agent Setup';

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Filament::auth()->check();
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

    public function getTitle(): string | Htmlable
    {
        return 'One-Time Agent Setup';
    }

    public function getHeading(): string | Htmlable
    {
        return 'Create Your Company Agent';
    }

    public function getSubheading(): string | Htmlable | null
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
                        \Filament\Forms\Components\TextInput::make('name')
                            ->label('Agent Name')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('website_url')
                            ->label('Website URL (optional)')
                            ->maxLength(255)
                            ->placeholder('Add later if needed'),
                        \Filament\Forms\Components\TextInput::make('industry')
                            ->maxLength(255),
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
                        \Filament\Forms\Components\Textarea::make('company_description')
                            ->rows(4)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Textarea::make('welcome_message')
                            ->rows(3)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Textarea::make('fallback_message')
                            ->rows(3)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Textarea::make('system_prompt')
                            ->rows(6)
                            ->columnSpanFull(),
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
