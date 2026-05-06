<?php

namespace App\Filament\Pages;

use App\Models\Agent;
use App\Services\AgentService;
use App\Support\BahrainPhone;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput as FormsTextInput;
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

    protected static ?int $navigationSort = 2;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected ?Agent $agentRecord = null;

    public static function canAccess(): bool
    {
        return Filament::auth()->check() && ! Filament::auth()->user()?->isSuperAdmin();
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
            'contact_email' => $this->agentRecord->contact_email,
            'support_email' => $this->agentRecord->support_email,
            'support_phone' => BahrainPhone::localDigits($this->agentRecord->support_phone),
            'welcome_message' => $this->agentRecord->welcome_message,
            'fallback_message' => $this->agentRecord->fallback_message,
            'privacy_url' => $this->agentRecord->settings['privacy_url'] ?? null,
            'help_center_items' => $this->agentRecord->settings['help_center_items'] ?? [],
            'is_active' => $this->agentRecord->is_active,
        ]);
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
                Section::make('Assistant Persona')
                    ->description('Control how the assistant presents itself for this company workspace.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Assistant Display Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Example: K-Agent')
                            ->helperText('Shown to visitors as the assistant identity.'),
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
                Section::make('Support and Routing')
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
                            ->dehydrateStateUsing(fn (?string $state): ?string => BahrainPhone::normalizeForStorage($state)),
                        TextInput::make('privacy_url')
                            ->label('Privacy Policy URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://example.com/privacy'),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Widget Integration')
                    ->description('Use this company widget token and embed code on your own website only.')
                    ->schema([
                        Placeholder::make('widget_token')
                            ->label('Widget Token')
                            ->content(fn (): string => (string) ($this->agentRecord?->widget_token ?? '-')),
                        FormsTextInput::make('widget_embed_code')
                            ->label('Embed Script')
                            ->dehydrated(false)
                            ->readOnly()
                            ->columnSpanFull()
                            ->formatStateUsing(fn (): string => sprintf(
                                '<script src="%s"></script>',
                                url('/widget/'.($this->agentRecord?->widget_token ?? '').'/embed.js')
                            )),
                    ])
                    ->columns(1),
                Section::make('Help Center Section')
                    ->description('This section will be displayed in the widget Help screen.')
                    ->schema([
                        Repeater::make('help_center_items')
                            ->label('Help Center Articles')
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->addActionLabel('Add help article')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Title')
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->columnSpanFull(),
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
        $helpCenterItems = collect($state['help_center_items'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'title' => trim((string) ($item['title'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
            ]);

        $hasIncompleteHelpCenterItem = $helpCenterItems->contains(
            fn (array $item): bool => $item['title'] === '' || $item['description'] === ''
        );

        $validHelpCenterItems = $helpCenterItems
            ->filter(fn (array $item): bool => $item['title'] !== '' && $item['description'] !== '')
            ->values();

        if ($hasIncompleteHelpCenterItem || $validHelpCenterItems->isEmpty()) {
            Notification::make()
                ->danger()
                ->title('Help Center section is incomplete')
                ->body('Add at least one Help Center item with both a title and description.')
                ->send();

            return;
        }

        $state['settings'] = array_filter([
            'privacy_url' => $state['privacy_url'] ?? null,
            'help_center_items' => $validHelpCenterItems->all(),
        ], fn (mixed $value): bool => filled($value));
        unset($state['privacy_url'], $state['help_center_items']);

        $agentService->updateAgent($agent, $state);

        Notification::make()
            ->success()
            ->title('Agent settings saved')
            ->body('Your company agent settings have been updated.')
            ->send();

        $this->agentRecord = Filament::auth()->user()?->fresh()?->agent()->first();
    }
}
