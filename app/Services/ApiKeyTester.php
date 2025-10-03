<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiKeyTester
{
    public function testYoutube(string $apiKey): ApiKeyTestResult
    {
        // Use a lightweight endpoint with key param - e.g., search list with a fixed query
        // https://www.googleapis.com/youtube/v3/search?part=id&type=video&q=test&maxResults=1&key=API_KEY
        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/search', [
                'part' => 'id',
                'type' => 'video',
                'q' => 'test',
                'maxResults' => 1,
                'key' => $apiKey,
            ]);

            if ($response->successful()) {
                return new ApiKeyTestResult(true, 'Clé YouTube valide.');
            }

            $msg = $response->json('error.message') ?? ('HTTP '.$response->status());

            return new ApiKeyTestResult(false, 'YouTube: '.$msg);
        } catch (\Throwable $e) {
            return new ApiKeyTestResult(false, 'YouTube: '.$e->getMessage());
        }
    }

    public function testGemini(string $apiKey, string $model): ApiKeyTestResult
    {
        $model = trim(str_replace(['(', ')', 'gratuit', 'free'], '', $model));
        // Google Generative Language API (Gemini) text generation minimal call
        // POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key=API_KEY
        try {
            $base = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $model);
            $url = $base;
            Log::info("Testing Gemini API with URL: $url");
            $response = Http::timeout(15)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => 'ping'],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return new ApiKeyTestResult(true, 'Clé LLM valide.');
            }

            $msg = $response->json('error.message') ?? ('HTTP '.$response->status());

            return new ApiKeyTestResult(false, 'LLM: '.$msg);
        } catch (\Throwable $e) {
            return new ApiKeyTestResult(false, 'LLM: '.$e->getMessage());
        }
    }
}
