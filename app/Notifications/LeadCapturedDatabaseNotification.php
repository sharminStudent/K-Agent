<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadCapturedDatabaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Lead $lead,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->lead->loadMissing(['agent', 'chatSession']);

        return [
            'type' => 'lead_captured',
            'title' => 'New lead captured',
            'body' => sprintf(
                '%s was captured as a new lead for %s.',
                $this->lead->name,
                $this->lead->agent?->company_name ?? 'your company'
            ),
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
            'lead_email' => $this->lead->email,
            'chat_session_id' => $this->lead->chatSession?->public_id,
            'url' => '/admin/leads',
            'created_at' => $this->lead->created_at?->toISOString(),
        ];
    }
}
