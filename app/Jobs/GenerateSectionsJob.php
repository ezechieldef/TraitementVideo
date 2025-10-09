<?php

namespace App\Jobs;

use App\Models\KeyToken;
use App\Models\Section;
use App\Models\Transcription;
use App\Models\Video;
use App\Services\LlmGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $videoId,
        public int $tokenId,
        public string $baseLangue,
        public string $customInstruction,
    ) {
    }

    public function handle(): void
    {
        set_time_limit(300); // 5 minutes
        $video = Video::find($this->videoId);
        $token = KeyToken::with('llm')->find($this->tokenId);
        if (!$video || !$token || !$token->llm) {
            Log::warning('GenerateSectionsJob invalid preconditions', ['video' => $this->videoId, 'token' => $this->tokenId]);

            return;
        }

        try {
            $transcription = Transcription::query()->where('video_id', $video->id)
                ->where('langue', $this->baseLangue)->latest('id')->first()
                ?? Transcription::query()->where('video_id', $video->id)->latest('id')->first();
            if (!$transcription) {
                Log::warning('GenerateSectionsJob: no transcription');

                return;
            }

            $system = 'Tu es un service qui découpe une vidéo en sections à partir d\'une transcription. Réponds STRICTEMENT en JSON avec le schéma: {"sections":[{"titre":"string","debut":"HH:MM:SS","fin":"HH:MM:SS", "langue":"string(len:2)"}]}';
            $system .= "\nRègles:\n"
                . "- Assure-toi que 0 <= debut < fin.\n"
                . "- debut/fin doivent correspondre à des timestamps existants dans la transcription.\n"
                . "- Ne pas dupliquer/chevaucher fortement les sections.\n"
                . "- Si le texte est court, peux renvoyer 1-2 sections pertinentes.\n"
                . 'Langue de sortie : ' . ($this->baseLangue ?: 'fr');

            $messages = [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $this->customInstruction],
                ['role' => 'user', 'content' => "Transcription:\n" . $transcription->contenu],
            ];

            $gateway = new LlmGateway;
            $raw = $gateway->chatComplete([
                'type' => strtoupper($token->llm->getType()),
                'apiKey' => (string) $token->value,
                'model' => (string) ($token->llm->nomModel() ?? ''),
            ], $messages);

            $parsed = $gateway->extractJson($raw);
            $items = (array) ($parsed['sections'] ?? []);
            if (empty($items)) {
                Log::info('GenerateSectionsJob: no sections returned');

                return;
            }
            $ordreBase = (int) $video->sections()->max('ordre');
            foreach ($items as $item) {
                $titre = (string) ($item['titre'] ?? '');
                $debut = $this->hmsToSec((string) ($item['debut'] ?? '00:00:00'));
                $fin = $this->hmsToSec((string) ($item['fin'] ?? '00:00:00'));
                if ($fin <= $debut) {
                    continue;
                }
                $s = Section::create([
                    'video_id' => $video->id,
                    'titre' => $titre ?: null,
                    'langue' => $this->baseLangue ?: ($video->langue ? explode('-', (string) $video->langue)[0] : null),
                    'debut' => $debut,
                    'fin' => $fin,
                    'longueur' => max(0, $fin - $debut),
                    'ordre' => ++$ordreBase,
                    'isFromCron' => false,
                    'custom_instruction' => $this->customInstruction ?: null,
                ]);
                // Optionally extract transcription snippet here (reuse logic externally if needed)
            }
        } catch (\Throwable $e) {
            Log::error('GenerateSectionsJob failed', ['video' => $this->videoId, 'error' => $e->getMessage()]);
        }
    }

    private function hmsToSec(string $hms): int
    {
        $parts = explode(':', $hms);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        $s = (int) ($parts[2] ?? 0);

        return max(0, $h * 3600 + $m * 60 + $s);
    }
}
