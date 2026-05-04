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
        protected ActivityLogService $activityLogService,
        protected UsageTrackingService $usageTrackingService,
    ) {}

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
                'login_logo_path' => $data['login_logo_path'] ?? null,
                'light_logo_path' => $data['light_logo_path'] ?? null,
                'dark_logo_path' => $data['dark_logo_path'] ?? null,
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

            $this->activityLogService->log(
                event: 'agent.created',
                description: 'A workspace agent was created.',
                category: 'admin',
                agent: $agent,
                user: $owner,
                subject: $agent,
                meta: [
                    'summary' => $agent->company_name ?: $agent->name,
                ],
            );

            return $agent;
        });
    }

    public function updateAgent(Agent $agent, array $data): Agent
    {
        return DB::transaction(function () use ($agent, $data): Agent {
            $agent->fill([
                'name' => array_key_exists('name', $data) ? $data['name'] : $agent->name,
                'company_name' => array_key_exists('company_name', $data) ? $data['company_name'] : $agent->company_name,
                'slug' => array_key_exists('slug', $data) ? $data['slug'] : $agent->slug,
                'website_url' => array_key_exists('website_url', $data) ? $data['website_url'] : $agent->website_url,
                'industry' => array_key_exists('industry', $data) ? $data['industry'] : $agent->industry,
                'company_description' => array_key_exists('company_description', $data) ? $data['company_description'] : $agent->company_description,
                'logo_path' => array_key_exists('logo_path', $data) ? $data['logo_path'] : $agent->logo_path,
                'login_logo_path' => array_key_exists('login_logo_path', $data) ? $data['login_logo_path'] : $agent->login_logo_path,
                'light_logo_path' => array_key_exists('light_logo_path', $data) ? $data['light_logo_path'] : $agent->light_logo_path,
                'dark_logo_path' => array_key_exists('dark_logo_path', $data) ? $data['dark_logo_path'] : $agent->dark_logo_path,
                'contact_email' => array_key_exists('contact_email', $data) ? $data['contact_email'] : $agent->contact_email,
                'support_email' => array_key_exists('support_email', $data) ? $data['support_email'] : $agent->support_email,
                'support_phone' => array_key_exists('support_phone', $data) ? $data['support_phone'] : $agent->support_phone,
                'system_prompt' => array_key_exists('system_prompt', $data) ? $data['system_prompt'] : $agent->system_prompt,
                'welcome_message' => array_key_exists('welcome_message', $data) ? $data['welcome_message'] : $agent->welcome_message,
                'fallback_message' => array_key_exists('fallback_message', $data) ? $data['fallback_message'] : $agent->fallback_message,
                'settings' => $this->agentProviderConfigService->mergeProviderSettings(
                    $data['settings'] ?? $agent->settings,
                    $data['provider_settings'] ?? null,
                ),
                'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $agent->is_active,
            ]);

            $agent->save();

            $currentUser = auth()->user();

            $this->activityLogService->log(
                event: 'agent.updated',
                description: 'Workspace agent settings were updated.',
                category: 'admin',
                agent: $agent,
                user: $currentUser instanceof User ? $currentUser : null,
                subject: $agent,
                meta: [
                    'summary' => $agent->company_name ?: $agent->name,
                ],
            );

            return $agent->fresh();
        });
    }

    public function regenerateWidgetToken(Agent $agent): Agent
    {
        $agent->forceFill([
            'widget_token' => Str::random(40),
        ])->save();

        $currentUser = auth()->user();

        $this->activityLogService->log(
            event: 'agent.widget_token_regenerated',
            description: 'The widget token was regenerated.',
            category: 'security',
            agent: $agent,
            user: $currentUser instanceof User ? $currentUser : null,
            subject: $agent,
        );

        return $agent->fresh();
    }

    public function resolveActiveAgentByWidgetToken(string $widgetToken): Agent
    {
        $agent = Agent::query()
            ->where('widget_token', $widgetToken)
            ->first();

        if (! $agent || ! $agent->allowsWorkspaceAccess()) {
            throw new ModelNotFoundException('Agent not found for the provided widget token.');
        }

        return $this->usageTrackingService->syncCurrentBillingPeriod($agent);
    }
}
