<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentService
{
    public function __construct(
        protected AgentProviderConfigService $agentProviderConfigService,
    ) {
    }

    public function createAgent(array $data, User $owner): Agent
    {
        if ($owner->agent_id !== null) {
            throw new AuthorizationException('The authenticated user is already assigned to an agent.');
        }

        return DB::transaction(function () use ($data, $owner): Agent {
            $agent = Agent::query()->create([
                'name' => $data['name'],
                'company_name' => $data['company_name'],
                'slug' => $data['slug'] ?? null,
                'website_url' => $data['website_url'] ?? null,
                'industry' => $data['industry'] ?? null,
                'company_description' => $data['company_description'] ?? null,
                'logo_path' => $data['logo_path'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'support_email' => $data['support_email'] ?? null,
                'support_phone' => $data['support_phone'] ?? null,
                'system_prompt' => $data['system_prompt'] ?? null,
                'welcome_message' => $data['welcome_message'] ?? null,
                'fallback_message' => $data['fallback_message'] ?? null,
                'settings' => $this->agentProviderConfigService->mergeProviderSettings(
                    $data['settings'] ?? null,
                    $data['provider_settings'] ?? null,
                ),
                'is_active' => $data['is_active'] ?? true,
            ]);

            $owner->forceFill([
                'agent_id' => $agent->id,
            ])->save();

            return $agent;
        });
    }

    public function updateAgent(Agent $agent, array $data): Agent
    {
        return DB::transaction(function () use ($agent, $data): Agent {
            $agent->fill([
                'name' => $data['name'] ?? $agent->name,
                'company_name' => $data['company_name'] ?? $agent->company_name,
                'slug' => $data['slug'] ?? $agent->slug,
                'website_url' => $data['website_url'] ?? $agent->website_url,
                'industry' => $data['industry'] ?? $agent->industry,
                'company_description' => $data['company_description'] ?? $agent->company_description,
                'logo_path' => $data['logo_path'] ?? $agent->logo_path,
                'contact_email' => $data['contact_email'] ?? $agent->contact_email,
                'support_email' => $data['support_email'] ?? $agent->support_email,
                'support_phone' => $data['support_phone'] ?? $agent->support_phone,
                'system_prompt' => $data['system_prompt'] ?? $agent->system_prompt,
                'welcome_message' => $data['welcome_message'] ?? $agent->welcome_message,
                'fallback_message' => $data['fallback_message'] ?? $agent->fallback_message,
                'settings' => $this->agentProviderConfigService->mergeProviderSettings(
                    $data['settings'] ?? $agent->settings,
                    $data['provider_settings'] ?? null,
                ),
                'is_active' => $data['is_active'] ?? $agent->is_active,
            ]);

            $agent->save();

            return $agent->fresh();
        });
    }

    public function regenerateWidgetToken(Agent $agent): Agent
    {
        $agent->forceFill([
            'widget_token' => Str::random(40),
        ])->save();

        return $agent->fresh();
    }

    public function resolveActiveAgentByWidgetToken(string $widgetToken): Agent
    {
        $agent = Agent::query()
            ->where('widget_token', $widgetToken)
            ->where('is_active', true)
            ->first();

        if (! $agent) {
            throw new ModelNotFoundException('Agent not found for the provided widget token.');
        }

        return $agent;
    }
}
