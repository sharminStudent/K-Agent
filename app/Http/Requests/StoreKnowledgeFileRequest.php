<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeFileRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:application/pdf,text/plain,text/csv,application/json,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword',
            ],
            'meta' => ['nullable', 'array'],
        ];
    }
}
