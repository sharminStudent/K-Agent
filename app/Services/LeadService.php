<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function __construct(
        protected AgentService $agentService,
    ) {
    }

    public function storeLead(array $data): Lead
    {
        $agent = $this->agentService->resolveActiveAgentByWidgetToken($data['widget_token']);

        $chatSession = null;

        if (! empty($data['session_id'])) {
            /** @var ChatSession $chatSession */
            $chatSession = ChatSession::query()
                ->where('public_id', $data['session_id'])
                ->where('agent_id', $agent->id)
                ->firstOrFail();
        }

        return DB::transaction(function () use ($agent, $chatSession, $data): Lead {
            return Lead::query()->create([
                'agent_id' => $agent->id,
                'chat_session_id' => $chatSession?->id,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => 'new',
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);
        });
    }
}
