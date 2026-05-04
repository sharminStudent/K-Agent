<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\ChatSession;
use App\Models\KnowledgeFile;
use Illuminate\Support\Facades\Storage;

class ResponseService
{
    public function closingIntent(): string
    {
        return 'Thank you for contacting. Is there anything you would like to know?';
    }

    public function greeting(Agent $agent): string
    {
        return "Hi there, I'm ".$this->companyAgentName($agent).' and I am here to assist you with '.$this->companyName($agent).' related questions. What would you like to know?';
    }

    public function assistantIdentity(Agent $agent): string
    {
        return 'I am '.$this->assistantName($agent).', the AI assistant for '.$this->companyName($agent).'. I can help answer questions about '.$this->companyName($agent).' using the company information available to me.';
    }

    public function socialCheckIn(Agent $agent): string
    {
        return 'I am doing well, thank you. How can I help you with '.$this->companyName($agent).' today?';
    }

    public function gratitude(Agent $agent): string
    {
        return $this->closingIntent();
    }

    public function projectLeadPrompt(Agent $agent, array $missingFields): string
    {
        $fieldPrompt = $this->contactPromptForMissingFields($missingFields, false);

        return 'That sounds great. I can help guide you. '.$fieldPrompt;
    }

    public function projectFollowUp(Agent $agent, ?string $topic = null, int $step = 0): string
    {
        return match ($step % 3) {
            0 => 'Great. What kind of '.($topic === 'website' ? 'website' : 'app or system').' are you planning to build, and who will use it?',
            1 => 'Understood. What are the main features you need first?',
            default => 'Do you already have requirements, a timeline, or a budget range in mind?',
        };
    }

    public function projectContinuationPrompt(?string $topic = null, string $message = ''): string
    {
        $normalized = mb_strtolower($message);

        if (str_contains($normalized, 'booking')) {
            return 'Got it. Would users need to create accounts, choose time slots, and receive booking confirmations?';
        }

        if (str_contains($normalized, 'feature')) {
            return 'The right features depend on the workflow. What should users be able to do first, and who are the main users?';
        }

        return 'That helps. What is the first outcome you want this '.($topic === 'website' ? 'website' : 'project').' to deliver?';
    }

    public function clarificationForFollowUp(): string
    {
        return 'Could you tell me what you would like more details about?';
    }

    public function complimentRedirect(Agent $agent): string
    {
        return $this->offTopicRedirect($agent);
    }

    public function clarification(Agent $agent): string
    {
        return 'Sure. Ask me a specific question about '.$this->companyName($agent).' services, pricing, team, projects, process, or support.';
    }

    public function offTopicRedirect(Agent $agent): string
    {
        return 'Thank you for responding. Unfortunately, I can only guide you with '.$this->companyName($agent).' related inquiries. What would you like to know?';
    }

    public function nonsenseRedirect(Agent $agent): string
    {
        return 'I do not understand that. Please ask me a question about '.$this->companyName($agent).' services.';
    }

    public function guidedRedirect(Agent $agent): string
    {
        return 'I can help with '.$this->companyName($agent).' services, pricing, team, projects, process, capabilities, or support. What would you like to know?';
    }

    public function doneConversationGoodbye(): string
    {
        return 'Thank you for contacting.';
    }

    public function doneConversationContinue(): string
    {
        return 'What would you like to know about?';
    }

    public function unsupportedKnowledgeFallback(Agent $agent): string
    {
        return 'For further detailed information, you can contact our team at '.$this->preferredKnowledgeSupportEmail($agent).'.';
    }

    public function blockedGuardrail(): string
    {
        return 'I cannot help with confidential system instructions, credentials, or private internal data.';
    }

    public function restrictedStaffPrivacy(Agent $agent): string
    {
        return 'I am not authorized to share employee salary or private staff information. For more information, please '.$this->supportContactReference($agent).'.';
    }

    public function handoff(Agent $agent): string
    {
        return 'Thank you for asking. For more information, please '.$this->supportContactReference($agent).'.';
    }

    public function directHandoff(Agent $agent): string
    {
        return 'You can contact the '.$this->companyName($agent).' team via '.$this->preferredSupportEmail($agent).'.';
    }

    public function directHandoffFollowUp(Agent $agent, string $message): string
    {
        $summary = trim((string) preg_replace('/\s+/', ' ', $message));

        if ($summary === '') {
            return $this->directHandoff($agent);
        }

        return 'You can contact the '.$this->companyName($agent).' team via '.$this->preferredSupportEmail($agent).'. You can mention: "'.$summary.'".';
    }

    public function escalationOption(Agent $agent): string
    {
        return 'Thank you for your question. I do not have enough confirmed information to answer that fully. I can help you request a follow-up, or you may '.$this->supportContactReference($agent).'.';
    }

    public function contactPromptForMissingFields(array $missingFields, bool $followUp = false, ?ChatSession $chatSession = null): string
    {
        if ($missingFields === ['full_name']) {
            if (filled($chatSession?->visitor_email)) {
                return $followUp
                    ? 'Thank you. I already have your email address. I still need your full name with first and last name. Reply exactly like this: Full name: Jane Doe'
                    : 'Thank you. I already have your email address. Please reply with your full name using this format: Full name: Jane Doe';
            }

            return $followUp
                ? 'Thank you. I still need your full name with first and last name before I can continue. Reply exactly like this: Full name: Jane Doe'
                : 'Before I continue, please share your full name with first and last name using this format: Full name: Jane Doe';
        }

        if ($missingFields === ['email']) {
            $name = trim((string) $chatSession?->visitor_name);

            if ($name !== '') {
                return $followUp
                    ? 'Thank you, '.$name.'. I still need a valid email address before I can continue. Reply exactly like this: Email: jane@example.com'
                    : 'Thank you, '.$name.'. Please reply with your email using this format: Email: jane@example.com';
            }

            return $followUp
                ? 'Thank you. I still need a valid email address before I can continue. Reply exactly like this: Email: jane@example.com'
                : 'Before I continue, please share your email using this format: Email: jane@example.com';
        }

        return $followUp
            ? 'Before I answer, can I please have your full name and email. Please reply in the format - eg- Full Name: Sharmin Ali, Email: sharmin@gmmail.com.'
            : 'Before I answer, can I please have your full name and email. Please reply in the format - eg- Full Name: Sharmin Ali, Email: sharmin@gmmail.com.';
    }

    public function contactFollowUpRequest(Agent $agent): string
    {
        return 'Sure. Before I continue, could you please share your name and email so the '.$this->companyName($agent).' team can follow up if needed?';
    }

    public function identityFollowUp(Agent $agent): string
    {
        return 'I can answer questions about '.$this->companyName($agent).' services, pricing, working hours, contact details, and any knowledge your team has uploaded for me. Ask me one question about '.$this->companyName($agent).' and I will use the available company information to help.';
    }

    public function conversationFollowUp(Agent $agent): string
    {
        return 'Sure. You can ask me about '.$this->companyName($agent).' services, pricing, working hours, contact details, or support options.';
    }

    public function leadCaptureFollowUp(Agent $agent, ChatSession $chatSession): string
    {
        $name = trim((string) $chatSession->visitor_name);
        $prefix = $name !== '' ? 'Thank you, '.$name.'. ' : 'Thank you. ';

        return $prefix.'I saved your contact details. You can ask me about '.$this->companyName($agent).' services, pricing, working hours, contact details, or support options.';
    }

    public function visitorName(ChatSession $chatSession): string
    {
        $name = trim((string) $chatSession->visitor_name);

        if ($name !== '') {
            return 'Your name is '.$name.'.';
        }

        return 'I do not have your full name yet. Please share your full name so I can save it correctly.';
    }

    public function prependLeadAcknowledgment(string $content, ChatSession $chatSession): string
    {
        $name = trim((string) $chatSession->visitor_name);

        if ($name === '' || $this->startsWithAcknowledgment($content, $name)) {
            return $content;
        }

        return 'Thank you, '.$name.'. '.$content;
    }

    /**
     * @param  array<int, array<string, mixed>>  $contextChunks
     */
    public function summarizeContext(Agent $agent, array $contextChunks): string
    {
        $content = trim((string) ($contextChunks[0]['content'] ?? ''));

        if ($content === '') {
            return $this->handoff($agent);
        }

        return mb_strlen($content) > 420
            ? mb_substr($content, 0, 419).'...'
            : $content;
    }

    protected function supportContactReference(Agent $agent): string
    {
        $companyName = trim((string) $agent->company_name);
        $team = $companyName !== '' ? $companyName.' team' : 'support team';
        $email = trim((string) ($agent->support_email ?: $agent->contact_email));
        $phone = trim((string) $agent->support_phone);

        if ($email !== '' && $phone !== '') {
            return 'contact the '.$team.' at '.$email.' or '.$phone;
        }

        if ($email !== '') {
            return 'contact the '.$team.' at '.$email;
        }

        if ($phone !== '') {
            return 'contact the '.$team.' at '.$phone;
        }

        return 'contact the '.$team;
    }

    protected function preferredKnowledgeSupportEmail(Agent $agent): string
    {
        $knowledgeEmail = $this->knowledgeSupportEmail($agent);

        if ($knowledgeEmail !== null) {
            return $knowledgeEmail;
        }

        $configured = trim((string) ($agent->support_email ?: $agent->contact_email));

        if ($configured !== '') {
            return $configured;
        }

        return $this->preferredSupportEmail($agent);
    }

    protected function knowledgeSupportEmail(Agent $agent): ?string
    {
        $knowledgeFiles = KnowledgeFile::query()
            ->where('agent_id', $agent->id)
            ->where('status', 'ready')
            ->orderByDesc('ingested_at')
            ->get(['disk', 'meta']);

        foreach ($knowledgeFiles as $knowledgeFile) {
            $path = $knowledgeFile->meta['processed_text_path'] ?? null;

            if (! is_string($path) || $path === '') {
                continue;
            }

            $disk = Storage::disk($knowledgeFile->disk);

            if (! $disk->exists($path)) {
                continue;
            }

            $contents = (string) $disk->get($path);

            if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $contents, $matches) === 1) {
                return mb_strtolower($matches[0]);
            }
        }

        return null;
    }

    protected function preferredSupportEmail(Agent $agent): string
    {
        $configured = trim((string) ($agent->support_email ?: $agent->contact_email));

        if ($configured !== '') {
            return $configured;
        }

        $domain = $this->emailDomain($agent);

        return 'hello@'.$domain;
    }

    protected function emailDomain(Agent $agent): string
    {
        $websiteHost = parse_url((string) $agent->website_url, PHP_URL_HOST);

        if (is_string($websiteHost) && $websiteHost !== '') {
            return preg_replace('/^www\./i', '', mb_strtolower($websiteHost)) ?? mb_strtolower($websiteHost);
        }

        $slug = preg_replace('/[^a-z0-9]+/i', '', mb_strtolower((string) $agent->company_name)) ?? '';

        if ($slug !== '') {
            return $slug.'.com';
        }

        return 'company.example';
    }

    protected function startsWithAcknowledgment(string $content, string $fullName): bool
    {
        $normalizedContent = mb_strtolower(trim($content));
        $fullName = mb_strtolower(trim($fullName));
        $firstName = mb_strtolower(trim((string) str($fullName)->before(' ')));

        $patterns = array_filter([
            '/^(thank you|thanks)[,!. ]/u',
            $fullName !== '' ? '/^(thank you|thanks)[,!. ]+\Q'.$fullName.'\E\b/u' : null,
            $firstName !== '' ? '/^(thank you|thanks)[,!. ]+\Q'.$firstName.'\E\b/u' : null,
        ]);

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalizedContent) === 1) {
                return true;
            }
        }

        return false;
    }

    protected function assistantName(Agent $agent): string
    {
        return trim((string) $agent->name) !== '' ? trim((string) $agent->name) : 'Support Assistant';
    }

    protected function companyAgentName(Agent $agent): string
    {
        return $this->companyName($agent).' Agent';
    }

    protected function companyName(Agent $agent): string
    {
        return trim((string) $agent->company_name) !== '' ? trim((string) $agent->company_name) : 'the company';
    }
}
