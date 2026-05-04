<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\ChatSession;

class PolicyService
{
    public function __construct(
        protected LeadCaptureService $leadCaptureService,
    ) {}

    /**
     * @param  array{layer: string, category: string, subtype: string, reason: string, confidence: float, extracted_entities?: array<string, mixed>}  $intent
     * @return array{action: string, reason: string, current_topic: ?string, pending_company_question: ?string, pending_project_interest: ?string, missing_lead_fields: array<int, string>, lead_capture_state: string, previous_topic: ?string}
     */
    public function decide(Agent $agent, ChatSession $chatSession, string $message, array $intent): array
    {
        $meta = $chatSession->meta ?? [];
        $previousTopic = $this->stringMeta($meta, 'current_topic');
        $missingLeadFields = $this->leadCaptureService->missingRequiredFields($agent, $chatSession);
        $leadCaptureState = $this->leadCaptureService->leadCaptureState($agent, $chatSession);
        $hasProjectContext = $this->hasProjectContext($meta);

        if ($intent['layer'] === 'dangerous') {
            return $this->decision('block_sensitive', $intent['reason'], $previousTopic, null, null, $missingLeadFields, $leadCaptureState, $previousTopic);
        }

        if ($missingLeadFields !== [] && ($this->hasPendingLeadContext($meta) || $intent['subtype'] === 'lead_info_provided')) {
            return $this->decision(
                'request_lead',
                'Lead capture is still incomplete for the active conversation.',
                $previousTopic,
                $this->stringMeta($meta, 'pending_company_question'),
                $this->stringMeta($meta, 'pending_project_interest'),
                $missingLeadFields,
                $leadCaptureState,
                $previousTopic
            );
        }

        if ($missingLeadFields === [] && $this->hasPendingLeadContext($meta) && $intent['subtype'] === 'lead_info_provided') {
            return $this->decision(
                'save_lead',
                'Lead capture completed for the active conversation.',
                $this->preferredTopic($intent, $previousTopic),
                $this->stringMeta($meta, 'pending_company_question'),
                $this->stringMeta($meta, 'pending_project_interest'),
                $missingLeadFields,
                $leadCaptureState,
                $previousTopic
            );
        }

        if ($intent['layer'] === 'project_inquiry') {
            return $this->decision(
                $missingLeadFields === [] ? 'answer_from_knowledge' : 'request_lead',
                $missingLeadFields === [] ? 'Project question should be answered from approved knowledge only.' : 'Visitor expressed project or sales intent.',
                $this->resolveTopic($intent, $previousTopic),
                null,
                $message,
                $missingLeadFields,
                $leadCaptureState,
                $previousTopic
            );
        }

        if ($hasProjectContext && in_array($intent['layer'], ['follow_up', 'project_continuation'], true)) {
            return $this->decision(
                $missingLeadFields === [] ? 'continue_previous_topic' : 'request_lead',
                $missingLeadFields === [] ? 'Continuing the active project question using approved knowledge only.' : 'Continuing the active project discovery conversation.',
                $this->resolveTopic($intent, $previousTopic),
                null,
                $this->stringMeta($meta, 'pending_project_interest'),
                $missingLeadFields,
                $leadCaptureState,
                $previousTopic
            );
        }

        if ($intent['layer'] === 'project_continuation') {
            return $this->decision(
                'ask_clarification',
                'Project detail was provided without an active project topic to continue.',
                $previousTopic,
                null,
                null,
                $missingLeadFields,
                $leadCaptureState,
                $previousTopic
            );
        }

        if ($intent['layer'] === 'company') {
            return $this->decision(
                $missingLeadFields === [] ? 'answer_from_knowledge' : 'request_lead',
                'Visitor asked a company or business question.',
                $this->resolveTopic($intent, $previousTopic),
                $message,
                null,
                $missingLeadFields,
                $leadCaptureState,
                $previousTopic
            );
        }

        if ($intent['layer'] === 'follow_up') {
            if ($previousTopic !== null || filled($meta['last_company_question'] ?? null) || filled($meta['last_valid_user_question'] ?? null)) {
                return $this->decision(
                    'continue_previous_topic',
                    'Follow-up was interpreted using the previous conversation topic.',
                    $previousTopic ?? $this->stringMeta($meta, 'last_answered_topic'),
                    $this->stringMeta($meta, 'pending_company_question') ?: $this->stringMeta($meta, 'last_valid_user_question'),
                    $this->stringMeta($meta, 'pending_project_interest'),
                    $missingLeadFields,
                    $leadCaptureState,
                    $previousTopic
                );
            }

            return $this->decision(
                'ask_clarification',
                'Follow-up message had no previous topic to continue.',
                $previousTopic,
                null,
                null,
                $missingLeadFields,
                $leadCaptureState,
                $previousTopic
            );
        }

        if ($intent['layer'] === 'knowledge') {
            return $this->decision(
                'answer_from_knowledge',
                'Visitor asked an allowed knowledge question.',
                $this->resolveTopic($intent, $previousTopic),
                $message,
                null,
                $missingLeadFields,
                $leadCaptureState,
                $previousTopic
            );
        }

        if ($intent['layer'] === 'off_topic') {
            return $this->decision(
                'redirect_offtopic',
                'Message was outside supported company scope or unclear.',
                $previousTopic,
                null,
                null,
                $missingLeadFields,
                $leadCaptureState,
                $previousTopic
            );
        }

        return $this->decision(
            'basic_reply',
            $intent['reason'],
            $previousTopic,
            null,
            null,
            $missingLeadFields,
            $leadCaptureState,
            $previousTopic
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function hasProjectContext(array $meta): bool
    {
        return filled($meta['pending_project_interest'] ?? null)
            || in_array(($meta['current_layer'] ?? null), ['project_inquiry', 'project_continuation'], true)
            || in_array(($meta['last_assistant_action'] ?? null), ['ask_project_followup', 'request_project_lead'], true);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function hasPendingLeadContext(array $meta): bool
    {
        return filled($meta['pending_company_question'] ?? null)
            || filled($meta['pending_project_interest'] ?? null)
            || in_array(($meta['conversation_state'] ?? null), ['awaiting_contact', 'partially_captured_contact'], true);
    }

    /**
     * @param  array{extracted_entities?: array<string, mixed>, subtype: string, layer: string}  $intent
     */
    protected function resolveTopic(array $intent, ?string $previousTopic): ?string
    {
        $topic = data_get($intent, 'extracted_entities.topic')
            ?? data_get($intent, 'extracted_entities.project_type')
            ?? data_get($intent, 'extracted_entities.company_topic');

        if (is_string($topic) && $topic !== '') {
            return $topic;
        }

        if ($intent['layer'] === 'project_inquiry' && $intent['subtype'] !== '') {
            return $intent['subtype'];
        }

        return $previousTopic;
    }

    protected function preferredTopic(array $intent, ?string $previousTopic): ?string
    {
        return $this->resolveTopic($intent, $previousTopic) ?? $previousTopic;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function stringMeta(array $meta, string $key): ?string
    {
        $value = $meta[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * @param  array<int, string>  $missingLeadFields
     * @return array{action: string, reason: string, current_topic: ?string, pending_company_question: ?string, pending_project_interest: ?string, missing_lead_fields: array<int, string>, lead_capture_state: string, previous_topic: ?string}
     */
    protected function decision(
        string $action,
        string $reason,
        ?string $currentTopic,
        ?string $pendingCompanyQuestion,
        ?string $pendingProjectInterest,
        array $missingLeadFields,
        string $leadCaptureState,
        ?string $previousTopic,
    ): array {
        return [
            'action' => $action,
            'reason' => $reason,
            'current_topic' => $currentTopic,
            'pending_company_question' => $pendingCompanyQuestion,
            'pending_project_interest' => $pendingProjectInterest,
            'missing_lead_fields' => $missingLeadFields,
            'lead_capture_state' => $leadCaptureState,
            'previous_topic' => $previousTopic,
        ];
    }
}
