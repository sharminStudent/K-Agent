<?php

namespace App\Services;

use App\Events\WidgetAssistantMessageCreated;
use App\Models\Agent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function __construct(
        protected AgentService $agentService,
        protected RetrievalService $retrievalService,
        protected PromptBuilderService $promptBuilderService,
        protected OpenAiChatService $openAiChatService,
        protected GuardrailService $guardrailService,
    ) {
    }

    public function createSession(array $data): ChatSession
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($data['widget_token']);

        return DB::transaction(function () use ($agent, $data): ChatSession {
            return ChatSession::query()->create([
                'agent_id' => $agent->id,
                'visitor_name' => $data['visitor_name'] ?? null,
                'visitor_email' => $data['visitor_email'] ?? null,
                'visitor_phone' => $data['visitor_phone'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);
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
            broadcast(new WidgetAssistantMessageCreated($chatSession->fresh(), $assistantMessage));
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
        $contextChunks = $this->retrievalService->retrieveRelevantChunks($agent, $latestUserMessage);

        if ($this->guardrailService->shouldUseFallback($contextChunks)) {
            return [
                'content' => $this->guardrailService->fallbackMessage($agent),
                'meta' => [
                    'source' => 'fallback',
                    'context_chunks' => 0,
                ],
            ];
        }

        if (! $this->openAiChatService->isConfigured($agent)) {
            return [
                'content' => $this->guardrailService->fallbackMessage($agent),
                'meta' => [
                    'source' => 'fallback_no_openai',
                    'context_chunks' => count($contextChunks),
                ],
            ];
        }

        try {
            $payload = $this->promptBuilderService->buildChatPayload($agent, $chatSession->fresh(['messages']), $contextChunks);
            $response = $this->openAiChatService->generateResponse($payload['instructions'], $payload['input'], $agent);

            return [
                'content' => $response['content'],
                'meta' => [
                    'source' => 'openai_rag',
                    'context_chunks' => $contextChunks,
                    'openai_response_id' => $response['raw']['id'] ?? null,
                ],
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'content' => $this->guardrailService->fallbackMessage($agent),
                'meta' => [
                    'source' => 'fallback_error',
                    'context_chunks' => count($contextChunks),
                    'error' => $exception->getMessage(),
                ],
            ];
        }
    }
}
