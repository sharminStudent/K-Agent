<?php

namespace App\Filament\Pages;

use App\Services\AiTesterService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property-read Schema $form
 */
class AiTester extends Page
{
    use HasMaxWidth;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?string $navigationLabel = 'AI Tester';

    protected static ?int $navigationSort = 5;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $result = null;

    public static function canAccess(): bool
    {
        return Filament::auth()->check() && Filament::auth()->user()?->agent_id !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        abort_unless(Filament::auth()->user()?->agent_id !== null, 404);

        $this->form->fill([
            'message' => 'What services does the company offer?',
            'scenario' => 'normal',
        ]);
    }

    public function getTitle(): string | Htmlable
    {
        return 'AI Tester';
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
                Section::make('Run AI Test')
                    ->description('Use this page to test normal RAG, guardrail fallback, and forced OpenAI failure handling.')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('message')
                            ->label('Test Message')
                            ->required()
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Select::make('scenario')
                            ->label('Scenario')
                            ->options([
                                'normal' => 'Normal RAG',
                                'no_context' => 'Force Guardrail: No Context',
                                'openai_unconfigured' => 'Force Fallback: OpenAI Unconfigured',
                                'openai_error' => 'Force Fallback: OpenAI Error',
                            ])
                            ->required()
                            ->default('normal'),
                    ])
                    ->columns(1),
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
                    ->livewireSubmitHandler('runTest')
                    ->footer([
                        Actions::make([
                            Action::make('runTest')
                                ->label('Run AI Test')
                                ->submit('runTest'),
                        ]),
                    ]),
                Section::make('Test Result')
                    ->visible(fn (): bool => filled($this->result))
                    ->schema([
                        View::make('filament.pages.ai-tester-results')
                            ->viewData([
                                'result' => $this->result,
                            ]),
                    ]),
            ]);
    }

    public function runTest(AiTesterService $aiTesterService): void
    {
        $agent = Filament::auth()->user()?->fresh()?->agent()->first();

        abort_unless($agent, 404);

        /** @var array<string, mixed> $state */
        $state = $this->form->getState();

        $this->result = $aiTesterService->run(
            $agent,
            (string) ($state['message'] ?? ''),
            (string) ($state['scenario'] ?? 'normal'),
        );

        Notification::make()
            ->success()
            ->title('AI test completed')
            ->body('The tester ran the selected AI scenario.')
            ->send();
    }
}
