<?php

namespace App\Http\Requests;

use App\Models\Agent;
use App\Models\KnowledgeFile;
use Illuminate\Foundation\Http\FormRequest;

class ProcessKnowledgeFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $widgetToken = (string) $this->input('widget_token');
        $knowledgeFile = $this->route('knowledgeFile');

        if (! $user || $widgetToken === '' || ! $knowledgeFile instanceof KnowledgeFile) {
            return false;
        }

        return $knowledgeFile->agent_id === $user->agent_id
            && Agent::query()
                ->where('widget_token', $widgetToken)
                ->where('is_active', true)
                ->whereKey($user->agent_id)
                ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'widget_token' => ['required', 'string', 'max:255'],
        ];
    }
}
