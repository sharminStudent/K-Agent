<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;

class AgentProviderConfigService
{
    /**
     * @param  array<string, mixed>|null  $existingSettings
     * @param  array<string, mixed>|null  $providerSettings
     * @return array<string, mixed>
     */
    public function mergeProviderSettings(?array $existingSettings, ?array $providerSettings): array
    {
        $settings = $existingSettings ?? [];

        if ($providerSettings === null) {
            return $settings;
        }

        $providers = Arr::get($settings, 'provider_credentials', []);

        foreach (['openai', 'qdrant', 'railway'] as $provider) {
            $input = $providerSettings[$provider] ?? null;

            if (! is_array($input)) {
                continue;
            }

            $current = $providers[$provider] ?? [];
            $providers[$provider] = array_filter([
                'enabled' => (bool) ($input['enabled'] ?? false),
                'api_key' => $this->resolveSecretValue($input['api_key'] ?? null, $current['api_key'] ?? null),
                'base_url' => $provider === 'openai' ? $this->resolveNullableString($input['base_url'] ?? ($current['base_url'] ?? null)) : null,
                'chat_model' => $provider === 'openai' ? $this->resolveNullableString($input['chat_model'] ?? ($current['chat_model'] ?? null)) : null,
                'embedding_model' => $provider === 'openai' ? $this->resolveNullableString($input['embedding_model'] ?? ($current['embedding_model'] ?? null)) : null,
                'timeout' => $this->resolveNullableInt($input['timeout'] ?? ($current['timeout'] ?? null)),
                'collection' => $provider === 'qdrant' ? $this->resolveNullableString($input['collection'] ?? ($current['collection'] ?? null)) : null,
                'distance' => $provider === 'qdrant' ? $this->resolveNullableString($input['distance'] ?? ($current['distance'] ?? null)) : null,
                'project_id' => $provider === 'railway' ? $this->resolveNullableString($input['project_id'] ?? ($current['project_id'] ?? null)) : null,
                'environment_id' => $provider === 'railway' ? $this->resolveNullableString($input['environment_id'] ?? ($current['environment_id'] ?? null)) : null,
                'service_id' => $provider === 'railway' ? $this->resolveNullableString($input['service_id'] ?? ($current['service_id'] ?? null)) : null,
                'secret_configured' => $this->hasSecret($input['api_key'] ?? null, $current['api_key'] ?? null),
            ], static fn (mixed $value): bool => $value !== null);
        }

        $settings['provider_credentials'] = $providers;

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function sanitizedProviderSettings(?Agent $agent): array
    {
        $providers = $agent?->settings['provider_credentials'] ?? [];

        return [
            'openai' => [
                'enabled' => (bool) ($providers['openai']['enabled'] ?? false),
                'has_api_key' => filled($providers['openai']['api_key'] ?? null),
                'base_url' => $providers['openai']['base_url'] ?? null,
                'chat_model' => $providers['openai']['chat_model'] ?? null,
                'embedding_model' => $providers['openai']['embedding_model'] ?? null,
                'timeout' => $providers['openai']['timeout'] ?? null,
            ],
            'qdrant' => [
                'enabled' => (bool) ($providers['qdrant']['enabled'] ?? false),
                'has_api_key' => filled($providers['qdrant']['api_key'] ?? null),
                'url' => $providers['qdrant']['base_url'] ?? null,
                'collection' => $providers['qdrant']['collection'] ?? null,
                'timeout' => $providers['qdrant']['timeout'] ?? null,
                'distance' => $providers['qdrant']['distance'] ?? null,
            ],
            'railway' => [
                'enabled' => (bool) ($providers['railway']['enabled'] ?? false),
                'has_api_key' => filled($providers['railway']['api_key'] ?? null),
                'project_id' => $providers['railway']['project_id'] ?? null,
                'environment_id' => $providers['railway']['environment_id'] ?? null,
                'service_id' => $providers['railway']['service_id'] ?? null,
            ],
        ];
    }

    /**
     * @return array{api_key: string|null, base_url: string, chat_model: string|null, embedding_model: string|null, timeout: int}
     */
    public function openAiConfig(?Agent $agent = null): array
    {
        $provider = $agent?->settings['provider_credentials']['openai'] ?? [];
        $enabled = (bool) ($provider['enabled'] ?? false);

        return [
            'api_key' => $enabled
                ? ($this->decrypt($provider['api_key'] ?? null) ?: config('services.openai.api_key'))
                : config('services.openai.api_key'),
            'base_url' => $enabled
                ? ($provider['base_url'] ?? config('services.openai.base_url'))
                : (string) config('services.openai.base_url'),
            'chat_model' => $enabled
                ? ($provider['chat_model'] ?? config('services.openai.chat_model'))
                : config('services.openai.chat_model'),
            'embedding_model' => $enabled
                ? ($provider['embedding_model'] ?? config('services.openai.embedding_model'))
                : config('services.openai.embedding_model'),
            'timeout' => (int) ($enabled
                ? ($provider['timeout'] ?? config('services.openai.timeout', 30))
                : config('services.openai.timeout', 30)),
        ];
    }

    /**
     * @return array{url: string|null, api_key: string|null, collection: string|null, timeout: int, distance: string}
     */
    public function qdrantConfig(?Agent $agent = null): array
    {
        $provider = $agent?->settings['provider_credentials']['qdrant'] ?? [];
        $enabled = (bool) ($provider['enabled'] ?? false);

        return [
            'url' => $enabled
                ? ($provider['base_url'] ?? config('services.qdrant.url'))
                : config('services.qdrant.url'),
            'api_key' => $enabled
                ? ($this->decrypt($provider['api_key'] ?? null) ?: config('services.qdrant.api_key'))
                : config('services.qdrant.api_key'),
            'collection' => $enabled
                ? ($provider['collection'] ?? config('services.qdrant.collection'))
                : config('services.qdrant.collection'),
            'timeout' => (int) ($enabled
                ? ($provider['timeout'] ?? config('services.qdrant.timeout', 15))
                : config('services.qdrant.timeout', 15)),
            'distance' => (string) ($enabled
                ? ($provider['distance'] ?? config('services.qdrant.distance', 'Cosine'))
                : config('services.qdrant.distance', 'Cosine')),
        ];
    }

    /**
     * @param  mixed  $value
     */
    protected function resolveSecretValue(mixed $value, ?string $existing): ?string
    {
        if ($value === '__keep__') {
            return $existing;
        }

        if (! is_string($value)) {
            return $existing;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return Crypt::encryptString($trimmed);
    }

    /**
     * @param  mixed  $value
     */
    protected function resolveNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  mixed  $value
     */
    protected function resolveNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  mixed  $inputValue
     */
    protected function hasSecret(mixed $inputValue, ?string $existing): bool
    {
        if ($inputValue === '__keep__') {
            return filled($existing);
        }

        if (is_string($inputValue)) {
            return trim($inputValue) !== '';
        }

        return filled($existing);
    }

    protected function decrypt(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }
}
