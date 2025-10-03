<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportVideoFromUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'entite_id' => ['required', 'integer'],
            'url' => ['required', 'string', 'url:http,https'],
            'titre' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'entite_id.required' => "L'entité est obligatoire.",
            'url.required' => "L'URL est obligatoire.",
            'url.url' => 'Veuillez saisir une URL valide.',
        ];
    }
}
