<?php

namespace App\Services;

use App\Models\KnowledgeFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class KnowledgeService
{
    public function __construct(
        protected AgentService $agentService,
        protected EmbeddingService $embeddingService,
        protected VectorStoreService $vectorStoreService,
    ) {
    }

    public function storeUploadedFile(array $data, UploadedFile $uploadedFile): KnowledgeFile
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($data['widget_token']);
        $disk = config('filesystems.default', 'local');
        $directory = 'knowledge-files/'.$agent->id;
        $filename = Str::uuid()->toString().'-'.$uploadedFile->getClientOriginalName();
        $path = $uploadedFile->storeAs($directory, $filename, $disk);
        $storedSize = Storage::disk($disk)->size($path);

        return DB::transaction(function () use ($agent, $uploadedFile, $path, $disk, $data, $storedSize): KnowledgeFile {
            return KnowledgeFile::query()->create([
                'agent_id' => $agent->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => $storedSize,
                'status' => 'pending',
                'meta' => array_merge($data['meta'] ?? [], [
                    'uploaded_extension' => $uploadedFile->getClientOriginalExtension(),
                ]),
            ]);
        });
    }

    public function processKnowledgeFile(KnowledgeFile $knowledgeFile, array $data): KnowledgeFile
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($data['widget_token']);

        if ($knowledgeFile->agent_id !== $agent->id) {
            throw new ModelNotFoundException('Knowledge file not found for the provided widget token.');
        }

        return DB::transaction(function () use ($knowledgeFile, $agent): KnowledgeFile {
            $knowledgeFile->forceFill([
                'status' => 'processing',
            ])->save();

            try {
                $text = $this->extractText($knowledgeFile);
                $chunks = $this->chunkText($text);
                $this->storeProcessingArtifacts($knowledgeFile, $text, $chunks);
                $this->storeEmbeddings($knowledgeFile, $chunks, $agent);

                $knowledgeFile->forceFill([
                    'status' => 'ready',
                    'ingested_at' => now(),
                    'meta' => array_merge($knowledgeFile->meta ?? [], [
                        'chunk_count' => count($chunks),
                        'extracted_characters' => mb_strlen($text),
                        'processing_error' => null,
                    ]),
                ])->save();
            } catch (\Throwable $exception) {
                $knowledgeFile->forceFill([
                    'status' => 'failed',
                    'meta' => array_merge($knowledgeFile->meta ?? [], [
                        'processing_error' => $exception->getMessage(),
                    ]),
                ])->save();

                throw $exception;
            }

            return $knowledgeFile->fresh();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $chunks
     */
    protected function storeEmbeddings(KnowledgeFile $knowledgeFile, array $chunks, \App\Models\Agent $agent): void
    {
        if (! $this->embeddingService->isConfigured($agent)) {
            $knowledgeFile->forceFill([
                'meta' => array_merge($knowledgeFile->meta ?? [], [
                    'embeddings_status' => 'skipped',
                    'embeddings_path' => null,
                    'vector_backend' => null,
                    'vector_collection' => null,
                    'vector_point_ids' => [],
                ]),
            ])->save();

            return;
        }

        $embeddings = $this->embeddingService->embedMany(array_column($chunks, 'content'), $agent);
        $stored = $this->vectorStoreService->storeKnowledgeEmbeddings($knowledgeFile, $chunks, $embeddings);

        $knowledgeFile->forceFill([
            'meta' => array_merge($knowledgeFile->meta ?? [], [
                'embeddings_status' => 'ready',
                'embeddings_path' => $stored['path'],
                'embedding_count' => $stored['count'],
                'vector_backend' => $stored['backend'],
                'vector_collection' => $stored['collection'],
                'vector_point_ids' => $stored['point_ids'],
            ]),
        ])->save();
    }

    protected function extractText(KnowledgeFile $knowledgeFile): string
    {
        $disk = Storage::disk($knowledgeFile->disk);
        $raw = $disk->get($knowledgeFile->path);
        $mimeType = (string) $knowledgeFile->mime_type;
        $extension = mb_strtolower((string) ($knowledgeFile->meta['uploaded_extension'] ?? pathinfo($knowledgeFile->original_name, PATHINFO_EXTENSION)));

        if ($mimeType === 'text/csv' || $extension === 'csv') {
            return $this->extractCsvText($raw);
        }

        if ($mimeType === 'application/json' || $extension === 'json') {
            return $this->extractJsonText($raw);
        }

        if (
            $mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            || in_array($extension, ['docx'], true)
        ) {
            return $this->extractDocxText($disk->path($knowledgeFile->path));
        }

        if (
            str_starts_with($mimeType, 'text/')
            || in_array($extension, ['txt', 'md', 'log', 'text'], true)
        ) {
            return trim($raw);
        }

        throw new \RuntimeException('Text extraction is not supported yet for this file type.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function chunkText(string $text, int $chunkSize = 800, int $overlap = 120): array
    {
        $normalizedText = preg_replace('/\s+/', ' ', trim($text)) ?? '';

        if ($normalizedText === '') {
            throw new \RuntimeException('No readable text could be extracted from this file.');
        }

        $chunks = [];
        $length = mb_strlen($normalizedText);
        $offset = 0;
        $index = 0;

        while ($offset < $length) {
            $chunk = mb_substr($normalizedText, $offset, $chunkSize);
            $chunks[] = [
                'index' => $index,
                'content' => $chunk,
                'length' => mb_strlen($chunk),
            ];

            $offset += max(1, $chunkSize - $overlap);
            $index++;
        }

        return $chunks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $chunks
     */
    protected function storeProcessingArtifacts(KnowledgeFile $knowledgeFile, string $text, array $chunks): void
    {
        $disk = Storage::disk($knowledgeFile->disk);
        $baseDirectory = 'knowledge-processed/'.$knowledgeFile->agent_id;

        $textPath = $baseDirectory.'/'.Str::uuid()->toString().'-text.txt';
        $chunksPath = $baseDirectory.'/'.Str::uuid()->toString().'-chunks.json';

        $disk->put($textPath, $text);
        $disk->put($chunksPath, json_encode($chunks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $knowledgeFile->forceFill([
            'meta' => array_merge($knowledgeFile->meta ?? [], [
                'processed_text_path' => $textPath,
                'processed_chunks_path' => $chunksPath,
            ]),
        ])->save();
    }

    protected function extractCsvText(string $raw): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];
        $rows = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $rows[] = implode(' | ', str_getcsv($line));
        }

        return implode("\n", $rows);
    }

    protected function extractJsonText(string $raw): string
    {
        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Uploaded JSON knowledge file is invalid.');
        }

        $json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? '' : $json;
    }

    protected function extractDocxText(string $path): string
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open DOCX file for text extraction.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($documentXml === false) {
            throw new \RuntimeException('DOCX file does not contain readable document data.');
        }

        $text = strip_tags(str_replace('</w:p>', "\n", $documentXml));

        return trim(html_entity_decode($text));
    }
}
