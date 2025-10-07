<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in the controller using the video/entité membership.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'titre' => ['nullable', 'string', 'max:255'],
            'langue' => ['nullable', 'string', 'max:10'],
            'debut' => ['required', 'integer', 'min:0'],
            'fin' => ['required', 'integer', 'gt:debut'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'extract' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'debut.required' => 'Le début est requis.',
            'fin.required' => 'La fin est requise.',
            'fin.gt' => 'La fin doit être supérieure au début.',
        ];
    }
}
