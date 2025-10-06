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
            // Ensure each non-empty line starts with [HH:MM:SS]
            'contenu' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $lines = preg_split("/(\r\n|\n|\r)/", (string) $value);
                    foreach ($lines as $index => $line) {
                        if (trim($line) === '') {
                            continue;
                        }
                        if (! preg_match('/^\s*\[[0-9]{2}:[0-9]{2}:[0-9]{2}\]\s+.+$/', $line)) {
                            $fail('Chaque ligne doit commencer par un timecode au format [HH:MM:SS]. Erreur à la ligne '.($index + 1).'.');

                            return;
                        }
                    }
                },
            ],
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
