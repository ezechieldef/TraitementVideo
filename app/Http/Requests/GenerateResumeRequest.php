<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'token_id' => ['required', 'integer', 'exists:key_tokens,id'],
            'custom_instruction' => ['required', 'string', 'max:2000'],
            'langue' => ['nullable', 'string', 'max:10'],
        ];
    }
}
