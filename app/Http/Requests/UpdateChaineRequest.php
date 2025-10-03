<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChaineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'channel_id' => ['nullable', 'string', 'max:255'],
            'youtube_url' => ['required', 'url:http,https'],
        ];
    }
}
