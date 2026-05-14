<?php

namespace App\Filament\SuperAdmin\Resources\Agents\Pages;

use App\Filament\SuperAdmin\Resources\Agents\AgentResource;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\ClientAccountService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property-read Schema $form
 */
class EditAgent extends Page
{
    use HasMaxWidth;
    use InteractsWithRecord;

    protected static string $resource = AgentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill(
            AgentResource::mutateRecordDataForForm($this->record->attributesToArray(), $this->record),
        );
    }

    public function getTitle(): string|Htmlable
    {
        return 'Edit Client';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('billingHistory')
                ->label('Billing History')
                ->url(AgentResource::getUrl('billing', ['record' => $this->getRecord()])),
            Action::make('resetClientPassword')
                ->label('Reset Client Password')
                ->color('gray')
                ->form([
                    TextInput::make('password')
                        ->label('New Client Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->same('password_confirmation'),
                    TextInput::make('password_confirmation')
                        ->label('Confirm New Client Password')
                        ->password()
                        ->revealable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $user = $this->getRecord()->primaryUser;

                    if (! $user instanceof User) {
                        Notification::make()
                            ->danger()
                            ->title('Client password not reset')
                            ->body('This client does not have a primary login account yet.')
                            ->send();

                        return;
                    }

                    $user->update([
                        'password' => (string) $data['password'],
                    ]);

                    app(ActivityLogService::class)->log(
                        event: 'client.password.reset',
                        description: 'A client password was reset by super admin.',
                        category: 'security',
                        severity: 'high',
                        status: 'success',
                        agent: $this->getRecord(),
                        user: auth()->user(),
                        subject: $user,
                        meta: [
                            'client_email' => $user->email,
                        ],
                    );

                    Notification::make()
                        ->success()
                        ->title('Client password reset')
                        ->body('The client password has been updated.')
                        ->send();
                }),
            Action::make('viewCompany')
                ->label('View Client')
                ->url(AgentResource::getUrl('view', ['record' => $this->getRecord()])),
        ];
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return AgentResource::form($schema);
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
                                ->url(AgentResource::getUrl('view', ['record' => $this->getRecord()])),
                            Action::make('save')
                                ->label('Save Client')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        /** @var array<string, mixed> $data */
        $data = $this->form->getState();
        $data['contact_email'] ??= $this->getRecord()->primaryUser?->email ?? $this->getRecord()->contact_email;
        $data = AgentResource::mutateProviderDataBeforeSave($data, $this->getRecord());

        $this->getRecord()->update([
            'company_name' => $data['company_name'] ?? null,
            'name' => $data['name'] ?? null,
            'slug' => $data['slug'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'contact_email' => $data['contact_email'] ?? null,
            'support_email' => $data['support_email'] ?? null,
            'support_phone' => $data['support_phone'] ?? null,
            'welcome_message' => $data['welcome_message'] ?? null,
            'fallback_message' => $data['fallback_message'] ?? null,
            'payment_status' => $data['payment_status'] ?? null,
            'monthly_token_limit' => $data['monthly_token_limit'] ?? null,
        ]);

        app(ClientAccountService::class)->syncPrimaryUser($this->getRecord()->fresh(), $data);

        Notification::make()
            ->success()
            ->title('Client saved')
            ->body('Client details have been updated.')
            ->send();

        $this->redirect(AgentResource::getUrl('view', ['record' => $this->getRecord()]), navigate: true);
    }
}
