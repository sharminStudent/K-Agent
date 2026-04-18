<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\Http;

class OpenAiChatService
{
    public function __construct(
        protected AgentProviderConfigService $agentProviderConfigService,
    ) {
    }

    public function isConfigured(?Agent $agent = null): bool
    {
        $config = $this->agentProviderConfigService->openAiConfig($agent);

        return filled($config['api_key']) && filled($config['chat_model']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $input
     * @return array{content: string, raw: array<string, mixed>}
     */
    public function generateResponse(string $instructions, array $input, ?Agent $agent = null): array
    {
        $config = $this->agentProviderConfigService->openAiConfig($agent);

        if (! filled($config['api_key']) || ! filled($config['chat_model'])) {
            throw new \RuntimeException('OpenAI chat is not configured.');
        }

        $response = Http::baseUrl($config['base_url'])
            ->timeout($config['timeout'])
            ->withToken($config['api_key'])
            ->post('/responses', [
                'model' => $config['chat_model'],
                'instructions' => $instructions,
                'input' => $input,
            ])
            ->throw()
            ->json();

        return [
            'content' => $this->extractOutputText($response),
            'raw' => $response,
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    protected function extractOutputText(array $response): string
    {
        foreach (($response['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $contentPart) {
                if (($contentPart['type'] ?? null) === 'output_text' && filled($contentPart['text'] ?? null)) {
                    return trim((string) $contentPart['text']);
                }
            }
        }

        throw new \RuntimeException('OpenAI response did not contain output text.');
    }
}
