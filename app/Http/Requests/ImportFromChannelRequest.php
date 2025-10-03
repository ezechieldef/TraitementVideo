<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportFromChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'entite_id' => ['required', 'integer'],
            'chaine_id' => ['required', 'integer'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'live' => ['sometimes', 'boolean'],
            'uploaded' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'entite_id.required' => "L'entité est obligatoire.",
            'chaine_id.required' => 'Veuillez sélectionner une chaîne.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}
