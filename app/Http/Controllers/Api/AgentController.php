<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Models\Agent;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;

class AgentController extends Controller
{
    public function __construct(
        protected AgentService $agentService,
    ) {
    }

    public function store(StoreAgentRequest $request): JsonResponse
    {
        $agent = $this->agentService->createAgent($request->validated());

        return response()->json([
            'data' => $this->formatAgent($agent),
        ], 201);
    }

    public function show(Agent $agent): JsonResponse
    {
        return response()->json([
            'data' => $this->formatAgent($agent),
        ]);
    }

    public function update(UpdateAgentRequest $request, Agent $agent): JsonResponse
    {
        $agent = $this->agentService->updateAgent($agent, $request->validated());

        return response()->json([
            'data' => $this->formatAgent($agent),
        ]);
    }

    public function regenerateWidgetToken(Agent $agent): JsonResponse
    {
        $agent = $this->agentService->regenerateWidgetToken($agent);

        return response()->json([
            'data' => $this->formatAgent($agent),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatAgent(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'name' => $agent->name,
            'company_name' => $agent->company_name,
            'slug' => $agent->slug,
            'website_url' => $agent->website_url,
            'industry' => $agent->industry,
            'company_description' => $agent->company_description,
            'widget_token' => $agent->widget_token,
            'contact_email' => $agent->contact_email,
            'support_email' => $agent->support_email,
            'support_phone' => $agent->support_phone,
            'system_prompt' => $agent->system_prompt,
            'welcome_message' => $agent->welcome_message,
            'fallback_message' => $agent->fallback_message,
            'settings' => $agent->settings,
            'is_active' => $agent->is_active,
            'created_at' => $agent->created_at?->toISOString(),
            'updated_at' => $agent->updated_at?->toISOString(),
        ];
    }
}
