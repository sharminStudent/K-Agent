<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateChatSessionRequest;
use App\Http\Requests\SendChatMessageRequest;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService,
    ) {
    }

    public function createSession(CreateChatSessionRequest $request): JsonResponse
    {
        $chatSession = $this->chatService->createSession($request->validated());

        return response()->json([
            'data' => [
                'session_id' => $chatSession->public_id,
                'status' => $chatSession->status,
                'created_at' => $chatSession->created_at?->toISOString(),
            ],
        ], 201);
    }

    public function sendMessage(SendChatMessageRequest $request): JsonResponse
    {
        [$chatSession, $message, $assistantMessage] = $this->chatService->storeVisitorMessage($request->validated());

        return response()->json([
            'data' => [
                'session_id' => $chatSession->public_id,
                'message_id' => $message->public_id,
                'role' => $message->role,
                'content' => $message->content,
                'assistant_message' => [
                    'message_id' => $assistantMessage->public_id,
                    'role' => $assistantMessage->role,
                    'content' => $assistantMessage->content,
                    'created_at' => $assistantMessage->created_at?->toISOString(),
                ],
                'created_at' => $message->created_at?->toISOString(),
            ],
        ], 201);
    }
}
