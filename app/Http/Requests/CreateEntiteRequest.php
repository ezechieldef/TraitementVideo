<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEntiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'in:INDIVIDUEL,GROUPE'],
            'type_contenu' => ['nullable', 'in:TUTORIEL,RELIGION,EDUCATION,AUTRE'],
        ];
    }
}
