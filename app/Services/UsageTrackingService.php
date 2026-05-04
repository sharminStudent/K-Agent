<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\DB;

class UsageTrackingService
{
    public function syncCurrentBillingPeriod(Agent $agent): Agent
    {
        return $this->withFreshAgent($agent, function (Agent $freshAgent): void {
            $this->resetMonthlyCountersIfNeeded($freshAgent);
        });
    }

    public function recordChatSession(Agent $agent): void
    {
        $this->incrementCounters($agent, [
            'monthly_chat_count' => 1,
        ]);
    }

    public function recordLead(Agent $agent): void
    {
        $this->incrementCounters($agent, [
            'monthly_lead_count' => 1,
        ]);
    }

    public function recordTokenUsage(Agent $agent, int $tokens): void
    {
        if ($tokens <= 0) {
            return;
        }

        $this->incrementCounters($agent, [
            'monthly_token_count' => $tokens,
            'api_request_count' => 1,
        ], [
            'last_api_request_at' => now(),
        ]);
    }

    public function recordProviderFailure(Agent $agent): void
    {
        $this->withFreshAgent($agent, function (Agent $freshAgent): void {
            $this->resetMonthlyCountersIfNeeded($freshAgent);

            $freshAgent->forceFill([
                'last_error_at' => now(),
            ])->save();
        });
    }

    /**
     * @param  array<string, int>  $increments
     * @param  array<string, mixed>  $extraAttributes
     */
    protected function incrementCounters(Agent $agent, array $increments, array $extraAttributes = []): void
    {
        $this->withFreshAgent($agent, function (Agent $freshAgent) use ($increments, $extraAttributes): void {
            $this->resetMonthlyCountersIfNeeded($freshAgent);

            $attributes = $extraAttributes;

            foreach ($increments as $column => $amount) {
                $currentValue = (int) ($freshAgent->{$column} ?? 0);
                $attributes[$column] = max(0, $currentValue + max(0, $amount));
            }

            $freshAgent->forceFill($attributes)->save();
        });
    }

    protected function resetMonthlyCountersIfNeeded(Agent $agent): void
    {
        $settings = is_array($agent->settings) ? $agent->settings : [];
        $currentPeriod = now()->format('Y-m');
        $storedPeriod = (string) data_get($settings, 'billing.current_usage_period', '');

        if ($storedPeriod === $currentPeriod) {
            return;
        }

        data_set($settings, 'billing.current_usage_period', $currentPeriod);

        $agent->forceFill([
            'settings' => $settings,
            'monthly_chat_count' => 0,
            'monthly_lead_count' => 0,
            'monthly_token_count' => 0,
        ])->save();
    }

    /**
     * @param  callable(Agent): void  $callback
     */
    protected function withFreshAgent(Agent $agent, callable $callback): Agent
    {
        return DB::transaction(function () use ($agent, $callback): Agent {
            /** @var Agent $freshAgent */
            $freshAgent = Agent::query()->lockForUpdate()->findOrFail($agent->id);
            $callback($freshAgent);

            return $freshAgent->fresh();
        });
    }
}
