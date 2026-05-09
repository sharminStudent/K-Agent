<?php

namespace App\Filament\Pages;

use App\Services\ActivityLogService;
use App\Support\BahrainPhone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class CompanyProfile extends Page
{
    use HasMaxWidth;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Profile';

    protected static string|UnitEnum|null $navigationGroup = 'General Settings';

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public bool $passwordChangeUnlocked = false;

    protected function mailNotificationsAreConfigured(): bool
    {
        return ! in_array(config('mail.default'), ['array', 'log'], true);
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->check() && ! Filament::auth()->user()?->isSuperAdmin();
    }

    public function mount(): void
    {
        $user = Filament::auth()->user();
        $agent = $user?->agent;

        $this->form->fill([
            'admin_name' => $user?->name,
            'admin_email' => $user?->email,
            'admin_phone' => BahrainPhone::localDigits($user?->phone),
            'company_name' => $agent?->company_name,
            'company_website_url' => $agent?->website_url,
            'company_slug' => $agent?->slug,
            'lead_notification_enabled' => (bool) data_get($agent?->settings, 'notifications.lead_capture.enabled', false),
            'lead_notification_email' => data_get($agent?->settings, 'notifications.lead_capture.email') ?? $user?->email,
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
                Section::make('Admin Profile')
                    ->description('Update the primary admin account details for this workspace.')
                    ->schema([
                        TextInput::make('admin_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('admin_email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->rule(fn () => Rule::unique('users', 'email')->ignore(Filament::auth()->id()))
                            ->maxLength(255),
                        TextInput::make('admin_phone')
                            ->label('Phone Number')
                            ->tel()
                            ->prefix('+973')
                            ->inputMode('numeric')
                            ->minLength(8)
                            ->maxLength(8)
                            ->placeholder('12345678')
                            ->helperText('Enter 8 digits. Bahrain country code `+973` is added automatically.')
                            ->rule('regex:/^\d{8}$/'),
                    ])
                    ->columns(2),
                Section::make('Company Details')
                    ->description('Update the company profile used in this workspace.')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('company_website_url')
                            ->label('Website URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('company_slug')
                            ->label('Slug')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Notifications')
                    ->description('Send an email when a new lead is captured so your team can review it from the dashboard.')
                    ->schema([
                        TextInput::make('lead_notification_email')
                            ->label('Lead Notification Email')
                            ->email()
                            ->maxLength(255)
                            ->helperText(fn (): ?string => $this->mailNotificationsAreConfigured()
                                ? null
                                : 'Email delivery is not configured on the server yet. Leads will still appear in the dashboard until SMTP, Resend, or another mail provider is connected in production.')
                            ->required(fn ($get): bool => (bool) $get('lead_notification_enabled')),
                        Toggle::make('lead_notification_enabled')
                            ->label('Email me when a lead is captured')
                            ->default(false),
                    ])
                    ->columns(2),
                Section::make('Security')
                    ->description('Change the workspace admin password when needed.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->revealable()
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
                            ->rule(Password::defaults())
                            ->same('new_password_confirmation')
                            ->visible(fn (): bool => $this->passwordChangeUnlocked),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->revealable()
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

        if (filled($state['new_password'] ?? null) && ! $this->passwordChangeUnlocked) {
            Notification::make()
                ->danger()
                ->title('Password not changed')
                ->body('Verify your current password before setting a new one.')
                ->send();

            return;
        }

        $user?->update([
            'name' => $state['admin_name'] ?? null,
            'email' => $state['admin_email'] ?? null,
            'phone' => BahrainPhone::normalizeForStorage($state['admin_phone'] ?? null),
        ]);

        if (filled($state['new_password'] ?? null)) {
            if (! filled($state['current_password'] ?? null) || ! Hash::check((string) $state['current_password'], (string) $user?->password)) {
                Notification::make()
                    ->danger()
                    ->title('Password not changed')
                    ->body('The current password is incorrect.')
                    ->send();

                return;
            }

            $user?->update([
                'password' => (string) $state['new_password'],
            ]);
        }

        $agent = $user?->agent;

        $settings = is_array($agent?->settings) ? $agent->settings : [];
        data_set($settings, 'notifications.lead_capture.enabled', (bool) ($state['lead_notification_enabled'] ?? false));
        data_set($settings, 'notifications.lead_capture.email', $state['lead_notification_email'] ?? null);

        $agent?->update([
            'company_name' => $state['company_name'] ?? null,
            'website_url' => $state['company_website_url'] ?? null,
            'slug' => $state['company_slug'] ?? null,
            'settings' => $settings,
        ]);

        app(ActivityLogService::class)->log(
            event: 'admin.profile.updated',
            description: 'Workspace profile settings were updated.',
            category: 'admin',
            agent: $agent,
            user: $user,
            subject: $agent,
            meta: [
                'summary' => filled($state['new_password'] ?? null)
                    ? 'Admin profile, company details, lead notification settings, or password were changed.'
                    : 'Admin profile, company details, or lead notification settings were changed.',
            ],
        );

        Notification::make()
            ->success()
            ->title('Profile saved')
            ->body(filled($state['new_password'] ?? null)
                ? 'Admin profile details and password have been updated.'
                : 'Admin profile details have been updated.')
            ->send();

        if (($state['lead_notification_enabled'] ?? false) && ! $this->mailNotificationsAreConfigured()) {
            Notification::make()
                ->warning()
                ->title('Email delivery is not configured')
                ->body('Lead notifications are still saved in the dashboard, but Railway is currently using the log mailer. Add SMTP or another mail provider to send real emails.')
                ->send();
        }

        $this->mount();
    }
}
