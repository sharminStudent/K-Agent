<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function __construct(
        protected AgentService $agentService,
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

        return DB::transaction(function () use ($chatSession, $agent, $data): array {
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
    }
}
