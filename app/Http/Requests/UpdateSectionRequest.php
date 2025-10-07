<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
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
            'titre' => ['nullable', 'string', 'max:255'],
            'langue' => ['nullable', 'string', 'max:10'],
            'debut' => ['required', 'integer', 'min:0'],
            'fin' => ['required', 'integer', 'gt:debut'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'extract' => ['sometimes', 'boolean'],
        ];
    }
}
