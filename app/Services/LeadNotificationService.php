<?php

namespace App\Services;

use App\Mail\LeadCapturedMail;
use App\Models\Lead;
use App\Notifications\LeadCapturedDatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class LeadNotificationService
{
    public function sendForLead(Lead $lead): void
    {
        $lead->loadMissing(['agent', 'chatSession']);

        $agent = $lead->agent;

        if (! $agent) {
            return;
        }

        $enabled = (bool) data_get($agent->settings, 'notifications.lead_capture.enabled', false);
        $recipient = data_get($agent->settings, 'notifications.lead_capture.email');

        if (! $enabled || ! is_string($recipient) || blank($recipient)) {
            $this->sendInAppNotifications($lead);

            return;
        }

        Mail::to($recipient)->send(new LeadCapturedMail($lead));

        $this->sendInAppNotifications($lead);
    }

    protected function sendInAppNotifications(Lead $lead): void
    {
        $agent = $lead->agent;

        if (! $agent) {
            return;
        }

        $users = $agent->users()
            ->where('is_active', true)
            ->where('is_super_admin', false)
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new LeadCapturedDatabaseNotification($lead));
    }
}
