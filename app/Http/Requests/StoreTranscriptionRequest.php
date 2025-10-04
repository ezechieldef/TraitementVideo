<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTranscriptionRequest extends FormRequest
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
            'langue' => ['required', 'string', 'max:10'],
            'contenu' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'langue.required' => 'La langue est requise.',
            'contenu.required' => 'Le contenu de la transcription est requis.',
        ];
    }
}
