<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('agents', 'slug')],
            'website_url' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'company_description' => ['nullable', 'string'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'system_prompt' => ['nullable', 'string'],
            'welcome_message' => ['nullable', 'string'],
            'fallback_message' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'provider_settings' => ['nullable', 'array'],
            'provider_settings.openai' => ['nullable', 'array'],
            'provider_settings.openai.enabled' => ['nullable', 'boolean'],
            'provider_settings.openai.api_key' => ['nullable', 'string'],
            'provider_settings.openai.base_url' => ['nullable', 'url', 'max:255'],
            'provider_settings.openai.chat_model' => ['nullable', 'string', 'max:255'],
            'provider_settings.openai.embedding_model' => ['nullable', 'string', 'max:255'],
            'provider_settings.openai.timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'provider_settings.qdrant' => ['nullable', 'array'],
            'provider_settings.qdrant.enabled' => ['nullable', 'boolean'],
            'provider_settings.qdrant.api_key' => ['nullable', 'string'],
            'provider_settings.qdrant.base_url' => ['nullable', 'url', 'max:255'],
            'provider_settings.qdrant.collection' => ['nullable', 'string', 'max:255'],
            'provider_settings.qdrant.timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'provider_settings.qdrant.distance' => ['nullable', 'string', 'max:50'],
            'provider_settings.railway' => ['nullable', 'array'],
            'provider_settings.railway.enabled' => ['nullable', 'boolean'],
            'provider_settings.railway.api_key' => ['nullable', 'string'],
            'provider_settings.railway.project_id' => ['nullable', 'string', 'max:255'],
            'provider_settings.railway.environment_id' => ['nullable', 'string', 'max:255'],
            'provider_settings.railway.service_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
