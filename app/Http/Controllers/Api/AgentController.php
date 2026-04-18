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
        protected \App\Services\AgentProviderConfigService $agentProviderConfigService,
    ) {
    }

    public function store(StoreAgentRequest $request): JsonResponse
    {
        $this->authorize('create', Agent::class);

        $agent = $this->agentService->createAgent($request->validated(), $request->user());

        return response()->json([
            'data' => $this->formatAgent($agent),
        ], 201);
    }

    public function show(Agent $agent): JsonResponse
    {
        $this->authorize('view', $agent);

        return response()->json([
            'data' => $this->formatAgent($agent),
        ]);
    }

    public function update(UpdateAgentRequest $request, Agent $agent): JsonResponse
    {
        $this->authorize('update', $agent);

        $agent = $this->agentService->updateAgent($agent, $request->validated());

        return response()->json([
            'data' => $this->formatAgent($agent),
        ]);
    }

    public function regenerateWidgetToken(Agent $agent): JsonResponse
    {
        $this->authorize('regenerateWidgetToken', $agent);

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
        $settings = $agent->settings ?? [];
        unset($settings['provider_credentials']);

        return [
            'id' => $agent->id,
            'name' => $agent->name,
            'company_name' => $agent->company_name,
            'slug' => $agent->slug,
            'website_url' => $agent->website_url,
            'industry' => $agent->industry,
            'company_description' => $agent->company_description,
            'logo_path' => $agent->logo_path,
            'logo_url' => $agent->logo_url,
            'widget_token' => $agent->widget_token,
            'contact_email' => $agent->contact_email,
            'support_email' => $agent->support_email,
            'support_phone' => $agent->support_phone,
            'system_prompt' => $agent->system_prompt,
            'welcome_message' => $agent->welcome_message,
            'fallback_message' => $agent->fallback_message,
            'settings' => $settings,
            'provider_settings' => $this->agentProviderConfigService->sanitizedProviderSettings($agent),
            'is_active' => $agent->is_active,
            'created_at' => $agent->created_at?->toISOString(),
            'updated_at' => $agent->updated_at?->toISOString(),
        ];
    }
}
