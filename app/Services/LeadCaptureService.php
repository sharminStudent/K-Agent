<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\ChatSession;
use App\Models\Lead;

class LeadCaptureService
{
    public function __construct(
        protected UsageTrackingService $usageTrackingService,
    ) {}

    /**
     * @return array<int, string>
     */
    public function requiredFields(Agent $agent): array
    {
        $configured = data_get($agent->settings, 'chat.lead_capture.required_fields')
            ?? data_get($agent->settings, 'lead_capture.required_fields');

        if (! is_array($configured) || $configured === []) {
            return ['full_name', 'email'];
        }

        $fields = array_values(array_intersect(
            ['full_name', 'email', 'phone'],
            array_map('strval', $configured)
        ));

        return $fields === [] ? ['full_name', 'email'] : $fields;
    }

    /**
     * @return array<int, string>
     */
    public function missingRequiredFields(Agent $agent, ChatSession $chatSession): array
    {
        $missing = [];

        foreach ($this->requiredFields($agent) as $field) {
            if ($field === 'full_name' && blank($chatSession->visitor_name)) {
                $missing[] = 'full_name';
            }

            if ($field === 'email' && blank($chatSession->visitor_email)) {
                $missing[] = 'email';
            }

            if ($field === 'phone' && blank($chatSession->visitor_phone)) {
                $missing[] = 'phone';
            }
        }

        return $missing;
    }

    public function hasRequiredContact(Agent $agent, ChatSession $chatSession): bool
    {
        return $this->missingRequiredFields($agent, $chatSession) === [];
    }

    public function leadCaptureState(Agent $agent, ChatSession $chatSession): string
    {
        $missingFields = $this->missingRequiredFields($agent, $chatSession);

        if ($missingFields === []) {
            return ChatService::STATE_LEAD_CAPTURED;
        }

        return $this->contactStateForMissingFields($missingFields);
    }

    public function contactStateForMissingFields(array $missingFields): string
    {
        return count($missingFields) >= 2
            ? ChatService::STATE_AWAITING_CONTACT
            : ChatService::STATE_PARTIALLY_CAPTURED_CONTACT;
    }

    public function captureFromMessage(Agent $agent, ChatSession $chatSession, string $message): ChatSession
    {
        $email = $this->extractEmail($message);
        $name = $this->extractName($message, $email, $chatSession) ?: $chatSession->visitor_name;
        $phone = $this->extractPhone($message);

        if (is_string($name) && ! $this->isLikelyFullName($name)) {
            $name = null;
        }

        if (! $email && ! $name && ! $phone) {
            return $chatSession;
        }

        $updates = [];

        if ($email && blank($chatSession->visitor_email)) {
            $updates['visitor_email'] = $email;
        }

        if ($name && blank($chatSession->visitor_name)) {
            $updates['visitor_name'] = $name;
        }

        if ($phone && blank($chatSession->visitor_phone)) {
            $updates['visitor_phone'] = $phone;
        }

        if ($updates !== []) {
            $chatSession->forceFill($updates)->save();
        }

        $chatSession = $chatSession->fresh();

        if (filled($chatSession->visitor_email) && filled($chatSession->visitor_name)) {
            $lead = Lead::query()->firstOrNew([
                'agent_id' => $agent->id,
                'email' => $chatSession->visitor_email,
            ]);

            $wasRecentlyCreated = ! $lead->exists;

            $lead->fill([
                'chat_session_id' => $chatSession->id,
                'name' => $chatSession->visitor_name,
                'phone' => $chatSession->visitor_phone,
                'status' => $lead->status ?: 'new',
                'notes' => $message,
                'meta' => array_merge($lead->meta ?? [], ['source' => 'widget_contact_capture']),
            ]);

            $lead->save();

            if ($wasRecentlyCreated) {
                $this->usageTrackingService->recordLead($agent);
            }
        }

        return $this->syncLeadMeta($agent, $chatSession->fresh());
    }

    public function looksLikeContactAttempt(string $message): bool
    {
        $normalized = mb_strtolower($message);

        return str_contains($normalized, 'name')
            || str_contains($normalized, 'email')
            || str_contains($normalized, '@')
            || preg_match('/^[a-z]+(?:\s+[a-z]+){0,3}$/i', trim($message)) === 1;
    }

    public function consentToShareContact(string $message): bool
    {
        return preg_match('/^(yes|yeah|yep|sure|ok|okay|fine|alright|yes sure|ok sure)[\s,!.?]*$/u', mb_strtolower(trim($message))) === 1;
    }

    public function isContactFollowUpNudge(string $message): bool
    {
        return preg_match('/^(i did|already did|i sent it|i shared it|i gave it|check above|see above|you already have my email|you have my email|i already gave my email|i already shared my email|you already have my name|you have my name|i already gave my name|i already shared my name)[\s,!.?]*$/u', mb_strtolower(trim($message))) === 1;
    }

    public function normalizePendingQuestion(Agent $agent, string $question): string
    {
        $normalized = mb_strtolower(trim($question));

        if (in_array($normalized, ['about', 'about company', 'about the company'], true) || $this->matchesCompanyAboutPrompt($agent, $normalized)) {
            return 'tell me about '.$agent->company_name;
        }

        return $question;
    }

    public function syncLeadMeta(Agent $agent, ChatSession $chatSession): ChatSession
    {
        $missingFields = $this->missingRequiredFields($agent, $chatSession);
        $meta = array_merge($chatSession->meta ?? [], [
            'lead_capture_state' => $missingFields === [] ? ChatService::STATE_LEAD_CAPTURED : $this->contactStateForMissingFields($missingFields),
            'missing_lead_fields' => $missingFields,
        ]);

        $chatSession->forceFill([
            'meta' => $meta,
        ])->save();

        return $chatSession->fresh();
    }

    protected function matchesCompanyAboutPrompt(Agent $agent, string $normalizedMessage): bool
    {
        $companyPattern = $this->companyNamePattern($agent);

        if ($companyPattern === null) {
            return false;
        }

        return preg_match('/^about\s+'.$companyPattern.'$/u', $normalizedMessage) === 1;
    }

    protected function companyNamePattern(Agent $agent): ?string
    {
        $company = mb_strtolower(trim((string) $agent->company_name));

        if ($company === '') {
            return null;
        }

        $parts = preg_split('/[\s\-]+/u', $company) ?: [];
        $parts = array_values(array_filter($parts, fn (string $part): bool => $part !== ''));

        if ($parts === []) {
            return null;
        }

        return implode('[\s\-]*', array_map(fn (string $part): string => preg_quote($part, '/'), $parts));
    }

    protected function extractEmail(string $message): ?string
    {
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $message, $matches) !== 1) {
            return null;
        }

        return mb_strtolower($matches[0]);
    }

    protected function extractName(string $message, ?string $email, ?ChatSession $chatSession = null): ?string
    {
        $withoutEmail = $email ? trim(str_ireplace($email, '', $message)) : $message;
        $normalizedWithoutEmail = trim((string) preg_replace('/\s+/', ' ', $withoutEmail));
        $cleanedWithoutEmail = $this->cleanNameCandidate($normalizedWithoutEmail);

        if (preg_match('/\b(?:full name|name)\s*[:=-]\s*([a-z][a-z\s\'.-]{1,80})(?=$|,|\s+email\b)/i', $withoutEmail, $matches) === 1) {
            $candidate = trim($matches[1]);

            return $this->isLikelyFullName($candidate) && $this->canTreatAsStandaloneName($candidate, $chatSession) ? $candidate : null;
        }

        if (preg_match('/\b(?:my full name is|full name is|my name is|name is|i am|i\'m|this is|name\s*[:=-]|full name\s*[:=-])\s+([a-z][a-z\s\'.-]{1,80}?)(?=[,.]|$|\s+and\s+my\s+email\b|\s+my\s+email\b|\s+email\b|\s+email\s*[:=-]?)/i', $withoutEmail, $matches) === 1) {
            $candidate = trim($matches[1]);

            return $this->isLikelyFullName($candidate) && $this->canTreatAsStandaloneName($candidate, $chatSession) ? $candidate : null;
        }

        if (preg_match('/^\s*([a-z][a-z\s\'.-]{1,60}?)(?:\s*,\s*|\s+)(?:email\b|e-mail\b|mail\b|my email\b|email\s*[:=-]?)/i', $withoutEmail, $matches) === 1) {
            $candidate = trim($matches[1]);

            return $this->isLikelyFullName($candidate) && $this->canTreatAsStandaloneName($candidate, $chatSession) ? $candidate : null;
        }

        if ($email && preg_match('/^\s*([a-z][a-z\s\'.-]{1,60})\s*(?:,\s*|\s+)'.preg_quote($email, '/').'/i', $message, $matches) === 1) {
            $candidate = trim($matches[1]);

            return $this->isLikelyFullName($candidate) && $this->canTreatAsStandaloneName($candidate, $chatSession) ? $candidate : null;
        }

        if ($email && preg_match('/^\s*([a-z][a-z\s\'.-]{1,80})\s*<\s*'.preg_quote($email, '/').'\s*>\s*$/i', $message, $matches) === 1) {
            $candidate = trim($matches[1]);

            return $this->isLikelyFullName($candidate) && $this->canTreatAsStandaloneName($candidate, $chatSession) ? $candidate : null;
        }

        if (preg_match('/^(?:i already said\s*[:,-]?\s*|already said\s*[:,-]?\s*|it is\s+)?([a-z]+(?:\s+[a-z]+){1,3})$/i', $normalizedWithoutEmail, $matches) === 1) {
            $candidate = trim($matches[1]);

            if ($this->canTreatAsStandaloneName($candidate, $chatSession)) {
                return $candidate;
            }
        }

        if ($cleanedWithoutEmail !== '' && $this->isLikelyFullName($cleanedWithoutEmail) && $this->canTreatAsStandaloneName($cleanedWithoutEmail, $chatSession)) {
            return $cleanedWithoutEmail;
        }

        if (blank($chatSession?->visitor_name)
            && filled($chatSession?->visitor_email)
            && preg_match('/^(?:i already said\s*[:,-]?\s*|already said\s*[:,-]?\s*|it is\s+)?([a-z]+(?:\s+[a-z]+){1,3})$/i', $normalizedWithoutEmail, $matches) === 1) {
            $candidate = trim($matches[1]);

            return $this->isLikelyFullName($candidate) ? $candidate : null;
        }

        return null;
    }

    protected function cleanNameCandidate(string $value): string
    {
        $cleaned = mb_strtolower(trim($value));
        $cleaned = preg_replace('/\b(?:my full name is|full name is|my name is|name is|this is|i am|i\'m|name|full name|email|e-mail|mail)\b/i', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\b(?:and|my)\b(?=\s*(?:email|e-mail|mail)\b)/i', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/[:=<>\[\](){}|]/', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/[,_;]+/', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned)) ?? trim($cleaned);

        return trim($cleaned, " \t\n\r\0\x0B,.-");
    }

    protected function canTreatAsStandaloneName(string $candidate, ?ChatSession $chatSession = null): bool
    {
        $normalized = mb_strtolower(trim($candidate));

        if ($normalized === '' || preg_match('/[^a-z\s\'.-]/i', $normalized) === 1) {
            return false;
        }

        if (! $this->isLikelyFullName($normalized)) {
            return false;
        }

        if (preg_match('/\b(what|who|why|how|when|where|services?|pricing|support|project|projects|process|team|email|contact|tell|more|already|said|name|cute|pretty|beautiful|smart|nice|good|bot|love|like)\b/i', $normalized) === 1) {
            return false;
        }

        $awaitingContact = is_array($chatSession?->meta)
            && in_array($chatSession->meta['conversation_state'] ?? null, [ChatService::STATE_AWAITING_CONTACT, ChatService::STATE_PARTIALLY_CAPTURED_CONTACT, ChatService::STATE_NORMAL], true)
            && (($chatSession->meta['pending_company_question'] ?? null) || ($chatSession->meta['pending_follow_up'] ?? null) || ($chatSession->meta['pending_project_interest'] ?? null));

        if (! $awaitingContact && ! blank($chatSession?->visitor_name)) {
            return false;
        }

        return true;
    }

    protected function isLikelyFullName(string $candidate): bool
    {
        $normalized = mb_strtolower(trim($candidate));

        if ($normalized === '' || preg_match('/[^a-z\s\'.-]/i', $normalized) === 1) {
            return false;
        }

        $tokens = collect(preg_split('/\s+/', $normalized) ?: [])
            ->filter(fn (string $token): bool => $token !== '')
            ->values();

        if ($tokens->count() < 2 || $tokens->count() > 4) {
            return false;
        }

        if ($tokens->contains(fn (string $token): bool => mb_strlen($token) < 2)) {
            return false;
        }

        return true;
    }

    protected function extractPhone(string $message): ?string
    {
        if (preg_match('/\+?[0-9][0-9\s().-]{6,20}[0-9]/', $message, $matches) !== 1) {
            return null;
        }

        return trim($matches[0]);
    }
}
