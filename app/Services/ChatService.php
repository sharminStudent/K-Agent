<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatService
{
    public const STATE_NORMAL = 'normal';

    public const STATE_AWAITING_CONTACT = 'awaiting_contact';

    public const STATE_PARTIALLY_CAPTURED_CONTACT = 'partially_captured_contact';

    public const STATE_LEAD_CAPTURED = 'lead_captured';

    public const STATE_COMPANY_FOLLOW_UP = 'company_follow_up';

    public const STATE_ESCALATION_OPTION = 'escalation_option';

    public const STATE_RESTRICTED_HANDOFF = 'restricted_handoff';

    public const STATE_PENDING_DONE_CHECK = 'pending_done_check';

    public function __construct(
        protected AgentService $agentService,
        protected RetrievalService $retrievalService,
        protected PromptBuilderService $promptBuilderService,
        protected OpenAiChatService $openAiChatService,
        protected GuardrailService $guardrailService,
        protected IntentService $intentService,
        protected LeadCaptureService $leadCaptureService,
        protected ResponseService $responseService,
        protected PolicyService $policyService,
        protected WidgetRealtimeService $widgetRealtimeService,
        protected UsageTrackingService $usageTrackingService,
    ) {}

    public function createSession(array $data): ChatSession
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($data['widget_token']);

        return DB::transaction(function () use ($agent, $data): ChatSession {
            $chatSession = ChatSession::query()->create([
                'agent_id' => $agent->id,
                'visitor_name' => $data['visitor_name'] ?? null,
                'visitor_email' => $data['visitor_email'] ?? null,
                'visitor_phone' => $data['visitor_phone'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);

            $this->usageTrackingService->recordChatSession($agent);

            return $chatSession;
        });
    }

    public function storeVisitorMessage(array $data): array
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($data['widget_token']);

        /** @var ChatSession $chatSession */
        $chatSession = ChatSession::query()
            ->where('public_id', $data['session_id'])
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        $this->ensureSessionCanReceiveMessages($chatSession);

        [$chatSession, $userMessage] = DB::transaction(function () use ($chatSession, $agent, $data): array {
            $message = ChatMessage::query()->create([
                'agent_id' => $agent->id,
                'chat_session_id' => $chatSession->id,
                'role' => 'user',
                'content' => $data['message'],
                'meta' => $data['meta'] ?? null,
            ]);

            $chatSession->forceFill([
                'last_message_at' => now(),
            ])->save();

            return [$chatSession->fresh(), $message];
        });

        $assistantReply = $this->buildAssistantReply($agent, $chatSession->fresh());

        $assistantMessage = DB::transaction(function () use ($chatSession, $agent, $assistantReply): ChatMessage {
            $message = ChatMessage::query()->create([
                'agent_id' => $agent->id,
                'chat_session_id' => $chatSession->id,
                'role' => 'assistant',
                'content' => $assistantReply['content'],
                'meta' => $assistantReply['meta'],
            ]);

            $chatSession->forceFill([
                'last_message_at' => now(),
            ])->save();

            return $message;
        });

        try {
            $this->widgetRealtimeService->broadcastAssistantMessage($chatSession->fresh(), $assistantMessage);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return [$chatSession->fresh(), $userMessage, $assistantMessage];
    }

    /**
     * @return array{content: string, meta: array<string, mixed>}
     */
    protected function buildAssistantReply(Agent $agent, ChatSession $chatSession): array
    {
        $latestUserMessage = (string) optional($chatSession->messages()->latest('id')->first())->content;
        $previousMissingLeadFields = $this->leadCaptureService->missingRequiredFields($agent, $chatSession);
        $hadPendingLeadContext = filled($chatSession->meta['pending_company_question'] ?? null)
            || filled($chatSession->meta['pending_project_interest'] ?? null)
            || filled($chatSession->meta['pending_follow_up'] ?? null);
        $chatSession = $this->leadCaptureService->syncLeadMeta(
            $agent,
            $this->leadCaptureService->captureFromMessage($agent, $chatSession, $latestUserMessage)
        );
        $intent = $this->intentService->classify($agent, $latestUserMessage);
        if ($this->shouldTreatAsContextualFollowUp($agent, $chatSession, $latestUserMessage, $intent)) {
            $intent = [
                'layer' => 'follow_up',
                'category' => 'follow_up',
                'subtype' => 'follow_up_request',
                'reason' => 'Matched a contextual follow-up to the previous assistant offer.',
                'confidence' => 0.96,
                'extracted_entities' => [],
            ];
        }
        $decision = $this->policyService->decide($agent, $chatSession, $latestUserMessage, $intent);
        $decision['existing_pending_company_question'] = $chatSession->meta['pending_company_question'] ?? null;
        $decision['existing_pending_follow_up'] = $chatSession->meta['pending_follow_up'] ?? null;
        $decision['existing_pending_project_interest'] = $chatSession->meta['pending_project_interest'] ?? null;

        if ($hadPendingLeadContext
            && $previousMissingLeadFields !== []
            && $this->leadCaptureService->missingRequiredFields($agent, $chatSession) === []) {
            $decision['action'] = 'save_lead';
            $decision['reason'] = 'Lead capture completed for the active conversation.';
        }
        $this->storeClassificationMeta($chatSession, $intent, $decision['action'], $decision['reason'] ?? null, $decision);

        if ($hadPendingLeadContext
            && $previousMissingLeadFields !== []
            && $this->leadCaptureService->missingRequiredFields($agent, $chatSession) === []) {
            $reply = $this->handleSavedLead($agent, $chatSession, $intent);

            return $this->finalizeReply($chatSession->fresh(), $reply, $intent, $reply['meta']['action'] ?? 'save_lead', $reply['meta']['reason'] ?? $decision['reason'], $decision);
        }

        if ($dangerousZone = $this->guardrailService->detectViolation($agent, $latestUserMessage, $intent)) {
            $chatSession = $this->transitionConversationState($chatSession, self::STATE_RESTRICTED_HANDOFF, [
                'restricted_attempt_count' => ((int) ($chatSession->meta['restricted_attempt_count'] ?? 0)) + 1,
            ]);
            $this->guardrailService->logFallback(
                $agent,
                $dangerousZone['meta']['source'],
                $latestUserMessage,
                [],
                $chatSession
            );

            return $this->finalizeReply($chatSession, $dangerousZone, $intent, 'block_sensitive', $dangerousZone['meta']['reason'] ?? 'Dangerous request blocked.', $decision);
        }

        if ($reply = $this->handlePendingDoneCheck($agent, $chatSession, $latestUserMessage)) {
            return $this->finalizeReply($chatSession, $reply, $intent, $reply['meta']['action'] ?? 'reply_basic', $reply['meta']['reason'] ?? $intent['reason'], $decision);
        }

        if ($reply = $this->handlePendingDirectHandoff($agent, $chatSession, $latestUserMessage, $intent)) {
            return $this->finalizeReply($chatSession, $reply, $intent, $reply['meta']['action'] ?? 'direct_handoff', $reply['meta']['reason'] ?? $intent['reason'], $decision);
        }

        if ($intent['subtype'] === 'follow_up_request') {
            if ($reply = $this->buildFollowUpReply($agent, $chatSession)) {
                return $this->finalizeReply($chatSession, $reply, $intent, $reply['meta']['action'] ?? 'continue_previous_topic', $reply['meta']['reason'] ?? $intent['reason'], $decision);
            }
        }

        $reply = match ($decision['action']) {
            'basic_reply' => $this->handleBasicIntent($agent, $chatSession, $intent),
            'request_lead' => $this->handleLeadRequest($agent, $chatSession, $latestUserMessage, $intent, $decision),
            'save_lead' => $this->handleSavedLead($agent, $chatSession, $intent),
            'ask_project_followup' => $this->handleProjectFollowUp($agent, $chatSession, $latestUserMessage, $intent, $decision),
            'continue_previous_topic' => $this->handleContextContinuation($agent, $chatSession, $latestUserMessage),
            'answer_from_knowledge' => $this->answerKnowledgeFromDecision($agent, $chatSession, $latestUserMessage, $decision),
            'ask_clarification' => $this->basicReply($chatSession, 'follow_up_clarification', $this->responseService->clarificationForFollowUp(), false, 'ask_clarification', $decision['reason']),
            'redirect_offtopic' => $this->handleOffTopic($agent, $chatSession, $intent),
            default => $this->basicReply($chatSession, 'guided_redirect', $this->responseService->guidedRedirect($agent), false, 'redirect_scope', 'Redirected visitor back to supported company topics.'),
        };

        return $this->finalizeReply($chatSession->fresh(), $reply ?? $this->basicReply(
            $chatSession,
            'guided_redirect',
            $this->responseService->guidedRedirect($agent)
        ), $intent, $reply['meta']['action'] ?? $decision['action'], $reply['meta']['reason'] ?? $decision['reason'], $decision);
    }

    /**
     * @param  array{category: string, subtype: string}  $intent
     * @return array{content: string, meta: array<string, mixed>}|null
     */
    protected function handleCompanyLayer(Agent $agent, ChatSession $chatSession, string $message, array $intent): ?array
    {
        $pendingQuestion = $chatSession->meta['pending_company_question'] ?? null;
        $pendingFollowUp = $chatSession->meta['pending_follow_up'] ?? null;
        $lastCompanyQuestion = $chatSession->meta['last_company_question'] ?? null;
        $missingContactFields = $this->leadCaptureService->missingRequiredFields($agent, $chatSession);

        if (is_string($pendingQuestion) && $pendingQuestion !== '' && $missingContactFields !== []) {
            $state = $this->leadCaptureService->contactStateForMissingFields($missingContactFields);

            if ($intent['subtype'] === 'compliment_redirect') {
                return null;
            }

            if ($intent['subtype'] === 'contact_consent') {
                $chatSession = $this->transitionConversationState($chatSession, $state);

                return $this->contactReply(
                    $chatSession,
                    'contact_request_follow_up_prompt',
                    $this->responseService->contactPromptForMissingFields($missingContactFields, false, $chatSession),
                    $missingContactFields,
                    'request_lead_fields',
                    'Lead capture is required before answering the pending company question.'
                );
            }

            if ($this->leadCaptureService->looksLikeContactAttempt($message) || $intent['subtype'] === 'contact_follow_up_nudge') {
                $chatSession = $this->transitionConversationState($chatSession, $state);

                return $this->contactReply(
                    $chatSession,
                    $intent['subtype'] === 'contact_follow_up_nudge' ? 'contact_request_follow_up_nudge' : 'contact_request_invalid_contact',
                    $this->responseService->contactPromptForMissingFields($missingContactFields, true, $chatSession),
                    $missingContactFields,
                    'request_lead_fields',
                    'Contact details are still incomplete.'
                );
            }

            if ($intent['category'] === 'company') {
                $chatSession = $this->transitionConversationState($chatSession, $state, [
                    'pending_company_question' => $message,
                    'contact_requested_at' => now()->toISOString(),
                ]);

                return $this->contactReply(
                    $chatSession,
                    'contact_request',
                    $this->responseService->contactPromptForMissingFields($missingContactFields, false, $chatSession),
                    $missingContactFields,
                    'request_lead_fields',
                    'Company question is pending until required lead fields are captured.'
                );
            }

            $chatSession = $this->transitionConversationState($chatSession, $state);

            return $this->contactReply(
                $chatSession,
                $intent['subtype'] === 'contact_follow_up_nudge' ? 'contact_request_follow_up_nudge' : 'contact_request_invalid_contact',
                $this->responseService->contactPromptForMissingFields($missingContactFields, true, $chatSession),
                $missingContactFields,
                'request_lead_fields',
                'Response did not satisfy the required lead fields.'
            );
        }

        if (is_string($pendingQuestion) && $pendingQuestion !== '' && $missingContactFields === []) {
            $chatSession = $this->transitionConversationState($chatSession, self::STATE_LEAD_CAPTURED, [
                'lead_captured_at' => now()->toISOString(),
            ]);

            return $this->answerPendingCompanyQuestion($agent, $chatSession, $pendingQuestion, true);
        }

        if (is_string($pendingFollowUp) && $pendingFollowUp !== '' && $missingContactFields === []) {
            $chatSession->forceFill([
                'meta' => array_diff_key($chatSession->meta ?? [], ['pending_follow_up' => true]),
            ])->save();

            $chatSession = $this->transitionConversationState($chatSession->fresh(), self::STATE_LEAD_CAPTURED, [
                'lead_captured_at' => now()->toISOString(),
            ]);

            return [
                'content' => $this->responseService->leadCaptureFollowUp($agent, $chatSession),
                'meta' => [
                    'source' => 'lead_capture_follow_up',
                    'context_chunks' => 0,
                    'lead_captured' => true,
                    'action' => 'confirm_lead_capture',
                    'reason' => 'Lead capture completed for pending follow-up context.',
                    'auto_close' => false,
                ],
            ];
        }

        if ($intent['subtype'] === 'follow_up_request' && $this->leadCaptureService->hasRequiredContact($agent, $chatSession) && is_string($lastCompanyQuestion) && $lastCompanyQuestion !== '') {
            $previousAssistantMessage = $chatSession->messages()
                ->where('role', 'assistant')
                ->latest('id')
                ->first();

            if ($previousAssistantMessage !== null && $this->shouldAnchorFollowUpToPreviousAssistant($message)) {
                return $this->answerCompanyFollowUpQuestion($agent, $chatSession, (string) $previousAssistantMessage->content, $message);
            }

            return $this->answerCompanyFollowUpQuestion($agent, $chatSession, $lastCompanyQuestion, $message);
        }

        if ($intent['subtype'] === 'follow_up_request') {
            return $this->buildFollowUpReply($agent, $chatSession);
        }

        if ($intent['category'] === 'company') {
            if ($missingContactFields !== []) {
                $chatSession = $this->transitionConversationState($chatSession, $this->leadCaptureService->contactStateForMissingFields($missingContactFields), [
                    'pending_company_question' => $message,
                    'contact_requested_at' => now()->toISOString(),
                ]);

                return $this->contactReply(
                    $chatSession,
                    'contact_request',
                    $this->responseService->contactPromptForMissingFields($missingContactFields, false, $chatSession),
                    $missingContactFields,
                    'request_lead_fields',
                    'Company question requires lead capture before retrieval.'
                );
            }

            $chatSession = $this->transitionConversationState($chatSession, self::STATE_LEAD_CAPTURED, [
                'lead_captured_at' => now()->toISOString(),
            ]);

            return $this->answerCompanyQuestion($agent, $chatSession, $message);
        }

        return null;
    }

    /**
     * @param  array{category: string, subtype: string}  $intent
     * @return array{content: string, meta: array<string, mixed>}|null
     */
    protected function handleBasicIntent(Agent $agent, ChatSession $chatSession, array $intent): ?array
    {
        return match ($intent['subtype']) {
            'visitor_name_lookup' => $this->basicReply($chatSession, 'visitor_name_lookup', $this->responseService->visitorName($chatSession), false, 'reply_basic', 'Returned stored visitor name.'),
            'closing_intent' => $this->doneCheckReply($chatSession, $this->responseService->closingIntent(), 'Detected closing intent.'),
            'greeting' => $this->basicReply($chatSession, 'greeting', $this->responseService->greeting($agent), false, 'reply_basic', 'Greeting does not require lead capture or retrieval.'),
            'assistant_identity' => $this->basicReply($chatSession, 'assistant_identity', $this->responseService->assistantIdentity($agent), false, 'reply_basic', 'Identity question answered directly.'),
            'social_check_in' => $this->basicReply($chatSession, 'social_check_in', $this->responseService->socialCheckIn($agent), false, 'reply_basic', 'Social check-in answered directly.'),
            'gratitude' => $this->doneCheckReply($chatSession, $this->responseService->gratitude($agent), 'Detected gratitude or a done-looking response.'),
            'compliment_redirect' => $this->basicReply($chatSession, 'compliment_redirect', $this->responseService->complimentRedirect($agent), false, 'redirect_scope', 'Compliment redirected to supported topics.'),
            'clarification' => $this->basicReply($chatSession, 'clarification', $this->responseService->clarification($agent), false, 'request_clarification', 'Prompt was too incomplete to answer.'),
            'handoff_request' => $this->directHandoffReply($agent, $chatSession, $this->responseService->directHandoff($agent), 'Visitor explicitly requested human or team contact.'),
            'off_topic_redirect' => $this->basicReply($chatSession, 'off_topic_redirect', $this->responseService->offTopicRedirect($agent), false, 'redirect_scope', 'Message is outside supported company scope.'),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $decision
     */
    protected function handleLeadRequest(Agent $agent, ChatSession $chatSession, string $message, array $intent, array $decision): array
    {
        if ($this->leadCaptureService->hasRequiredContact($agent, $chatSession)) {
            if (filled($decision['existing_pending_project_interest'] ?? null)
                || $intent['layer'] === 'project_inquiry'
                || $intent['layer'] === 'project_continuation') {
                return $this->answerProjectQuestion($agent, $chatSession, $message);
            }

            return $this->answerCompanyQuestion($agent, $chatSession, $message);
        }

        $missingFields = $decision['missing_lead_fields'] ?? $this->leadCaptureService->missingRequiredFields($agent, $chatSession);
        $topic = $decision['current_topic']
            ?? data_get($intent, 'extracted_entities.project_type')
            ?? data_get($intent, 'extracted_entities.company_topic')
            ?? ($chatSession->meta['current_topic'] ?? null);
        $hasPendingContext = filled($decision['existing_pending_company_question'] ?? null)
            || filled($decision['existing_pending_project_interest'] ?? null)
            || filled($decision['existing_pending_follow_up'] ?? null);

        if ($intent['subtype'] === 'compliment_redirect') {
            return $this->basicReply($chatSession, 'compliment_redirect', $this->responseService->complimentRedirect($agent), false, 'redirect_scope', 'Compliment redirected to supported topics.');
        }

        $source = match ($intent['subtype']) {
            'contact_consent' => 'contact_request_follow_up_prompt',
            'contact_follow_up_nudge' => 'contact_request_follow_up_nudge',
            'lead_info_provided' => 'contact_request_invalid_contact',
            default => 'contact_request',
        };

        if ($source === 'contact_request' && $hasPendingContext && $intent['layer'] !== 'company') {
            $source = 'contact_request_invalid_contact';
        }

        $followUp = ! in_array($source, ['contact_request', 'contact_request_follow_up_prompt'], true);
        $prompt = $intent['layer'] === 'project_inquiry' || filled($decision['pending_project_interest'] ?? null)
            ? $this->responseService->projectLeadPrompt($agent, $missingFields)
            : $this->responseService->contactPromptForMissingFields($missingFields, $followUp, $chatSession);

        if ($intent['layer'] === 'project_inquiry' || filled($decision['pending_project_interest'] ?? null)) {
            $chatSession = $this->transitionConversationState($chatSession, $this->leadCaptureService->contactStateForMissingFields($missingFields), [
                'pending_project_interest' => $decision['pending_project_interest'] ?? $message,
                'current_layer' => 'project_inquiry',
                'current_topic' => $topic,
                'contact_requested_at' => now()->toISOString(),
            ]);

            return $this->contactReply(
                $chatSession,
                $source === 'contact_request' ? 'project_contact_request' : $source,
                $prompt,
                $missingFields,
                'request_project_lead',
                'Project inquiry requires lead capture before project discovery continues.'
            );
        }

        $chatSession = $this->transitionConversationState($chatSession, $this->leadCaptureService->contactStateForMissingFields($missingFields), [
            'pending_company_question' => $message,
            'current_layer' => 'company',
            'current_topic' => $topic,
            'contact_requested_at' => now()->toISOString(),
        ]);

        return $this->contactReply(
            $chatSession,
            $source,
            $prompt,
            $missingFields,
            'request_lead',
            'Company question requires lead capture before retrieval.'
        );
    }

    protected function handleSavedLead(Agent $agent, ChatSession $chatSession, array $intent): array
    {
        $pendingProjectInterest = $chatSession->meta['pending_project_interest'] ?? null;
        $pendingCompanyQuestion = $chatSession->meta['pending_company_question'] ?? null;
        $chatSession = $this->transitionConversationState($chatSession, self::STATE_LEAD_CAPTURED, [
            'lead_captured_at' => now()->toISOString(),
        ]);

        if (is_string($pendingProjectInterest) && $pendingProjectInterest !== '') {
            return $this->answerPendingProjectQuestion($agent, $chatSession, $pendingProjectInterest, true);
        }

        if (is_string($pendingCompanyQuestion) && $pendingCompanyQuestion !== '') {
            return $this->answerPendingCompanyQuestion($agent, $chatSession, $pendingCompanyQuestion, true);
        }

        if (($chatSession->meta['pending_follow_up'] ?? null) !== null) {
            $meta = $chatSession->meta ?? [];
            unset($meta['pending_follow_up']);
            $chatSession->forceFill(['meta' => $meta])->save();

            return [
                'content' => $this->responseService->leadCaptureFollowUp($agent, $chatSession->fresh()),
                'meta' => [
                    'source' => 'lead_capture_follow_up',
                    'context_chunks' => 0,
                    'lead_captured' => true,
                    'action' => 'confirm_lead_capture',
                    'reason' => 'Lead capture completed for pending follow-up context.',
                    'auto_close' => false,
                ],
            ];
        }

        return [
            'content' => $this->responseService->leadCaptureFollowUp($agent, $chatSession),
            'meta' => [
                'source' => 'lead_capture_follow_up',
                'context_chunks' => 0,
                'lead_captured' => true,
                'action' => 'confirm_lead_capture',
                'reason' => 'Lead capture completed.',
                'auto_close' => false,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $decision
     */
    protected function handleProjectFollowUp(Agent $agent, ChatSession $chatSession, string $message, array $intent, array $decision, bool $acknowledgeLead = false): array
    {
        $baseQuestion = (string) ($chatSession->meta['last_project_question'] ?? $chatSession->meta['pending_project_interest'] ?? $message);
        $question = $acknowledgeLead || $baseQuestion === $message
            ? $message
            : trim($baseQuestion."\nFollow-up: ".$message);

        $answer = $this->answerProjectQuestion($agent, $chatSession, $question);

        if (! $acknowledgeLead) {
            return $answer;
        }

        $answer['content'] = $this->responseService->prependLeadAcknowledgment($answer['content'], $chatSession);
        $answer['meta']['lead_captured'] = true;

        return $answer;
    }

    protected function handleContextContinuation(Agent $agent, ChatSession $chatSession, string $message): array
    {
        if ($reply = $this->buildFollowUpReply($agent, $chatSession)) {
            return $reply;
        }

        $previousAssistantMessage = $chatSession->messages()
            ->where('role', 'assistant')
            ->latest('id')
            ->first();

        if ($previousAssistantMessage !== null
            && $this->shouldAnchorFollowUpToPreviousAssistant($message)
            && in_array(($previousAssistantMessage->meta['source'] ?? null), ['lead_captured_answer', 'knowledge_direct', 'openai_rag', 'knowledge_direct_openai_error'], true)) {
            return $this->answerCompanyFollowUpQuestion(
                $agent,
                $chatSession,
                (string) $previousAssistantMessage->content,
                $message
            );
        }

        if (filled($chatSession->meta['pending_project_interest'] ?? null)
            || in_array(($chatSession->meta['current_layer'] ?? null), ['project_inquiry', 'project_continuation'], true)) {
            $previousQuestion = (string) ($chatSession->meta['last_project_question'] ?? $chatSession->meta['pending_project_interest'] ?? '');

            return $this->answerProjectFollowUpQuestion($agent, $chatSession, $previousQuestion, $message);
        }

        if (filled($chatSession->meta['last_company_question'] ?? null)) {
            return $this->answerCompanyFollowUpQuestion(
                $agent,
                $chatSession,
                (string) $chatSession->meta['last_company_question'],
                $message
            );
        }

        if (filled($chatSession->meta['last_valid_user_question'] ?? null)) {
            return $this->answerCompanyFollowUpQuestion(
                $agent,
                $chatSession,
                (string) $chatSession->meta['last_valid_user_question'],
                $message
            );
        }

        return $this->basicReply(
            $chatSession,
            'follow_up_clarification',
            $this->responseService->clarificationForFollowUp(),
            false,
            'ask_clarification',
            'No previous topic was available for this follow-up.'
        );
    }

    /**
     * @param  array<string, mixed>  $decision
     */
    protected function answerKnowledgeFromDecision(Agent $agent, ChatSession $chatSession, string $message, array $decision): array
    {
        if (filled($decision['existing_pending_company_question'] ?? null) && $this->leadCaptureService->hasRequiredContact($agent, $chatSession)) {
            return $this->answerPendingCompanyQuestion($agent, $chatSession, (string) $decision['existing_pending_company_question'], true);
        }

        if (filled($decision['existing_pending_project_interest'] ?? null)
            && in_array(($chatSession->meta['current_layer'] ?? null), ['project_inquiry', 'project_continuation'], true)
            && $this->leadCaptureService->hasRequiredContact($agent, $chatSession)) {
            return $this->answerProjectQuestion($agent, $chatSession, $message);
        }

        return $this->answerCompanyQuestion($agent, $chatSession, $message);
    }

    protected function handleOffTopic(Agent $agent, ChatSession $chatSession, array $intent): array
    {
        $chatSession = $this->transitionConversationState($chatSession, self::STATE_NORMAL);

        $source = match ($intent['subtype']) {
            'nonsense_redirect' => 'nonsense_redirect',
            'out_of_scope_redirect' => 'out_of_scope_redirect',
            default => 'off_topic_redirect',
        };

        $content = match ($intent['subtype']) {
            'nonsense_redirect' => $this->responseService->nonsenseRedirect($agent),
            'out_of_scope_redirect', 'off_topic_redirect' => $this->responseService->offTopicRedirect($agent),
            default => $this->responseService->offTopicRedirect($agent),
        };

        return $this->basicReply(
            $chatSession,
            $source,
            $content,
            false,
            'redirect_offtopic',
            'Message was outside supported company scope or unclear.'
        );
    }

    /**
     * @return array{content: string, meta: array<string, mixed>}|null
     */
    protected function handlePendingDoneCheck(Agent $agent, ChatSession $chatSession, string $message): ?array
    {
        if (($chatSession->meta['pending_done_check'] ?? false) !== true) {
            return null;
        }

        $normalized = mb_strtolower(trim($message));

        if (preg_match('/^(no|nope|nah|not now|nothing|nothing else|that is all|thats all)$/u', $normalized) === 1) {
            return $this->basicReply(
                $chatSession,
                'closing_end',
                $this->responseService->doneConversationGoodbye(),
                true,
                'end_conversation',
                'Visitor confirmed the conversation is finished.'
            );
        }

        if (preg_match('/^(yes|yeah|yep|sure|ok|okay|alright|tell me|i do)$/u', $normalized) === 1) {
            return $this->basicReply(
                $chatSession,
                'closing_continue',
                $this->responseService->doneConversationContinue(),
                false,
                'request_clarification',
                'Visitor wants to continue after the done check.'
            );
        }

        if ($this->intentService->classify($agent, $message)['layer'] === 'company') {
            return $this->basicReply(
                $chatSession,
                'closing_continue',
                $this->responseService->doneConversationContinue(),
                false,
                'request_clarification',
                'Visitor wants to continue after the done check.'
            );
        }

        return null;
    }

    /**
     * @param  array{layer: string, category: string, subtype: string, reason: string, confidence: float}  $intent
     * @return array{content: string, meta: array<string, mixed>}|null
     */
    protected function handlePendingDirectHandoff(Agent $agent, ChatSession $chatSession, string $message, array $intent): ?array
    {
        if (($chatSession->meta['pending_direct_handoff'] ?? false) !== true) {
            return null;
        }

        if (in_array($intent['subtype'], ['handoff_request', 'closing_intent', 'greeting', 'assistant_identity', 'social_check_in', 'gratitude'], true)) {
            return null;
        }

        if ($intent['layer'] === 'dangerous') {
            return null;
        }

        return $this->directHandoffReply(
            $agent,
            $chatSession,
            $this->responseService->directHandoffFollowUp($agent, $message),
            'Visitor continued after requesting team contact; repeated direct handoff with their message summary.'
        );
    }

    /**
     * @return array{content: string, meta: array<string, mixed>}|null
     */
    protected function buildFollowUpReply(Agent $agent, ChatSession $chatSession): ?array
    {
        $previousAssistantMessage = $chatSession->messages()
            ->where('role', 'assistant')
            ->latest('id')
            ->first();

        $source = $previousAssistantMessage?->meta['source'] ?? null;

        if ($source === 'assistant_identity') {
            if (! $this->leadCaptureService->hasRequiredContact($agent, $chatSession)) {
                $chatSession->forceFill([
                    'meta' => array_merge($chatSession->meta ?? [], [
                        'pending_follow_up' => 'identity_more',
                        'contact_requested_at' => now()->toISOString(),
                    ]),
                ])->save();
                $chatSession = $this->transitionConversationState($chatSession->fresh(), self::STATE_AWAITING_CONTACT);

                return [
                    'content' => $this->responseService->contactFollowUpRequest($agent),
                    'meta' => [
                        'source' => 'contact_request_follow_up',
                        'context_chunks' => 0,
                        'requires_contact' => true,
                        'action' => 'request_lead_fields',
                        'reason' => 'Follow-up continues only after required contact is captured.',
                        'auto_close' => false,
                    ],
                ];
            }

            return $this->basicReply(
                $chatSession,
                'identity_follow_up',
                $this->responseService->identityFollowUp($agent)
            );
        }

        if ($source === 'greeting' || $source === 'social_check_in') {
            return $this->basicReply(
                $chatSession,
                'conversation_follow_up',
                $this->responseService->conversationFollowUp($agent)
            );
        }

        return null;
    }

    protected function answerCompanyQuestion(Agent $agent, ChatSession $chatSession, string $message): array
    {
        $chatSession = $this->rememberCompanyQuestion($chatSession, $message);
        $contextChunks = $this->retrievalService->retrieveRelevantChunks($agent, $message);

        return $this->answerApprovedKnowledgeQuestion($agent, $chatSession, $message, $contextChunks);
    }

    protected function answerCompanyFollowUpQuestion(Agent $agent, ChatSession $chatSession, string $previousQuestion, string $followUpMessage): array
    {
        $combinedMessage = $this->buildCompanyFollowUpQuery($previousQuestion, $followUpMessage);
        $chatSession = $this->transitionConversationState(
            $this->rememberCompanyQuestion($chatSession, $combinedMessage),
            self::STATE_COMPANY_FOLLOW_UP
        );

        return $this->answerCompanyQuestion($agent, $chatSession, $combinedMessage);
    }

    protected function answerPendingCompanyQuestion(Agent $agent, ChatSession $chatSession, string $pendingQuestion, bool $acknowledgeLead = false): array
    {
        $normalizedQuestion = $this->leadCaptureService->normalizePendingQuestion($agent, $pendingQuestion);
        $chatSession = $this->clearPendingCompanyQuestion($chatSession);

        $answer = $this->answerCompanyQuestion($agent, $chatSession, $normalizedQuestion);

        if (! $acknowledgeLead) {
            return $answer;
        }

        $answer['content'] = $this->responseService->prependLeadAcknowledgment($answer['content'], $chatSession);
        $answer['meta']['lead_captured'] = true;
        $answer['meta']['source'] = 'lead_captured_answer';

        return $answer;
    }

    protected function answerProjectQuestion(Agent $agent, ChatSession $chatSession, string $message): array
    {
        $chatSession = $this->rememberProjectQuestion($chatSession, $message);
        $contextChunks = $this->retrievalService->retrieveRelevantChunks($agent, $message);

        return $this->answerApprovedKnowledgeQuestion($agent, $chatSession, $message, $contextChunks);
    }

    protected function answerProjectFollowUpQuestion(Agent $agent, ChatSession $chatSession, string $previousQuestion, string $followUpMessage): array
    {
        $base = trim($previousQuestion) !== '' ? $previousQuestion : (string) ($chatSession->meta['pending_project_interest'] ?? '');
        $combinedMessage = trim($base !== '' ? $base."\nFollow-up: ".$followUpMessage : $followUpMessage);
        $chatSession = $this->transitionConversationState($chatSession, self::STATE_COMPANY_FOLLOW_UP);

        return $this->answerProjectQuestion($agent, $chatSession, $combinedMessage);
    }

    protected function answerPendingProjectQuestion(Agent $agent, ChatSession $chatSession, string $pendingQuestion, bool $acknowledgeLead = false): array
    {
        $chatSession = $this->clearPendingProjectInterest($chatSession);

        $answer = $this->answerProjectQuestion($agent, $chatSession, $pendingQuestion);

        if (! $acknowledgeLead) {
            return $answer;
        }

        $answer['content'] = $this->responseService->prependLeadAcknowledgment($answer['content'], $chatSession);
        $answer['meta']['lead_captured'] = true;

        return $answer;
    }

    /**
     * @param  array<int, array<string, mixed>>  $contextChunks
     * @return array{content: string, meta: array<string, mixed>}
     */
    protected function answerApprovedKnowledgeQuestion(Agent $agent, ChatSession $chatSession, string $message, array $contextChunks): array
    {
        if ($this->guardrailService->shouldUseFallback($contextChunks, $message)) {
            $this->guardrailService->logFallback(
                $agent,
                'handoff_missing_approved_context',
                $message,
                $contextChunks,
                $chatSession
            );

            $this->transitionConversationState($chatSession, self::STATE_ESCALATION_OPTION);

            return [
                'content' => $this->guardrailService->unsupportedKnowledgeMessage($agent),
                'meta' => [
                    'source' => 'unsupported_knowledge_fallback',
                    'context_chunks' => count($contextChunks),
                    'action' => 'handoff_contact_team',
                    'reason' => 'No approved knowledge supported the requested answer.',
                    'auto_close' => false,
                ],
            ];
        }

        if (! $this->openAiChatService->isConfigured($agent)) {
            $this->transitionConversationState($chatSession, self::STATE_COMPANY_FOLLOW_UP);

            return [
                'content' => $this->responseService->summarizeContext($agent, $contextChunks),
                'meta' => [
                    'source' => 'knowledge_direct',
                    'context_chunks' => count($contextChunks),
                    'action' => 'answer_from_knowledge',
                    'reason' => 'Approved knowledge was returned directly without OpenAI.',
                    'auto_close' => false,
                ],
            ];
        }

        try {
            $payload = $this->promptBuilderService->buildChatPayload($agent, $chatSession->fresh(['messages']), $contextChunks);
            $response = $this->openAiChatService->generateResponse($payload['instructions'], $payload['input'], $agent);

            if ($unsafeReason = $this->guardrailService->unsafeAssistantMessage($response['content'])) {
                $this->guardrailService->logFallback(
                    $agent,
                    'blocked_assistant_guardrail',
                    $message,
                    $contextChunks,
                    $chatSession,
                    ['reason' => $unsafeReason]
                );
                $this->transitionConversationState($chatSession, self::STATE_RESTRICTED_HANDOFF);

                return [
                    'content' => $this->responseService->handoff($agent),
                    'meta' => [
                        'source' => 'restricted_handoff',
                        'context_chunks' => count($contextChunks),
                        'action' => 'block_output',
                        'reason' => 'Generated output failed final safety checks.',
                        'auto_close' => false,
                    ],
                ];
            }

            $this->transitionConversationState($chatSession, self::STATE_COMPANY_FOLLOW_UP);

            return [
                'content' => $response['content'],
                'meta' => [
                    'source' => 'openai_rag',
                    'context_chunks' => $contextChunks,
                    'openai_response_id' => $response['raw']['id'] ?? null,
                    'action' => 'answer_from_knowledge',
                    'reason' => 'Approved retrieval context passed backend checks and OpenAI response was allowed.',
                    'auto_close' => false,
                ],
            ];
        } catch (\Throwable $exception) {
            report($exception);
            $this->guardrailService->logFallback(
                $agent,
                'handoff_openai_error',
                $message,
                $contextChunks,
                $chatSession,
                ['error' => $exception->getMessage()]
            );
            $this->transitionConversationState($chatSession, self::STATE_RESTRICTED_HANDOFF);

            return [
                'content' => $this->responseService->handoff($agent),
                'meta' => [
                    'source' => 'handoff_openai_error',
                    'context_chunks' => count($contextChunks),
                    'error' => $exception->getMessage(),
                    'action' => 'handoff_contact_team',
                    'reason' => 'OpenAI generation failed after retrieval.',
                    'auto_close' => false,
                ],
            ];
        }
    }

    protected function clearPendingCompanyQuestion(ChatSession $chatSession): ChatSession
    {
        $meta = $chatSession->meta ?? [];
        unset($meta['pending_company_question'], $meta['contact_requested_at']);

        $chatSession->forceFill([
            'meta' => $meta,
        ])->save();

        return $chatSession->fresh();
    }

    protected function clearPendingProjectInterest(ChatSession $chatSession): ChatSession
    {
        $meta = $chatSession->meta ?? [];
        unset($meta['pending_project_interest'], $meta['contact_requested_at']);

        $chatSession->forceFill([
            'meta' => $meta,
        ])->save();

        return $chatSession->fresh();
    }

    protected function rememberCompanyQuestion(ChatSession $chatSession, string $message): ChatSession
    {
        $meta = array_merge($chatSession->meta ?? [], [
            'last_company_question' => $message,
            'last_valid_user_question' => $message,
        ]);

        $chatSession->forceFill([
            'meta' => $meta,
        ])->save();

        return $chatSession->fresh();
    }

    protected function rememberProjectQuestion(ChatSession $chatSession, string $message): ChatSession
    {
        $meta = array_merge($chatSession->meta ?? [], [
            'last_project_question' => $message,
            'last_valid_user_question' => $message,
        ]);

        $chatSession->forceFill([
            'meta' => $meta,
        ])->save();

        return $chatSession->fresh();
    }

    /**
     * @return array{content: string, meta: array<string, mixed>}
     */
    protected function basicReply(ChatSession $chatSession, string $source, string $content, bool $autoClose = false, string $action = 'reply_basic', ?string $reason = null): array
    {
        $chatSession = $this->transitionConversationState($chatSession, self::STATE_NORMAL);

        return [
            'content' => $content,
            'meta' => [
                'source' => $source,
                'context_chunks' => 0,
                'action' => $action,
                'reason' => $reason,
                'auto_close' => $autoClose,
            ],
        ];
    }

    /**
     * @return array{content: string, meta: array<string, mixed>}
     */
    protected function doneCheckReply(ChatSession $chatSession, string $content, ?string $reason = null): array
    {
        $chatSession = $this->transitionConversationState($chatSession, self::STATE_PENDING_DONE_CHECK, [
            'pending_done_check' => true,
        ]);

        return [
            'content' => $content,
            'meta' => [
                'source' => 'closing_check',
                'context_chunks' => 0,
                'action' => 'confirm_done_status',
                'reason' => $reason,
                'auto_close' => false,
            ],
        ];
    }

    /**
     * @return array{content: string, meta: array<string, mixed>}
     */
    protected function directHandoffReply(Agent $agent, ChatSession $chatSession, string $content, ?string $reason = null): array
    {
        $chatSession = $this->transitionConversationState($chatSession, self::STATE_ESCALATION_OPTION, [
            'pending_direct_handoff' => true,
        ]);

        return [
            'content' => $content,
            'meta' => [
                'source' => 'direct_handoff',
                'context_chunks' => 0,
                'action' => 'direct_handoff',
                'reason' => $reason,
                'auto_close' => false,
            ],
        ];
    }

    /**
     * @param  array<int, string>  $missingFields
     * @return array{content: string, meta: array<string, mixed>}
     */
    protected function contactReply(ChatSession $chatSession, string $source, string $content, array $missingFields, string $action = 'request_lead_fields', ?string $reason = null): array
    {
        return [
            'content' => $content,
            'meta' => [
                'source' => $source,
                'context_chunks' => 0,
                'missing_fields' => $missingFields,
                'requires_contact' => true,
                'action' => $action,
                'reason' => $reason,
                'auto_close' => false,
            ],
        ];
    }

    /**
     * @param  array{layer: string, category: string, subtype: string, reason: string, confidence: float}  $intent
     * @return array{content: string, meta: array<string, mixed>}
     */
    protected function withClassificationMeta(array $reply, array $intent, string $action, ?string $reason = null, array $decision = []): array
    {
        $reply['meta'] = array_merge($reply['meta'] ?? [], [
            'layer' => $intent['layer'],
            'classified_layer' => $intent['layer'],
            'sub_intent' => $intent['subtype'],
            'action' => $action,
            'reason' => $reason ?? $intent['reason'],
            'confidence' => $intent['confidence'],
            'previous_topic' => $decision['previous_topic'] ?? null,
            'current_topic' => $decision['current_topic'] ?? null,
            'lead_capture_state' => $decision['lead_capture_state'] ?? null,
            'missing_lead_fields' => $decision['missing_lead_fields'] ?? [],
        ]);

        return $reply;
    }

    /**
     * @param  array{layer: string, category: string, subtype: string, reason: string, confidence: float}  $intent
     */
    protected function storeClassificationMeta(ChatSession $chatSession, array $intent, string $action, ?string $reason = null, array $decision = []): void
    {
        $latestUserMessage = $chatSession->messages()
            ->where('role', 'user')
            ->latest('id')
            ->first();

        if ($latestUserMessage !== null) {
            $latestUserMessage->forceFill([
                'meta' => array_merge($latestUserMessage->meta ?? [], [
                    'layer' => $intent['layer'],
                    'classified_layer' => $intent['layer'],
                    'sub_intent' => $intent['subtype'],
                    'action' => $action,
                    'reason' => $reason ?? $intent['reason'],
                    'confidence' => $intent['confidence'],
                    'previous_topic' => $decision['previous_topic'] ?? null,
                    'current_topic' => $decision['current_topic'] ?? null,
                    'lead_capture_state' => $decision['lead_capture_state'] ?? null,
                    'missing_lead_fields' => $decision['missing_lead_fields'] ?? [],
                ]),
            ])->save();
        }

        $chatSession->forceFill([
            'meta' => array_merge($chatSession->meta ?? [], [
                'last_classification' => [
                    'layer' => $intent['layer'],
                    'classified_layer' => $intent['layer'],
                    'sub_intent' => $intent['subtype'],
                    'action' => $action,
                    'reason' => $reason ?? $intent['reason'],
                    'confidence' => $intent['confidence'],
                ],
                'current_layer' => $intent['layer'],
                'current_intent' => $intent['subtype'],
                'previous_topic' => $decision['previous_topic'] ?? ($chatSession->meta['current_topic'] ?? null),
                'current_topic' => $decision['current_topic'] ?? ($chatSession->meta['current_topic'] ?? null),
                'lead_capture_state' => $decision['lead_capture_state'] ?? ($chatSession->meta['lead_capture_state'] ?? null),
                'missing_lead_fields' => $decision['missing_lead_fields'] ?? ($chatSession->meta['missing_lead_fields'] ?? []),
                'pending_company_question' => $decision['pending_company_question'] ?? ($chatSession->meta['pending_company_question'] ?? null),
                'pending_project_interest' => $decision['pending_project_interest'] ?? ($chatSession->meta['pending_project_interest'] ?? null),
            ]),
        ])->save();
    }

    protected function finalizeReply(ChatSession $chatSession, array $reply, array $intent, string $action, ?string $reason = null, array $decision = []): array
    {
        $reply = $this->withClassificationMeta($reply, $intent, $action, $reason, $decision);
        $source = $reply['meta']['source'] ?? null;
        $pendingCompanyQuestion = $decision['pending_company_question'] ?? ($chatSession->meta['pending_company_question'] ?? null);
        $pendingProjectInterest = $decision['pending_project_interest'] ?? ($chatSession->meta['pending_project_interest'] ?? null);

        if (in_array($source, ['lead_captured_answer', 'knowledge_direct', 'openai_rag', 'knowledge_direct_openai_error', 'unsupported_knowledge_fallback'], true)) {
            $pendingCompanyQuestion = null;
            $pendingProjectInterest = null;
        }

        if (in_array($source, ['project_follow_up', 'project_follow_up_after_lead', 'project_continuation'], true)) {
            $pendingCompanyQuestion = null;
            $pendingProjectInterest = null;
        }

        $meta = array_merge($chatSession->meta ?? [], [
            'current_layer' => $intent['layer'],
            'current_intent' => $intent['subtype'],
            'current_topic' => $decision['current_topic'] ?? ($chatSession->meta['current_topic'] ?? null),
            'pending_company_question' => $pendingCompanyQuestion,
            'pending_project_interest' => $pendingProjectInterest,
            'lead_capture_state' => $decision['lead_capture_state'] ?? ($chatSession->meta['lead_capture_state'] ?? null),
            'missing_lead_fields' => $decision['missing_lead_fields'] ?? ($chatSession->meta['missing_lead_fields'] ?? []),
            'last_assistant_action' => $action,
            'last_answered_topic' => $decision['current_topic'] ?? ($chatSession->meta['last_answered_topic'] ?? null),
        ]);

        if ($source === 'closing_check') {
            $meta['pending_done_check'] = true;
        } else {
            unset($meta['pending_done_check']);
        }

        if ($pendingCompanyQuestion === null) {
            unset($meta['pending_company_question']);
        }

        if ($pendingProjectInterest === null) {
            unset($meta['pending_project_interest']);
            unset($meta['contact_requested_at']);
        }

        $chatSession->forceFill([
            'meta' => $meta,
        ])->save();

        return $reply;
    }

    protected function transitionConversationState(ChatSession $chatSession, string $state, array $extraMeta = []): ChatSession
    {
        $meta = array_merge($chatSession->meta ?? [], $extraMeta, [
            'conversation_state' => $state,
        ]);

        $chatSession->forceFill(['meta' => $meta])->save();

        return $chatSession->fresh();
    }

    protected function ensureSessionCanReceiveMessages(ChatSession $chatSession): void
    {
        if ($chatSession->status === 'closed') {
            throw ValidationException::withMessages([
                'session_id' => 'This chat session is already closed. Please start a new chat.',
            ]);
        }

        if (! $this->isSessionExpired($chatSession)) {
            return;
        }

        $chatSession->forceFill([
            'status' => 'closed',
            'meta' => array_merge($chatSession->meta ?? [], [
                'closed_reason' => 'idle_timeout',
                'closed_at' => now()->toISOString(),
            ]),
        ])->save();

        throw ValidationException::withMessages([
            'session_id' => 'This chat session has expired. Please start a new chat.',
        ]);
    }

    protected function isSessionExpired(ChatSession $chatSession): bool
    {
        $timeoutMinutes = (int) config('services.widget.chat_idle_timeout_minutes', 30);

        if ($timeoutMinutes <= 0) {
            return false;
        }

        $activityAt = $chatSession->last_message_at ?? $chatSession->created_at;

        return $activityAt !== null && $activityAt->lte(now()->subMinutes($timeoutMinutes));
    }

    /**
     * @param  array{layer: string, category: string, subtype: string, reason: string, confidence: float, extracted_entities?: array<string, mixed>}  $intent
     */
    protected function shouldTreatAsContextualFollowUp(Agent $agent, ChatSession $chatSession, string $message, array $intent): bool
    {
        if (! $this->leadCaptureService->hasRequiredContact($agent, $chatSession)) {
            return false;
        }

        if (in_array($intent['layer'], ['company', 'follow_up', 'project_inquiry', 'project_continuation', 'dangerous'], true)) {
            return false;
        }

        if (($chatSession->meta['pending_done_check'] ?? false) === true) {
            return false;
        }

        $normalized = mb_strtolower(trim($message));

        if (! $this->isAffirmativeContinuationMessage($message)) {
            return false;
        }

        $previousAssistantMessage = $chatSession->messages()
            ->where('role', 'assistant')
            ->latest('id')
            ->first();

        $source = $previousAssistantMessage?->meta['source'] ?? null;

        if (! in_array($source, ['lead_captured_answer', 'knowledge_direct', 'openai_rag', 'knowledge_direct_openai_error'], true)) {
            return false;
        }

        return filled($chatSession->meta['last_company_question'] ?? null)
            || filled($chatSession->meta['last_project_question'] ?? null)
            || filled($chatSession->meta['last_valid_user_question'] ?? null);
    }

    protected function isAffirmativeContinuationMessage(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));

        return preg_match('/^(?:(?:yes|yeah|yep|sure|okay|ok|alright)(?:[\s,!.?]+(?:please|guide me|guide me through|help me get started|get me started|next steps|show me how|show me the process))?|please do|guide me|guide me through|help me get started|get me started|next steps|show me how|show me the process)[\s,!.?]*$/u', $normalized) === 1;
    }

    protected function shouldAnchorFollowUpToPreviousAssistant(string $message): bool
    {
        if ($this->isAffirmativeContinuationMessage($message)) {
            return true;
        }

        $normalized = mb_strtolower(trim($message));

        return preg_match('/\b(walk me through|guide me|guide me through|get started|next steps|show me how|show me the process)\b/u', $normalized) === 1;
    }

    protected function buildCompanyFollowUpQuery(string $previousQuestion, string $followUpMessage): string
    {
        $normalizedFollowUp = mb_strtolower(trim($followUpMessage));

        if (preg_match('/\b(guide me|guide me through|get started|next steps|show me how|show me the process)\b/u', $normalizedFollowUp) === 1) {
            return trim("Focus: company process, next steps, and how to get started.\nVisitor follow-up: ".$followUpMessage);
        }

        return trim($previousQuestion."\nFollow-up: ".$followUpMessage);
    }
}
