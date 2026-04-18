<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\KnowledgeFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VectorStoreService
{
    public function __construct(
        protected AgentProviderConfigService $agentProviderConfigService,
    ) {
    }

    public function isConfigured(?Agent $agent = null): bool
    {
        $config = $this->agentProviderConfigService->qdrantConfig($agent);

        return filled($config['url']) && filled($config['collection']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $chunks
     * @param  array<int, array<int, float>>  $embeddings
     * @return array{path: string, count: int, backend: string, collection: string|null, point_ids: array<int, string>}
     */
    public function storeKnowledgeEmbeddings(KnowledgeFile $knowledgeFile, array $chunks, array $embeddings): array
    {
        $payload = collect($chunks)->values()->map(function (array $chunk, int $index) use ($embeddings): array {
            return [
                'index' => $chunk['index'] ?? $index,
                'content' => $chunk['content'] ?? '',
                'length' => $chunk['length'] ?? mb_strlen((string) ($chunk['content'] ?? '')),
                'embedding' => $embeddings[$index] ?? [],
            ];
        })->all();

        $path = $this->storeLocally($knowledgeFile, $payload);
        $pointIds = [];
        $backend = 'file';

        $agent = $this->resolveAgentForKnowledgeFile($knowledgeFile);

        if ($this->isConfigured($agent) && $payload !== []) {
            try {
                $pointIds = $this->upsertToQdrant($knowledgeFile, $payload, $agent);
                $backend = 'qdrant';
            } catch (\Throwable $exception) {
                report($exception);
                Log::warning('Falling back to file-backed vector store.', [
                    'knowledge_file_id' => $knowledgeFile->id,
                    'agent_id' => $knowledgeFile->agent_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'path' => $path,
            'count' => count($payload),
            'backend' => $backend,
            'collection' => $backend === 'qdrant' ? $this->collectionName() : null,
            'point_ids' => $pointIds,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function loadKnowledgeEmbeddings(KnowledgeFile $knowledgeFile): array
    {
        $agent = $this->resolveAgentForKnowledgeFile($knowledgeFile);

        if ($this->isConfigured($agent) && (($knowledgeFile->meta['vector_backend'] ?? null) === 'qdrant')) {
            try {
                $remoteChunks = $this->loadFromQdrant($knowledgeFile, $agent);

                if ($remoteChunks !== []) {
                    return $remoteChunks;
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $path = $knowledgeFile->meta['embeddings_path'] ?? null;

        if (! $path) {
            return [];
        }

        $disk = Storage::disk($knowledgeFile->disk);

        if (! $disk->exists($path)) {
            return [];
        }

        $decoded = json_decode($disk->get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<int, float>  $embedding
     * @return array<int, array<string, mixed>>
     */
    public function searchSimilarChunks(Agent $agent, array $embedding, int $limit = 5): array
    {
        if (! $this->isConfigured($agent) || $embedding === []) {
            return [];
        }

        $qdrant = $this->agentProviderConfigService->qdrantConfig($agent);

        $response = $this->http($qdrant)
            ->post('/collections/'.$this->collectionName($agent).'/points/query', [
                'query' => array_values($embedding),
                'limit' => $limit,
                'with_payload' => true,
                'filter' => [
                    'must' => [
                        [
                            'key' => 'agent_id',
                            'match' => [
                                'value' => $agent->id,
                            ],
                        ],
                    ],
                ],
            ])
            ->throw()
            ->json();

        return collect($response['result']['points'] ?? $response['result'] ?? [])
            ->map(function (array $point): array {
                $payload = $point['payload'] ?? [];

                return [
                    'knowledge_file_id' => $payload['knowledge_file_id'] ?? null,
                    'knowledge_file_name' => $payload['knowledge_file_name'] ?? null,
                    'chunk_index' => $payload['chunk_index'] ?? null,
                    'content' => $payload['content'] ?? '',
                    'score' => (float) ($point['score'] ?? 0),
                ];
            })
            ->filter(fn (array $chunk): bool => $chunk['content'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $payload
     * @return array<int, string>
     */
    protected function upsertToQdrant(KnowledgeFile $knowledgeFile, array $payload, ?Agent $agent = null): array
    {
        $firstEmbedding = $payload[0]['embedding'] ?? [];

        if ($firstEmbedding === []) {
            throw new \RuntimeException('Cannot store embeddings in Qdrant without vector data.');
        }

        $this->ensureCollection(count($firstEmbedding), $agent);

        $points = [];
        $pointIds = [];

        foreach ($payload as $item) {
            $pointId = (string) Str::uuid();
            $pointIds[] = $pointId;
            $points[] = [
                'id' => $pointId,
                'vector' => array_values(array_map('floatval', $item['embedding'] ?? [])),
                'payload' => [
                    'agent_id' => $knowledgeFile->agent_id,
                    'knowledge_file_id' => $knowledgeFile->id,
                    'knowledge_file_name' => $knowledgeFile->original_name,
                    'chunk_index' => $item['index'] ?? null,
                    'content' => $item['content'] ?? '',
                    'length' => $item['length'] ?? null,
                ],
            ];
        }

        $this->http($this->agentProviderConfigService->qdrantConfig($agent))
            ->put('/collections/'.$this->collectionName($agent).'/points', [
                'points' => $points,
            ])
            ->throw();

        return $pointIds;
    }

    /**
     * @param  array<int, array<string, mixed>>  $payload
     */
    protected function storeLocally(KnowledgeFile $knowledgeFile, array $payload): string
    {
        $disk = Storage::disk($knowledgeFile->disk);
        $baseDirectory = 'knowledge-vectors/'.$knowledgeFile->agent_id;
        $path = $baseDirectory.'/'.Str::uuid()->toString().'-embeddings.json';

        $disk->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function loadFromQdrant(KnowledgeFile $knowledgeFile, ?Agent $agent = null): array
    {
        $response = $this->http($this->agentProviderConfigService->qdrantConfig($agent))
            ->post('/collections/'.$this->collectionName($agent).'/points/scroll', [
                'limit' => 256,
                'with_payload' => true,
                'with_vector' => true,
                'filter' => [
                    'must' => [
                        [
                            'key' => 'knowledge_file_id',
                            'match' => [
                                'value' => $knowledgeFile->id,
                            ],
                        ],
                    ],
                ],
            ])
            ->throw()
            ->json();

        return collect($response['result']['points'] ?? [])
            ->map(function (array $point): array {
                $payload = $point['payload'] ?? [];

                return [
                    'index' => $payload['chunk_index'] ?? null,
                    'content' => $payload['content'] ?? '',
                    'length' => $payload['length'] ?? null,
                    'embedding' => array_map('floatval', $point['vector'] ?? []),
                ];
            })
            ->filter(fn (array $chunk): bool => $chunk['content'] !== '')
            ->sortBy('index')
            ->values()
            ->all();
    }

    protected function ensureCollection(int $dimensions, ?Agent $agent = null): void
    {
        $qdrant = $this->agentProviderConfigService->qdrantConfig($agent);
        $response = $this->http($qdrant)->get('/collections/'.$this->collectionName($agent));

        if ($response->successful()) {
            return;
        }

        if ($response->status() !== 404) {
            $response->throw();
        }

        $this->http($qdrant)
            ->put('/collections/'.$this->collectionName($agent), [
                'vectors' => [
                    'size' => $dimensions,
                    'distance' => $qdrant['distance'],
                ],
            ])
            ->throw();
    }

    protected function collectionName(?Agent $agent = null): string
    {
        return (string) ($this->agentProviderConfigService->qdrantConfig($agent)['collection'] ?? 'k_agent_knowledge');
    }

    /**
     * @param  array{url: string|null, api_key: string|null, collection: string|null, timeout: int, distance: string}  $qdrant
     */
    protected function http(array $qdrant)
    {
        $request = Http::baseUrl(rtrim((string) ($qdrant['url'] ?? ''), '/'))
            ->timeout((int) ($qdrant['timeout'] ?? 15))
            ->acceptJson();

        if (filled($qdrant['api_key'] ?? null)) {
            $request = $request->withHeaders([
                'api-key' => $qdrant['api_key'],
            ]);
        }

        return $request;
    }

    protected function resolveAgentForKnowledgeFile(KnowledgeFile $knowledgeFile): ?Agent
    {
        if ($knowledgeFile->relationLoaded('agent')) {
            return $knowledgeFile->agent;
        }

        return $knowledgeFile->agent()->first();
    }
}
