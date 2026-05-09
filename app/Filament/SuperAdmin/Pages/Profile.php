<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Services\ActivityLogService;
use App\Support\BahrainPhone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class Profile extends Page
{
    use HasMaxWidth;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Profile';

    protected static string|UnitEnum|null $navigationGroup = 'General Settings';

    protected static ?int $navigationSort = 2;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public bool $passwordChangeUnlocked = false;

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->isSuperAdmin();
    }

    public function mount(): void
    {
        $user = Filament::auth()->user();

        $this->form->fill([
            'name' => $user?->name,
            'email' => $user?->email,
            'phone' => BahrainPhone::localDigits($user?->phone),
            'basic_info' => $user?->basic_info,
            'current_password' => null,
            'new_password' => null,
            'new_password_confirmation' => null,
        ]);

        $this->passwordChangeUnlocked = false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Profile';
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
                Section::make('Super Admin Profile')
                    ->description('Update the account details used to access the super admin control panel.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->prefix('+973')
                            ->inputMode('numeric')
                            ->minLength(8)
                            ->maxLength(8)
                            ->placeholder('12345678')
                            ->helperText('Enter 8 digits. Bahrain country code `+973` is added automatically.')
                            ->rule('regex:/^\d{8}$/'),
                        Textarea::make('basic_info')
                            ->label('Basic Info')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Account Details')
                    ->description('Read-only account information for the currently signed-in super admin.')
                    ->schema([
                        Placeholder::make('account_role')
                            ->label('Role')
                            ->content('Super Admin'),
                        Placeholder::make('account_status')
                            ->label('Status')
                            ->content(fn (): string => Filament::auth()->user()?->is_active ? 'Active' : 'Inactive'),
                        Placeholder::make('email_verification')
                            ->label('Email Verification')
                            ->content(fn (): string => Filament::auth()->user()?->email_verified_at?->format('M j, Y g:i A') ?? 'Not verified'),
                        Placeholder::make('member_since')
                            ->label('Member Since')
                            ->content(fn (): string => Filament::auth()->user()?->created_at?->format('M j, Y g:i A') ?? '-'),
                    ])
                    ->columns(2),
                Section::make('Security')
                    ->description('Change the super admin password when needed.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->revealable()
                            ->extraInputAttributes([
                                'autocomplete' => 'new-password',
                                'data-lpignore' => 'true',
                            ])
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (): void {
                                $this->passwordChangeUnlocked = false;
                                data_set($this->data, 'new_password', null);
                                data_set($this->data, 'new_password_confirmation', null);
                            }),
                        Actions::make([
                            Action::make('verifyCurrentPassword')
                                ->label('Send')
                                ->action('verifyCurrentPassword')
                                ->color('primary'),
                        ])
                            ->columnSpanFull(),
                        TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->extraInputAttributes([
                                'autocomplete' => 'new-password',
                                'data-lpignore' => 'true',
                            ])
                            ->rule(Password::defaults())
                            ->same('new_password_confirmation')
                            ->visible(fn (): bool => $this->passwordChangeUnlocked),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->revealable()
                            ->extraInputAttributes([
                                'autocomplete' => 'new-password',
                                'data-lpignore' => 'true',
                            ])
                            ->visible(fn (): bool => $this->passwordChangeUnlocked),
                    ])
                    ->columns(2),
            ]);
    }

    public function verifyCurrentPassword(): void
    {
        $currentPassword = data_get($this->form->getState(), 'current_password');
        $user = Filament::auth()->user();

        if (! filled($currentPassword)) {
            $this->passwordChangeUnlocked = false;
            $this->addError('data.current_password', 'Enter your current password first.');

            return;
        }

        if (! Hash::check((string) $currentPassword, (string) $user?->password)) {
            $this->passwordChangeUnlocked = false;
            $this->addError('data.current_password', 'The current password is incorrect.');

            Notification::make()
                ->danger()
                ->title('Password not verified')
                ->body('The current password is incorrect.')
                ->send();

            return;
        }

        $this->resetErrorBag('data.current_password');
        $this->passwordChangeUnlocked = true;

        Notification::make()
            ->success()
            ->title('Password verified')
            ->body('You can now enter a new password.')
            ->send();
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

        $user = Filament::auth()->user();
        abort_unless($user, 403);

        if (filled($state['new_password'] ?? null) && ! $this->passwordChangeUnlocked) {
            Notification::make()
                ->danger()
                ->title('Password not changed')
                ->body('Verify your current password before setting a new one.')
                ->send();

            return;
        }

        $user->update([
            'name' => $state['name'] ?? null,
            'email' => $state['email'] ?? null,
            'phone' => BahrainPhone::normalizeForStorage($state['phone'] ?? null),
            'basic_info' => $state['basic_info'] ?? null,
        ]);

        if (filled($state['new_password'] ?? null)) {
            if (! filled($state['current_password'] ?? null) || ! Hash::check((string) $state['current_password'], (string) $user->password)) {
                Notification::make()
                    ->danger()
                    ->title('Password not changed')
                    ->body('The current password is incorrect.')
                    ->send();

                return;
            }

            $user->update([
                'password' => (string) $state['new_password'],
            ]);
        }

        app(ActivityLogService::class)->log(
            event: 'super_admin.profile.updated',
            description: 'Super admin profile details were updated.',
            category: 'admin',
            user: $user,
            subject: $user,
            meta: [
                'summary' => filled($state['new_password'] ?? null)
                    ? 'Super admin account profile and password were changed.'
                    : 'Super admin account profile was changed.',
            ],
        );

        Notification::make()
            ->success()
            ->title('Profile saved')
            ->body(filled($state['new_password'] ?? null)
                ? 'Super admin profile details and password have been updated.'
                : 'Super admin profile details have been updated.')
            ->send();

        $this->mount();
    }
}
