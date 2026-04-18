<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function __construct(
        protected AgentProviderConfigService $agentProviderConfigService,
    ) {
    }

    public function isConfigured(?Agent $agent = null): bool
    {
        $config = $this->agentProviderConfigService->openAiConfig($agent);

        return filled($config['api_key']) && filled($config['embedding_model']);
    }

    /**
     * @param  array<int, string>  $inputs
     * @return array<int, array<int, float>>
     */
    public function embedMany(array $inputs, ?Agent $agent = null): array
    {
        $config = $this->agentProviderConfigService->openAiConfig($agent);

        if (! filled($config['api_key']) || ! filled($config['embedding_model'])) {
            throw new \RuntimeException('OpenAI embeddings are not configured.');
        }

        $response = Http::baseUrl($config['base_url'])
            ->timeout($config['timeout'])
            ->withToken($config['api_key'])
            ->post('/embeddings', [
                'model' => $config['embedding_model'],
                'input' => array_values($inputs),
            ])
            ->throw()
            ->json();

        return collect($response['data'] ?? [])
            ->sortBy('index')
            ->pluck('embedding')
            ->map(fn (mixed $embedding): array => array_map('floatval', $embedding))
            ->all();
    }

    /**
     * @return array<int, float>
     */
    public function embedText(string $input, ?Agent $agent = null): array
    {
        return $this->embedMany([$input], $agent)[0] ?? [];
    }
}
