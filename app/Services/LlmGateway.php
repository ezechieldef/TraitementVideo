<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlmGateway
{
    /**
     * Call an OpenAI-compatible chat completion endpoint and return raw content string.
     * Supports providers whose KeyToken::type is 'OPENAI' or 'GROQ'.
     *
     * @param  array{type:string, apiKey:string, model:string, baseUrl?:string}  $params
     */
    public function chatComplete(array $params, array $messages): string
    {
        // Avoid logging sensitive values such as API keys
        Log::info('LLM chatComplete called', [
            'type' => $params['type'] ?? null,
            'model' => $params['model'] ?? null,
            'baseUrl' => isset($params['baseUrl']) ? '[provided]' : null,
        ]);
        $type = strtoupper($params['type'] ?? '');
        $apiKey = (string) ($params['apiKey'] ?? '');
        $model = (string) ($params['model'] ?? '');
        $baseUrl = rtrim((string) ($params['baseUrl'] ?? ''), '/');

        if ($apiKey === '' || $model === '') {
            throw new \InvalidArgumentException('API key or model not provided');
        }

        // Build and send request per provider
        if ($type === 'OPENAI' || $type === 'GROQ') {
            $endpoint = ($type === 'OPENAI')
                ? ($baseUrl ?: 'https://api.openai.com/v1').'/chat/completions'
                : ($baseUrl ?: 'https://api.groq.com/openai/v1').'/chat/completions';

            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
            ];

            $resp = Http::withToken($apiKey)
                ->timeout(60)
                ->acceptJson()
                ->asJson()
                ->post($endpoint, $payload)
                ->throw();

            $data = $resp->json();

            return (string) ($data['choices'][0]['message']['content'] ?? '');
        }

        if ($type === 'GOOGLE' || $type === 'GEMINI') {
            // Delegate to GeminiCall service (package-backed)
            $caller = new \App\Services\GeminiCall;

            return $caller->generate([
                'apiKey' => $apiKey,
                'model' => $model,
                'messages' => $messages,
                'generationConfig' => [
                    'temperature' => 0,
                    'responseMimeType' => 'application/json',
                ],
                'apiToken' => $params['apiToken'] ?? null,
            ]);
        }

        throw new \RuntimeException('Unsupported LLM provider '.$type);
    }

    /**
     * Extract the first valid JSON object from a string, stripping code fences if present.
     *
     * @return array<string, mixed>
     */
    public function extractJson(string $text): array
    {
        $clean = trim($text);
        // Strip ```json fences
        if (preg_match('/^```json\s*(.*?)\s*```/is', $clean, $m)) {
            $clean = $m[1];
        }
        // Try direct decode
        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Fallback: find first {...} block
        if (preg_match('/\{[\s\S]*\}/', $clean, $m2)) {
            $decoded = json_decode($m2[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        throw new \RuntimeException('Réponse LLM non JSON');
    }

    /**
     * Attempt to extract text from a Gemini Laravel SDK response.
     *
     * @param  mixed  $response
     */
    private function extractTextFromGeminiResponse($response): string
    {
        // Common helper method name
        if (is_object($response) && method_exists($response, 'text')) {
            try {
                $t = (string) $response->text();
                if ($t !== '') {
                    return $t;
                }
            } catch (\Throwable $e) {
                // ignore and try other shapes
            }
        }

        // Try array-like shapes commonly returned by SDKs
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

        // Fallback to string cast
        return (string) $response;
    }
}
