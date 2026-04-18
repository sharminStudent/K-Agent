<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\KnowledgeFile;

class RetrievalService
{
    public function __construct(
        protected EmbeddingService $embeddingService,
        protected VectorStoreService $vectorStoreService,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function retrieveRelevantChunks(Agent $agent, string $query, ?int $limit = null): array
    {
        $limit ??= (int) config('services.rag.top_k', 5);

        $knowledgeFiles = KnowledgeFile::query()
            ->where('agent_id', $agent->id)
            ->where('status', 'ready')
            ->orderByDesc('ingested_at')
            ->get();

        if ($knowledgeFiles->isEmpty()) {
            return [];
        }

        $queryEmbedding = null;

        if ($this->embeddingService->isConfigured($agent)) {
            try {
                $queryEmbedding = $this->embeddingService->embedText($query, $agent);
            } catch (\Throwable) {
                $queryEmbedding = null;
            }
        }

        if ($queryEmbedding !== null && $this->vectorStoreService->isConfigured($agent)) {
            try {
                $remoteMatches = $this->vectorStoreService->searchSimilarChunks($agent, $queryEmbedding, $limit);

                if ($remoteMatches !== []) {
                    return $remoteMatches;
                }
            } catch (\Throwable) {
                // Fall back to file-backed retrieval when the external vector store is unavailable.
            }
        }

        $candidates = [];

        foreach ($knowledgeFiles as $knowledgeFile) {
            $chunks = $this->vectorStoreService->loadKnowledgeEmbeddings($knowledgeFile);

            if ($chunks === []) {
                $chunks = $this->loadProcessedChunks($knowledgeFile);
            }

            foreach ($chunks as $chunk) {
                $content = (string) ($chunk['content'] ?? '');

                if ($content === '') {
                    continue;
                }

                $score = $queryEmbedding && ! empty($chunk['embedding'])
                    ? $this->cosineSimilarity($queryEmbedding, array_map('floatval', $chunk['embedding']))
                    : (float) $this->keywordScore($query, $content);

                if ($score < $this->minimumScore($queryEmbedding !== null)) {
                    continue;
                }

                $candidates[] = [
                    'knowledge_file_id' => $knowledgeFile->id,
                    'knowledge_file_name' => $knowledgeFile->original_name,
                    'chunk_index' => $chunk['index'] ?? null,
                    'content' => $content,
                    'score' => $score,
                ];
            }
        }

        usort($candidates, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($candidates, 0, $limit);
    }

    protected function minimumScore(bool $usingEmbeddings): float
    {
        return $usingEmbeddings ? 0.15 : (float) config('services.rag.min_keyword_score', 1);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function loadProcessedChunks(KnowledgeFile $knowledgeFile): array
    {
        $path = $knowledgeFile->meta['processed_chunks_path'] ?? null;

        if (! $path) {
            return [];
        }

        $disk = \Illuminate\Support\Facades\Storage::disk($knowledgeFile->disk);

        if (! $disk->exists($path)) {
            return [];
        }

        $decoded = json_decode($disk->get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function keywordScore(string $query, string $content): int
    {
        $tokens = collect(preg_split('/[^a-z0-9]+/i', mb_strtolower($query)) ?: [])
            ->filter(fn (string $token): bool => mb_strlen($token) > 2)
            ->unique();

        $haystack = mb_strtolower($content);
        $score = 0;

        foreach ($tokens as $token) {
            if (str_contains($haystack, $token)) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            return 0.0;
        }

        $count = min(count($a), count($b));
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
