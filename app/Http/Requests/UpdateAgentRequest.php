<?php

namespace App\Http\Requests;

use App\Models\Agent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentRequest extends FormRequest
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
        /** @var Agent $agent */
        $agent = $this->route('agent');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'company_name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('agents', 'slug')->ignore($agent->id)],
            'website_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_description' => ['sometimes', 'nullable', 'string'],
            'logo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'support_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'support_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'system_prompt' => ['sometimes', 'nullable', 'string'],
            'welcome_message' => ['sometimes', 'nullable', 'string'],
            'fallback_message' => ['sometimes', 'nullable', 'string'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'provider_settings' => ['sometimes', 'nullable', 'array'],
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
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
