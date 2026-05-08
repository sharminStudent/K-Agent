<?php

namespace App\Filament\Pages;

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
use Illuminate\Validation\Rule;

/**
 * @property-read Schema $form
 */
class EditCompanyProfile extends Page
{
    use HasMaxWidth;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = 'Edit Profile';

    protected static ?string $navigationParentItem = 'General Settings';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

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
        ]);
    }

    public function getTitle(): string|Htmlable
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
                Section::make('Edit Company Details')
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
                            ->required(fn ($get): bool => (bool) $get('lead_notification_enabled')),
                        Toggle::make('lead_notification_enabled')
                            ->label('Email me when a lead is captured')
                            ->default(false),
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

        $user = Filament::auth()->user();

        $user?->update([
            'name' => $state['admin_name'] ?? null,
            'email' => $state['admin_email'] ?? null,
            'phone' => BahrainPhone::normalizeForStorage($state['admin_phone'] ?? null),
        ]);

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

        Notification::make()
            ->success()
            ->title('Profile saved')
            ->body('Admin profile details have been updated.')
            ->send();

        $this->redirect(CompanyProfile::getUrl(), navigate: true);
    }
}
