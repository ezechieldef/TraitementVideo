<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'entite_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:SECTION,RESUME,section,resume'],
            'categorie' => ['nullable', 'string', 'max:100'],
            'titre' => ['required', 'string', 'max:255'],
            'contenu' => ['required', 'string'],
            'langue' => ['nullable', 'string', 'max:10'],
            'is_default' => ['sometimes', 'boolean'],
            'visible' => ['sometimes', 'boolean'],
        ];
    }
}
