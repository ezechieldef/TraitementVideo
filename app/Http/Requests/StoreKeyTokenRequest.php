<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKeyTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:YOUTUBE,LLM'],
            'value' => ['required', 'string'],
            'entite_id' => ['required', 'integer'],
            'llm_id' => ['nullable', 'integer', 'exists:l_l_m_s,id', 'required_if:type,LLM'],
            'usage_limit_count' => ['nullable', 'integer', 'min:1'],
            'limit_periode_minutes' => ['nullable', 'integer', 'min:1'],
            'priority' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entite_id.required' => "L'entité est obligatoire.",
            'type.required' => 'Le type de clé est obligatoire.',
            'type.in' => 'Le type doit être YOUTUBE ou LLM.',
            'value.required' => 'La valeur de la clé est obligatoire.',
            'llm_id.exists' => 'Le modèle LLM sélectionné est invalide.',
        ];
    }
}
