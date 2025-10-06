<?php

namespace App\Services;

use App\Models\Transcription;
use App\Models\Video;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Facades\Log;
use MrMySQL\YoutubeTranscript\Transcript;
use MrMySQL\YoutubeTranscript\TranscriptListFetcher;

class YoutubeTranscriptPlugin
{
    protected $fetcher;

    public function __construct()
    {

        $http_client = new Client;
        $request_factory = new HttpFactory;
        $stream_factory = new HttpFactory; // GuzzleHttp\Psr7\HttpFactory implements StreamFactoryInterface

        $fetcher = new TranscriptListFetcher($http_client, $request_factory, $stream_factory);
        $this->fetcher = $fetcher;

    }

    // public function fetchTranscript($videoId, $langPref = ['fr', 'en'])
    // {

    //     $transcript_list = $this->fetcher->fetch($videoId);
    //     // Log::info('Plugin Fetch the Transcript List', ['transcripts' => $transcript_list]);
    //     // $language_codes = $transcript_list->getAvailableLanguageCodes();
    //     $transcript = $transcript_list->findTranscript($langPref);
    //     $transcript_text = $transcript->fetch();
    //     return $transcript_text;
    //     Log::info('Plugin Fetch the Transcript', ['transcript' => $transcript_text]);
    // }

    public function fetchAvailableTracks($videoId): array
    {
        $transcript_list = $this->fetcher->fetch($videoId);
        $language_codes = $transcript_list->getAvailableLanguageCodes();
        $videoModel = Video::where('youtube_id', $videoId)->firstOrFail();
        // Log::info('Plugin Fetch the Available Languages', ['languages' => $language_codes]);
        foreach ($language_codes as $code) {
            try {
                // code...

                $tr = $transcript_list->findTranscript([$code])->fetch();

                if ($tr === null) {

                    continue;
                }

                $content = collect($tr)
                    ->map(function (array $l): string {
                        $sec = (int) round($l['start']);
                        // cast seconds to human readable time H:i:s
                        $h = floor($sec / 3600);
                        $m = floor(($sec % 3600) / 60);
                        $s = $sec % 60;
                        $timestr = sprintf('%02d:%02d:%02d', $h, $m, $s);

                        $text = (string) ($l['text'] ?? '');
                        $text = $this->decodeEntities($text);
                        $text = trim(str_replace('>', ' ', $text));

                        return '['.$timestr.'] '.$text;
                    })
                    ->implode("\n");

                Transcription::updateOrCreate([
                    'langue' => $code,
                    'video_id' => $videoModel->id,
                ], [
                    'contenu' => $content,
                ]);

            } catch (\Throwable $th) {
                // throw $th;
            }
        }

        return $language_codes;

    }

    /**
     * Decode common HTML entities (including numeric) to UTF-8 characters.
     * Decodes up to two times to handle double-encoded inputs safely.
     */
    private function decodeEntities(string $text): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($decoded !== $text) {
            $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $decoded;
    }
}
