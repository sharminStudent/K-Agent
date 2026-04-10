<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendChatMessageRequest extends FormRequest
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
            'widget_token' => ['required', 'string', 'max:255'],
            'session_id' => ['required', 'string', 'max:26'],
            'message' => ['required', 'string', 'max:5000'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
