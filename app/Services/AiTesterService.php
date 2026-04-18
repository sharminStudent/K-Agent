<?php

namespace App\Services;

use App\Models\Agent;

class AiTesterService
{
    public function __construct(
        protected RetrievalService $retrievalService,
        protected PromptBuilderService $promptBuilderService,
        protected OpenAiChatService $openAiChatService,
        protected GuardrailService $guardrailService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(Agent $agent, string $message, string $scenario = 'normal'): array
    {
        $contextChunks = match ($scenario) {
            'no_context' => [],
            default => $this->retrievalService->retrieveRelevantChunks($agent, $message),
        };

        if (($scenario === 'no_context') || $this->guardrailService->shouldUseFallback($contextChunks)) {
            return [
                'scenario' => $scenario,
                'source' => $scenario === 'no_context' ? 'forced_guardrail_no_context' : 'fallback',
                'content' => $this->guardrailService->fallbackMessage($agent),
                'used_fallback' => true,
                'context_chunks' => $contextChunks,
                'prompt' => null,
                'error' => null,
            ];
        }

        if ($scenario === 'openai_unconfigured' || (! $this->openAiChatService->isConfigured($agent))) {
            return [
                'scenario' => $scenario,
                'source' => $scenario === 'openai_unconfigured' ? 'forced_no_openai' : 'fallback_no_openai',
                'content' => $this->guardrailService->fallbackMessage($agent),
                'used_fallback' => true,
                'context_chunks' => $contextChunks,
                'prompt' => null,
                'error' => null,
            ];
        }

        $payload = $this->promptBuilderService->buildSingleTurnPayload($agent, $message, $contextChunks);

        try {
            if ($scenario === 'openai_error') {
                throw new \RuntimeException('Forced OpenAI failure for tester.');
            }

            $response = $this->openAiChatService->generateResponse($payload['instructions'], $payload['input'], $agent);

            return [
                'scenario' => $scenario,
                'source' => 'openai_rag',
                'content' => $response['content'],
                'used_fallback' => false,
                'context_chunks' => $contextChunks,
                'prompt' => $payload,
                'error' => null,
                'openai_response_id' => $response['raw']['id'] ?? null,
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'scenario' => $scenario,
                'source' => 'fallback_error',
                'content' => $this->guardrailService->fallbackMessage($agent),
                'used_fallback' => true,
                'context_chunks' => $contextChunks,
                'prompt' => $payload,
                'error' => $exception->getMessage(),
            ];
        }
    }
}
