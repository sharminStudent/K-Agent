<?php

namespace App\Filament\Pages;

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
class EditCompanyProfile extends Page
{
    use HasMaxWidth;

    protected static bool $shouldRegisterNavigation = false;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = 'Edit Profile';

    protected static ?string $navigationParentItem = 'General Settings';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Filament::auth()->check();
    }

    public function mount(): void
    {
        $user = Filament::auth()->user();

        $this->form->fill([
            'admin_name' => $user?->name,
            'admin_email' => $user?->email,
            'admin_phone' => $user?->phone,
            'admin_basic_info' => $user?->basic_info,
        ]);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Edit Profile';
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
                Section::make('Edit Admin Profile')
                    ->description('Update the primary admin account details for this workspace.')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('admin_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('admin_email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('admin_phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255),
                        \Filament\Forms\Components\Textarea::make('admin_basic_info')
                            ->label('Basic Info')
                            ->rows(4)
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
                            Action::make('cancel')
                                ->label('Cancel')
                                ->color('gray')
                                ->url(CompanyProfile::getUrl()),
                            Action::make('save')
                                ->label('Save Profile')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        /** @var array<string, mixed> $state */
        $state = $this->form->getState();

        Filament::auth()->user()?->update([
            'name' => $state['admin_name'] ?? null,
            'email' => $state['admin_email'] ?? null,
            'phone' => $state['admin_phone'] ?? null,
            'basic_info' => $state['admin_basic_info'] ?? null,
        ]);

        Notification::make()
            ->success()
            ->title('Profile saved')
            ->body('Admin profile details have been updated.')
            ->send();

        $this->redirect(CompanyProfile::getUrl(), navigate: true);
    }
}
