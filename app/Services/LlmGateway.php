<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LlmGateway
{
    /**
     * Call an OpenAI-compatible chat completion endpoint and return raw content string.
     * Supports providers whose KeyToken::type is 'OPENAI' or 'GROQ'.
     *
     * @param array{type:string, apiKey:string, model:string, baseUrl?:string} $params
     */
    public function chatComplete(array $params, array $messages): string
    {
        $type = strtoupper($params['type'] ?? '');
        $apiKey = (string) ($params['apiKey'] ?? '');
        $model = (string) ($params['model'] ?? '');
        $baseUrl = rtrim((string) ($params['baseUrl'] ?? ''), '/');

        if ($apiKey === '' || $model === '') {
            throw new \InvalidArgumentException('API key or model not provided');
        }

        // Endpoints by provider
        $endpoint = match ($type) {
            'OPENAI' => ($baseUrl ?: 'https://api.openai.com/v1') . '/chat/completions',
            'GROQ' => ($baseUrl ?: 'https://api.groq.com/openai/v1') . '/chat/completions',
            default => null,
        };

        if ($endpoint === null) {
            throw new \RuntimeException('Unsupported LLM provider');
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0,
            'response_format' => [ 'type' => 'json_object' ],
        ];

        $resp = Http::withToken($apiKey)
            ->timeout(60)
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $payload)
            ->throw();

        $data = $resp->json();
        $content = (string) ($data['choices'][0]['message']['content'] ?? '');
        return $content;
    }

    /**
     * Extract the first valid JSON object from a string, stripping code fences if present.
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
}
