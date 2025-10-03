<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChaineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'entite_id' => ['required', 'integer'],
            'titre' => ['required', 'string', 'max:255'],
            'channel_id' => ['required', 'string', 'max:255'],
            'youtube_url' => ['required', 'url:http,https'],
        ];
    }
}
