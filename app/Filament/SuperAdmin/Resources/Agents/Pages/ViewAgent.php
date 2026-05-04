<?php

namespace App\Filament\SuperAdmin\Resources\Agents\Pages;

use App\Filament\SuperAdmin\Resources\Agents\AgentResource;
use Filament\Actions\Action;
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

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill([
            'company_name' => $this->record->company_name,
            'name' => $this->record->name,
            'slug' => $this->record->slug,
            'widget_token' => $this->record->widget_token,
            'website_url' => $this->record->website_url,
            'is_active' => $this->record->is_active,
            'contact_email' => $this->record->primaryUser?->email ?? $this->record->contact_email,
            'support_email' => $this->record->support_email,
            'support_phone' => $this->record->support_phone,
            'password' => null,
            'welcome_message' => $this->record->welcome_message,
            'fallback_message' => $this->record->fallback_message,
            'payment_status' => $this->record->payment_status,
            'monthly_token_limit' => $this->record->monthly_token_limit,
            'monthly_chat_count' => $this->record->monthly_chat_count,
            'monthly_lead_count' => $this->record->monthly_lead_count,
            'monthly_token_count' => $this->record->monthly_token_count,
        ]);
    }

    public function getTitle(): string | Htmlable
    {
        return 'View Client';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('billingHistory')
                ->label('Billing History')
                ->url(AgentResource::getUrl('billing', ['record' => $this->getRecord()])),
            Action::make('editCompany')
                ->label('Edit Client')
                ->url(AgentResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('view')
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
