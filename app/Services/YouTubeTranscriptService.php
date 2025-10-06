<?php

// filepath: d:\TraitementVideo\app\Services\YouTubeTranscriptService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class YouTubeTranscriptService
{
    /**
     * @return array{lang:string, lines: list<array{start:float,text:string}>}
     */
    public function fetch(string $videoId, ?array $preferredLang = null): array
    {
        $langs = $preferredLang ?? ['fr', 'en'];
        foreach (array_values($langs) as $lang) {
            if (str_contains($lang, '-')) {
                $base = explode('-', $lang)[0];
                if ($base !== $lang && ! in_array($base, $langs, true)) {
                    $langs[] = $base;
                }
            }
        }

        $tracks = $this->listTracks($videoId);
        if (empty($tracks)) {
            throw new \RuntimeException('Aucune transcription disponible.');
        }

        // Choisir la meilleure piste: humain > asr, langue préférée > fallback
        $preferred = null;
        foreach ($langs as $l) {
            $preferred = $this->findTrack($tracks, $l, false);
            if ($preferred) {
                break;
            }
        }
        if (! $preferred) {
            foreach ($langs as $l) {
                $preferred = $this->findTrack($tracks, $l, true);
                if ($preferred) {
                    break;
                }
            }
        }
        if (! $preferred) {
            $preferred = $this->findAnyTrack($tracks, false) ?? $this->findAnyTrack($tracks, true);
        }
        if (! $preferred) {
            throw new \RuntimeException('Aucune transcription disponible.');
        }

        // Télécharger via baseUrl si présent (meilleur chemin), sinon via timedtext
        $xml = null;
        if (! empty($preferred['baseUrl'])) {
            $xml = $this->requestByBaseUrl($preferred['baseUrl']);
        }
        if ($xml === null) {
            $xml = $this->requestTimedtext(
                $videoId,
                $preferred['lang_code'],
                ($preferred['kind'] ?? null) === 'asr' ? 'asr' : null,
                $preferred['name'] ?: null
            );
        }
        if ($xml === null) {
            throw new \RuntimeException('Aucune transcription disponible.');
        }

        $lines = $this->parseTimedtextXml($xml);
        if (empty($lines)) {
            throw new \RuntimeException('Aucune transcription disponible.');
        }

        return ['lang' => $preferred['lang_code'], 'lines' => $lines];
    }

    /**
     * @return list<array{lang_code:string, kind:?string, name:string, lang_translated:?string, baseUrl:?string}>
     */
    public function listTracks(string $videoId): array
    {
        // 1) timedtext list
        $tracks = $this->listTracksViaTimedtext($videoId);
        if (! empty($tracks)) {
            return $tracks;
        }

        // 2) get_video_info
        $tracks = $this->listTracksViaGetVideoInfo($videoId);
        if (! empty($tracks)) {
            return $tracks;
        }

        // 3) watch HTML fallback
        return $this->listTracksViaWatchHtml($videoId);
    }

    private function listTracksViaTimedtext(string $videoId): array
    {
        $resp = Http::timeout(15)->get('https://www.youtube.com/api/timedtext', [
            'type' => 'list',
            'v' => $videoId,
        ]);
        if (! $resp->successful()) {
            return [];
        }
        $xml = (string) $resp->body();
        if (trim($xml) === '') {
            return [];
        }
        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml);
        if ($sx === false) {
            return [];
        }
        $tracks = [];
        foreach ($sx->track as $t) {
            $tracks[] = [
                'lang_code' => (string) ($t['lang_code'] ?? ''),
                'kind' => isset($t['kind']) ? (string) $t['kind'] : null, // 'asr' si auto
                'name' => (string) ($t['name'] ?? ''),
                'lang_translated' => isset($t['lang_translated']) ? (string) $t['lang_translated'] : null,
                'baseUrl' => null,
            ];
        }

        return $tracks;
    }

    private function listTracksViaGetVideoInfo(string $videoId): array
    {
        $resp = Http::timeout(20)->withHeaders([
            'User-Agent' => 'Mozilla/5.0',
        ])->get('https://www.youtube.com/get_video_info', [
            'video_id' => $videoId,
            'c' => 'TVHTML5',
            'cver' => '7.202410',
        ]);
        if (! $resp->successful() || trim((string) $resp->body()) === '') {
            return [];
        }

        parse_str((string) $resp->body(), $parsed);
        $playerResponseJson = $parsed['player_response'] ?? $parsed['playerResponse'] ?? null;
        if (! $playerResponseJson) {
            return [];
        }
        $pr = json_decode($playerResponseJson, true);

        return $this->parseCaptionTracksFromPlayer($pr);
    }

    private function listTracksViaWatchHtml(string $videoId): array
    {
        $resp = Http::timeout(20)->withHeaders([
            'User-Agent' => 'Mozilla/5.0',
            'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
        ])->get('https://www.youtube.com/watch', ['v' => $videoId]);
        if (! $resp->successful()) {
            return [];
        }
        $html = (string) $resp->body();
        if ($html === '') {
            return [];
        }
        if (! preg_match('/ytInitialPlayerResponse\s*=\s*(\{.+?\});/s', $html, $m)) {
            return [];
        }
        $json = $m[1] ?? null;
        if (! $json) {
            return [];
        }
        $pr = json_decode($json, true);

        return $this->parseCaptionTracksFromPlayer($pr);
    }

    /**
     * @param  array<string,mixed>  $player
     * @return list<array{lang_code:string, kind:?string, name:string, lang_translated:?string, baseUrl:?string}>
     */
    private function parseCaptionTracksFromPlayer(array $player): array
    {
        $tracks = [];
        $list = $player['captions']['playerCaptionsTracklistRenderer']['captionTracks'] ?? null;
        if (! is_array($list)) {
            return [];
        }
        foreach ($list as $t) {
            $tracks[] = [
                'lang_code' => (string) ($t['languageCode'] ?? ''),
                'kind' => isset($t['kind']) ? (string) $t['kind'] : null, // 'asr' si auto
                'name' => (string) ($t['name']['simpleText'] ?? ''),
                'lang_translated' => (string) ($t['name']['simpleText'] ?? null),
                'baseUrl' => (string) ($t['baseUrl'] ?? ''),
            ];
        }

        return $tracks;
    }

    private function findTrack(array $tracks, string $lang, bool $asr): ?array
    {
        foreach ($tracks as $tr) {
            $isAsr = ($tr['kind'] ?? null) === 'asr';
            if ($asr !== $isAsr) {
                continue;
            }
            if (strcasecmp($tr['lang_code'], $lang) === 0) {
                return $tr;
            }
        }
        if (str_contains($lang, '-')) {
            $base = explode('-', $lang)[0];
            foreach ($tracks as $tr) {
                $isAsr = ($tr['kind'] ?? null) === 'asr';
                if ($asr !== $isAsr) {
                    continue;
                }
                if (strcasecmp($tr['lang_code'], $base) === 0) {
                    return $tr;
                }
            }
        }

        return null;
    }

    private function findAnyTrack(array $tracks, bool $asr): ?array
    {
        foreach ($tracks as $tr) {
            $isAsr = ($tr['kind'] ?? null) === 'asr';
            if ($asr === $isAsr) {
                return $tr;
            }
        }

        return null;
    }

    private function requestTimedtext(string $videoId, string $lang, ?string $kind, ?string $name = null): ?string
    {
        $params = ['v' => $videoId, 'lang' => $lang];
        if ($kind !== null) {
            $params['kind'] = $kind;
        }
        if ($name !== null && $name !== '') {
            $params['name'] = $name;
        }
        $resp = Http::timeout(15)->get('https://www.youtube.com/api/timedtext', $params);
        if (! $resp->successful()) {
            return null;
        }
        $body = (string) $resp->body();

        return trim($body) === '' ? null : $body;
    }

    private function requestByBaseUrl(string $baseUrl): ?string
    {
        if ($baseUrl === '') {
            return null;
        }
        $url = $this->appendQuery($baseUrl, ['fmt' => 'xml']);
        $resp = Http::timeout(20)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);
        if (! $resp->successful()) {
            return null;
        }
        $body = (string) $resp->body();

        return trim($body) === '' ? null : $body;
    }

    private function appendQuery(string $url, array $params): string
    {
        $parts = parse_url($url);
        $query = [];
        if (! empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }
        $query = array_merge($query, $params);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? 'www.youtube.com';
        $path = $parts['path'] ?? '/api/timedtext';

        return $scheme.'://'.$host.$path.'?'.http_build_query($query);
    }

    /**
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

        // Format classique <text>
        if (isset($sx->text)) {
            foreach ($sx->text as $node) {
                $start = isset($node['start']) ? (float) $node['start'] : 0.0;
                $text = trim((string) $node);
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = preg_replace('/\s+/', ' ', $text) ?? $text;
                $text = trim($text);
                if ($text !== '') {
                    $lines[] = ['start' => $start, 'text' => $text];
                }
            }
        }

        // Format SRV3 (<body><p><s>)
        if (empty($lines) && isset($sx->body->p)) {
            foreach ($sx->body->p as $p) {
                $startMs = isset($p['t']) ? (int) $p['t'] : 0;
                $start = $startMs / 1000.0;
                $textParts = [];
                if (isset($p->s)) {
                    foreach ($p->s as $s) {
                        $textParts[] = (string) $s;
                    }
                } else {
                    $textParts[] = (string) $p;
                }
                $text = html_entity_decode(trim(implode(' ', $textParts)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = preg_replace('/\s+/', ' ', $text) ?? $text;
                $text = trim($text);
                if ($text !== '') {
                    $lines[] = ['start' => $start, 'text' => $text];
                }
            }
        }

        return $lines;
    }
}
