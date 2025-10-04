<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class YouTubeTranscriptService
{
    /**
     * Try to fetch the transcript from YouTube timedtext endpoint.
     * It will attempt the provided language (and its base, e.g. fr from fr-FR),
     * then fallback to English, and try both human captions and auto-generated (kind=asr).
     *
     * @return array{lang:string, lines: list<array{start:float,text:string}>}
     */
    public function fetch(string $videoId, ?array $preferredLang = null): array
    {
        $langs = $preferredLang ?? ["fr", "en"];
        foreach ($langs as $lang) {
            if (str_contains($lang, '-')) {
                $base = explode('-', $lang)[0];
                if ($base !== $lang) {
                    $langs[] = $base;
                }
            }
        }

        $lastError = null;
        foreach ($langs as $lang) {
            // First try human captions
            $xml = $this->requestTimedtext($videoId, $lang, null);
            if ($xml === null) {
                // Try auto-generated
                $xml = $this->requestTimedtext($videoId, $lang, 'asr');
            }

            if ($xml !== null) {
                $lines = $this->parseTimedtextXml($xml);
                if (!empty($lines)) {
                    return ['lang' => $lang, 'lines' => $lines];
                }
            }
        }

        if ($lastError === null) {
            $lastError = 'Aucune transcription disponible.';
        }

        throw new \RuntimeException($lastError);
    }

    private function requestTimedtext(string $videoId, string $lang, ?string $kind): ?string
    {
        $params = [
            'v' => $videoId,
            'lang' => $lang,
            // 'fmt' => 'vtt', // Keep XML for easier parsing of start times
        ];
        if ($kind !== null) {
            $params['kind'] = $kind; // asr = auto-generated captions
        }

        $resp = Http::timeout(15)->get('https://www.youtube.com/api/timedtext', $params);
        Log::info('YouTube timedtext request', ["lang" => $lang, 'params' => $params, "resp" => $resp->body(), 'status' => $resp->status()]);
        if (!$resp->successful()) {
            return null;
        }
        $body = (string) $resp->body();
        // Empty body means no captions for this combination
        if (trim($body) === '') {
            return null;
        }

        return $body;
    }

    /**
     * Parse the YouTube timedtext XML format.
     * Returns list of ['start' => float seconds, 'text' => string]
     *
     * @return list<array{start:float,text:string}>
     */
    private function parseTimedtextXml(string $xml): array
    {
        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml);
        if ($sx === false) {
            return [];
        }

        $lines = [];
        foreach ($sx->text as $node) {
            $start = isset($node['start']) ? (float) $node['start'] : 0.0;
            $text = trim((string) $node);
            // YouTube encodes special chars; decode HTML entities
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Replace newlines inside a caption with spaces
            $text = preg_replace("/\s+\n\s+/", ' ', $text) ?? $text;
            $text = preg_replace('/\s+/', ' ', $text) ?? $text;
            $text = trim($text);
            if ($text !== '') {
                $lines[] = ['start' => $start, 'text' => $text];
            }
        }

        return $lines;
    }
}
