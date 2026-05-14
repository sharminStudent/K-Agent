<?php

namespace App\Filament\SuperAdmin\Resources\Agents\Pages;

use App\Filament\SuperAdmin\Resources\Agents\AgentResource;
use App\Models\User;
use App\Services\ActivityLogService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property-read Schema $form
 */
class ViewAgent extends Page
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
        return 'View Client';
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
            Action::make('editCompany')
                ->label('Edit Client')
                ->url(AgentResource::getUrl('edit', ['record' => $this->getRecord()])),
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
                    ->disabled(),
            ]);
    }
}
