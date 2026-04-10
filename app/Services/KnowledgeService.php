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
    ) {
    }

    public function storeUploadedFile(array $data, UploadedFile $uploadedFile): KnowledgeFile
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($data['widget_token']);
        $disk = config('filesystems.default', 'local');
        $directory = 'knowledge-files/'.$agent->id;
        $filename = Str::uuid()->toString().'-'.$uploadedFile->getClientOriginalName();
        $path = $uploadedFile->storeAs($directory, $filename, $disk);

        return DB::transaction(function () use ($agent, $uploadedFile, $path, $disk, $data): KnowledgeFile {
            return KnowledgeFile::query()->create([
                'agent_id' => $agent->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => $uploadedFile->getSize(),
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

        return DB::transaction(function () use ($knowledgeFile): KnowledgeFile {
            $knowledgeFile->forceFill([
                'status' => 'processing',
            ])->save();

            try {
                $text = $this->extractText($knowledgeFile);
                $chunks = $this->chunkText($text);
                $this->storeProcessingArtifacts($knowledgeFile, $text, $chunks);

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

    protected function extractText(KnowledgeFile $knowledgeFile): string
    {
        $disk = Storage::disk($knowledgeFile->disk);
        $raw = $disk->get($knowledgeFile->path);

        return match ($knowledgeFile->mime_type) {
            'text/plain' => trim($raw),
            'text/csv' => $this->extractCsvText($raw),
            'application/json' => $this->extractJsonText($raw),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => $this->extractDocxText($disk->path($knowledgeFile->path)),
            default => throw new \RuntimeException('Text extraction is not supported yet for this file type.'),
        };
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
