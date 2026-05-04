<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadCapturedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
    ) {}

    public function envelope(): Envelope
    {
        $companyName = $this->lead->agent?->company_name ?: 'your company';

        return new Envelope(
            subject: 'New lead captured for '.$companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-captured',
        );
    }
}
