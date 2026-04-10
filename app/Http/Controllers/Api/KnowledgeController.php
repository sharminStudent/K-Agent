<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessKnowledgeFileRequest;
use App\Http\Requests\StoreKnowledgeFileRequest;
use App\Models\KnowledgeFile;
use App\Services\KnowledgeService;
use Illuminate\Http\JsonResponse;

class KnowledgeController extends Controller
{
    public function __construct(
        protected KnowledgeService $knowledgeService,
    ) {
    }

    public function store(StoreKnowledgeFileRequest $request): JsonResponse
    {
        $knowledgeFile = $this->knowledgeService->storeUploadedFile($request->validated(), $request->file('file'));

        return response()->json([
            'data' => [
                'id' => $knowledgeFile->id,
                'agent_id' => $knowledgeFile->agent_id,
                'original_name' => $knowledgeFile->original_name,
                'mime_type' => $knowledgeFile->mime_type,
                'size' => $knowledgeFile->size,
                'status' => $knowledgeFile->status,
                'created_at' => $knowledgeFile->created_at?->toISOString(),
            ],
        ], 201);
    }

    public function process(ProcessKnowledgeFileRequest $request, KnowledgeFile $knowledgeFile): JsonResponse
    {
        $knowledgeFile = $this->knowledgeService->processKnowledgeFile($knowledgeFile, $request->validated());

        return response()->json([
            'data' => [
                'id' => $knowledgeFile->id,
                'agent_id' => $knowledgeFile->agent_id,
                'status' => $knowledgeFile->status,
                'chunk_count' => $knowledgeFile->meta['chunk_count'] ?? 0,
                'ingested_at' => $knowledgeFile->ingested_at?->toISOString(),
                'created_at' => $knowledgeFile->created_at?->toISOString(),
            ],
        ]);
    }
}
