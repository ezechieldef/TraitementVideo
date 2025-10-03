<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    /**
     * Extract the YouTube video ID from a URL.
     */
    public function extractVideoId(string $url): ?string
    {
        $patterns = [
            '/v=([\w-]{11})/i',
            '#youtu\.be/([\w-]{11})#i',
            '#/embed/([\w-]{11})#i',
            '#/shorts/([\w-]{11})#i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $url, $m)) {
                return $m[1];
            }
        }
        // If exact 11 length?
        if (preg_match('/^[\w-]{11}$/', trim($url))) {
            return trim($url);
        }

        return null;
    }

    /**
     * Fetch video info using videos.list and optionally captions/list for language guess.
     *
     * @return array{success:bool,message?:string,data?:array}
     */
    public function fetchVideoInfoFromUrl(string $url, ?string $apiKey): array
    {
        $id = $this->extractVideoId($url);
        if ($id === null) {
            return ['success' => false, 'message' => 'URL YouTube invalide ou ID introuvable.'];
        }
        // Ensure a standard 11-character video id (defensive)
        if (strlen($id) !== 11) {
            return ['success' => false, 'message' => 'ID vidéo invalide (longueur inattendue).'];
        }
        $apiKey = $apiKey !== null ? trim($apiKey) : null;
        if ($apiKey === null || $apiKey === '') {
            return ['success' => false, 'message' => "Aucune clé API YouTube configurée. Ajoutez-en une dans Clés d'API."];
        }

        $resp = Http::timeout(15)->get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'snippet,contentDetails,liveStreamingDetails',
            'id' => $id,
            'key' => $apiKey,
        ]);
        if (!$resp->successful()) {
            $error = $resp->json('error') ?? [];
            $msg = $error['message'] ?? ('HTTP ' . $resp->status());
            $reason = $error['errors'][0]['reason'] ?? null;
            if ($reason) {
                $msg .= ' (reason: ' . $reason . ')';
            }

            return ['success' => false, 'message' => 'YouTube: ' . $msg];
        }
        $item = $resp->json('items.0');
        if (!$item) {
            return ['success' => false, 'message' => 'Vidéo introuvable.'];
        }

        $snippet = $item['snippet'] ?? [];
        $details = $item['contentDetails'] ?? [];
        $live = $item['liveStreamingDetails'] ?? [];

        $durationISO8601 = $details['duration'] ?? null;
        $durationSeconds = $durationISO8601 ? $this->iso8601ToSeconds($durationISO8601) : null;
        $isLive = isset($live['actualStartTime']) || ($snippet['liveBroadcastContent'] ?? '') === 'live';

        $data = [
            'id' => $id,
            'url' => 'https://www.youtube.com/watch?v=' . $id,
            'title' => $snippet['title'] ?? '',
            'published_at' => isset($snippet['publishedAt']) ? new \DateTimeImmutable($snippet['publishedAt']) : null,
            'thumbnails' => $snippet['thumbnails'] ?? [],
            'durationSeconds' => $durationSeconds,
            'language' => $snippet['defaultAudioLanguage'] ?? ($snippet['defaultLanguage'] ?? null),
            'isLive' => (bool) $isLive,
        ];

        return ['success' => true, 'data' => $data];
    }

    /**
     * Resolve a YouTube channel id (UC...) from various inputs: handle (@name), /user/name, /c/custom, or full URL.
     *
     * @return array{success:bool,channelId?:string,message?:string}
     */
    public function resolveChannelId(string $input, ?string $apiKey): array
    {
        $input = trim($input);
        $apiKey = $apiKey !== null ? trim($apiKey) : null;
        if ($input === '') {
            return ['success' => false, 'message' => 'Entrée vide pour la résolution du channel id.'];
        }
        if ($apiKey === null || $apiKey === '') {
            return ['success' => false, 'message' => 'Aucune clé API YouTube disponible pour la résolution.'];
        }

        // 1) Already a UC... id
        if (preg_match('/^UC[0-9A-Za-z_-]+$/', $input)) {
            return ['success' => true, 'channelId' => $input];
        }

        // 2) Extract from /channel/UC...
        if (preg_match('#/channel/(UC[0-9A-Za-z_-]+)#i', $input, $m)) {
            return ['success' => true, 'channelId' => $m[1]];
        }

        // Normalize if full URL
        $path = $input;
        if (preg_match('#^https?://[^/]+/(.+)$#i', $input, $m)) {
            $path = '/' . ltrim($m[1], '/');
        }

        // 3) Handle @handle (from string or URL)
        if (preg_match('#@([A-Za-z0-9_.-]+)#', $input, $m)) {
            $handle = $m[1];
            $resp = Http::timeout(15)->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'id',
                'forHandle' => $handle,
                'key' => $apiKey,
            ]);
            if ($resp->successful() && ($id = $resp->json('items.0.id'))) {
                return ['success' => true, 'channelId' => $id];
            }
        }

        // 4) /user/username
        if (preg_match('#/user/([^/?]+)#i', $path, $m)) {
            $username = $m[1];
            $resp = Http::timeout(15)->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'id',
                'forUsername' => $username,
                'key' => $apiKey,
            ]);
            if ($resp->successful() && ($id = $resp->json('items.0.id'))) {
                return ['success' => true, 'channelId' => $id];
            }
        }

        // 5) /c/customName or other: fallback search by name
        if (preg_match('#/c/([^/?]+)#i', $path, $m)) {
            $custom = $m[1];
            $resp = Http::timeout(15)->get('https://www.googleapis.com/youtube/v3/search', [
                'part' => 'id',
                'type' => 'channel',
                'q' => $custom,
                'maxResults' => 1,
                'key' => $apiKey,
            ]);
            $cid = $resp->json('items.0.id.channelId') ?? null;
            if ($resp->successful() && $cid) {
                return ['success' => true, 'channelId' => $cid];
            }
        }

        // 6) Last resort: search using the whole input (likely a name)
        $resp = Http::timeout(15)->get('https://www.googleapis.com/youtube/v3/search', [
            'part' => 'id',
            'type' => 'channel',
            'q' => $input,
            'maxResults' => 1,
            'key' => $apiKey,
        ]);
        $cid = $resp->json('items.0.id.channelId') ?? null;
        if ($resp->successful() && $cid) {
            return ['success' => true, 'channelId' => $cid];
        }

        $error = $resp->json('error.message') ?? 'Impossible de résoudre le Channel ID.';

        return ['success' => false, 'message' => $error];
    }

    /**
     * Search channel videos between dates. Returns simplified items.
     *
     * @return array{success:bool,message?:string,items?:array<int,array<string,mixed>>}
     */
    public function searchChannelVideos(
        string $channelId,
        string $apiKey,
        ?string $publishedAfter = null,
        ?string $publishedBefore = null,
        bool $includeLive = true,
        bool $includeUploaded = true,
        int $maxResults = 25,
    ): array {
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'Aucune clé API YouTube configurée.'];
        }

        $typeFilters = [];
        if ($includeUploaded) {
            $typeFilters[] = 'video';
        }
        // For live, we still use search, filtering by eventType=live or upcoming (we'll include both live and completed?)
        $liveEventTypes = $includeLive ? ['live', 'completed'] : [];

        $items = [];

        // First uploaded videos
        if (!empty($typeFilters)) {
            $params = [
                'part' => 'snippet',
                'channelId' => $channelId,
                'type' => 'video',
                'maxResults' => min(50, $maxResults),
                'order' => 'date',
                'key' => $apiKey,
            ];
            if ($publishedAfter !== null) {
                $params['publishedAfter'] = $publishedAfter;
            }
            if ($publishedBefore !== null) {
                $params['publishedBefore'] = $publishedBefore;
            }
            Log::info('YouTube API search params', $params);
            $resp = Http::timeout(20)->get('https://www.googleapis.com/youtube/v3/search', $params);
            if (!$resp->successful()) {
                $msg = $resp->json('error.message') ?? ('HTTP ' . $resp->status());

                return ['success' => false, 'message' => 'YouTube: ' . $msg];
            }
            foreach ((array) ($resp->json('items') ?? []) as $it) {
                $vid = $it['id']['videoId'] ?? null;
                if (!$vid) {
                    continue;
                }
                $items[] = [
                    'id' => $vid,
                    'title' => $it['snippet']['title'] ?? '',
                    'published_at' => isset($it['snippet']['publishedAt']) ? new \DateTimeImmutable($it['snippet']['publishedAt']) : null,
                    'thumbnails' => $it['snippet']['thumbnails'] ?? [],
                    'isLive' => false,
                ];
            }
        }

        // Then live events if needed
        foreach ($liveEventTypes as $evt) {
            $params = [
                'part' => 'snippet',
                'channelId' => $channelId,
                'type' => 'video',
                'eventType' => $evt,
                'maxResults' => min(50, $maxResults),
                'order' => 'date',
                'key' => $apiKey,
            ];
            if ($publishedAfter !== null) {
                $params['publishedAfter'] = $publishedAfter;
            }
            if ($publishedBefore !== null) {
                $params['publishedBefore'] = $publishedBefore;
            }
            $resp = Http::timeout(20)->get('https://www.googleapis.com/youtube/v3/search', $params);
            if ($resp->successful()) {
                foreach ((array) ($resp->json('items') ?? []) as $it) {
                    $vid = $it['id']['videoId'] ?? null;
                    if (!$vid) {
                        continue;
                    }
                    $items[] = [
                        'id' => $vid,
                        'title' => $it['snippet']['title'] ?? '',
                        'published_at' => isset($it['snippet']['publishedAt']) ? new \DateTimeImmutable($it['snippet']['publishedAt']) : null,
                        'thumbnails' => $it['snippet']['thumbnails'] ?? [],
                        'isLive' => true,
                    ];
                }
            }
        }

        // Optionally enrich durations via videos.list in batches of 50
        $ids = array_values(array_unique(array_column($items, 'id')));
        if (!empty($ids)) {
            $chunks = array_chunk($ids, 50);
            $durations = [];
            foreach ($chunks as $chunk) {
                $r = Http::timeout(20)->get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'contentDetails',
                    'id' => implode(',', $chunk),
                    'key' => $apiKey,
                ]);
                if ($r->successful()) {
                    foreach ((array) ($r->json('items') ?? []) as $it) {
                        $vid = $it['id'] ?? null;
                        $d = $it['contentDetails']['duration'] ?? null;
                        if ($vid && $d) {
                            $durations[$vid] = $this->iso8601ToSeconds($d);
                        }
                    }
                }
            }
            foreach ($items as &$it) {
                $it['durationSeconds'] = $durations[$it['id']] ?? null;
            }
        }

        return ['success' => true, 'items' => $items];
    }

    private function iso8601ToSeconds(string $duration): int
    {
        $interval = new \DateInterval($duration);

        return ($interval->d * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
    }
}
