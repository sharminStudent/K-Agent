<?php

namespace App\Services;

use App\Models\ChatSession;

class TranscriptService
{
    public function buildTranscript(ChatSession $chatSession): string
    {
        $chatSession->loadMissing('messages');

        $lines = [
            'K-Agent Transcript',
            'Session ID: '.$chatSession->public_id,
            'Visitor Name: '.($chatSession->visitor_name ?: 'Unknown'),
            'Visitor Email: '.($chatSession->visitor_email ?: 'Unknown'),
            'Visitor Phone: '.($chatSession->visitor_phone ?: 'Unknown'),
            'Status: '.($chatSession->status ?: 'Unknown'),
            'Created At: '.optional($chatSession->created_at)?->toDateTimeString(),
            '',
        ];

        foreach ($chatSession->messages as $message) {
            $lines[] = sprintf(
                '[%s] %s: %s',
                $message->created_at?->toDateTimeString(),
                strtoupper((string) $message->role),
                trim((string) $message->content)
            );
        }

        return implode("\n", $lines)."\n";
    }
}
