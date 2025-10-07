<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the google-gemini-php/laravel package.
 *
 * Contract (inputs):
 * - apiKey: string|null  Optional per-request API key (falls back to config if null)
 * - model: string        Gemini model (e.g., 'gemini-2.0-flash')
 * - messages: array<int, array{role:string, content:string}>|null  Chat-style messages
 * - text: string|null    Raw text prompt; used if provided, else built from messages
 * - generationConfig: array<string, mixed>|null  Optional Gemini generation config
 *
 * Output: string content returned by the model (as text)
 */
class GeminiCall
{
    public function generate(array $params): string
    {
        if (! class_exists('Gemini\\Laravel\\Facades\\Gemini')) {
            throw new \RuntimeException('Gemini Laravel package not installed or not autoloaded.');
        }

        $apiKey = (string) ($params['apiKey'] ?? '');
        $model = (string) ($params['model'] ?? '');
        $messages = $params['messages'] ?? null;
        $text = (string) ($params['text'] ?? '');
        $generationConfig = (array) ($params['generationConfig'] ?? []);

        if ($model === '') {
            throw new \InvalidArgumentException('Gemini model is required.');
        }

        if ($text === '') {
            $text = $this->buildTextFromMessages(is_array($messages) ? $messages : []);
        }

        // Defaults kept for future use if needed; not applied directly to avoid SDK signature mismatches
        $generationConfig = array_merge([
            'temperature' => 0,
            'responseMimeType' => 'application/json',
        ], $generationConfig);

        // Select client: prefer per-request key if method exists
        $client = null;
        try {
            if ($apiKey !== '' && method_exists(\Gemini\Laravel\Facades\Gemini::class, 'client')) {
                /** @var mixed $client */
                $client = \Gemini\Laravel\Facades\Gemini::client($apiKey);
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini::client($apiKey) failed, using default configured client', ['error' => $e->getMessage()]);
            $client = null;
        }

        // Build generative model handler
        try {
            if ($client && method_exists($client, 'generativeModel')) {
                $gen = $client->generativeModel(model: $model);
            } else {
                $gen = \Gemini\Laravel\Facades\Gemini::generativeModel(model: $model);
            }
            // Note: We avoid applying generation config here to prevent SDK "Unhandled match case" issues.
            // If needed, we can append constraints directly in the prompt text.
        } catch (\Throwable $e) {
            throw new \RuntimeException('Unable to initialize Gemini generative model: '.$e->getMessage(), 0, $e);
        }

        // Invoke model
        try {
            /** @var mixed $response */
            $response = $gen->generateContent($text);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Gemini generateContent failed: '.$e->getMessage(), 0, $e);
        }

        return $this->extractText($response);
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    private function buildTextFromMessages(array $messages): string
    {
        if (empty($messages)) {
            return '';
        }
        $parts = [];
        foreach ($messages as $m) {
            $content = trim((string) ($m['content'] ?? ''));
            if ($content !== '') {
                $parts[] = $content;
            }
        }

        return trim(implode("\n\n", $parts));
    }

    /**
     * Attempt to extract plain text from a Gemini SDK response.
     *
     * @param  mixed  $response
     */
    private function extractText($response): string
    {
        if (is_object($response) && method_exists($response, 'text')) {
            try {
                $t = (string) $response->text();
                if ($t !== '') {
                    return $t;
                }
            } catch (\Throwable $e) {
                // ignore and try fallbacks
            }
        }

        if (is_array($response)) {
            $parts = (array) ($response['candidates'][0]['content']['parts'] ?? []);
            $texts = array_map(static function ($p): string {
                return (string) ($p['text'] ?? '');
            }, $parts);
            $joined = trim(implode("\n", $texts));
            if ($joined !== '') {
                return $joined;
            }
        }

        return (string) $response;
    }
}
