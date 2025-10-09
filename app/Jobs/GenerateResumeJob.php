<?php

namespace App\Jobs;

use App\Models\KeyToken;
use App\Models\Resume;
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

class GenerateResumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $resumeId,
        public int $tokenId,
        public string $customInstruction,
    ) {
    }

    public function handle(): void
    {
        set_time_limit(300); // 5 minutes
        $resume = Resume::find($this->resumeId);
        if (!$resume) {
            return;
        }
        $video = Video::find($resume->video_id);
        $section = Section::find($resume->section_id);
        if (!$video || !$section) {
            $resume->is_processing = false;
            $resume->error_message = 'Video ou section introuvable';
            $resume->save();

            return;
        }
        $token = KeyToken::with('llm')->find($this->tokenId);
        if (!$token || !$token->llm) {
            $resume->is_processing = false;
            $resume->error_message = 'Jeton indisponible';
            $resume->save();

            return;
        }

        try {
            $baseLang = $resume->langue ? explode('-', $resume->langue)[0] : null;
            $tQuery = Transcription::query()->where('video_id', $video->id);
            if ($baseLang) {
                $tQuery->where('langue', $baseLang);
            }
            $transcription = $tQuery->latest('id')->first()
                ?? Transcription::query()->where('video_id', $video->id)->latest('id')->first();
            if (!$transcription) {
                throw new \RuntimeException('Aucune transcription disponible.');
            }
            $gateway = new LlmGateway;
            $system = <<<'EOT'
            Tu es un assistant spécialisé dans l'analyse et la rédaction de résumés à partir de transcriptions de vidéos afin de fournir un résumé pertinent et concis sous forme d'article représentatif de tout ce qui a été dit dans la vidéo.
            Ta tâche est de produire EXACTEMENT 1 objet JSON prêt à être enregistré en base de données
            IMPORTANT — Format de sortie STRICT (JSON uniquement):
            Retourne UNIQUEMENT un objet JSON (UTF-8), sans texte hors JSON, ni commentaires, ni backticks:

            {
            "langue": "string (code langue, ex. fr)",
            "titre": "string non vide (max 100 caractères)",
            "contenu": "string non vide (Markdown autorisé; sauts de ligne encodés avec \n)"
            }

            Contraintes:
            - Pas de texte hors JSON. Aucune explication.
            - Les champs s’appellent exactement: langue, titre, contenu.
            - Dans contenu, les balises Markdown sont autorisées, mais pas les balises HTML (uniquement dans contenu).
            - Tous les champs sont obligatoires et non vides.
            - langue: code langue (ex. fr, en, es, de). Si incertitude, mettre "fr".
            - titre: un titre concis (max 100 caractères) résumant le sujet de la vidéo.
            - contenu: un résumé détaillé (minimum 200 mots) couvrant tous les points clés abordés dans la vidéo.
            - Le résumé doit être pertinent, cohérent et refléter fidèlement le contenu de la vidéo.
            - Le résumé doit être structuré en paragraphes clairs et logiques.
            - Le résumé doit être informatif et utile pour quelqu’un n’ayant pas vu la vidéo.
            - Les instructuons personnalisées de l’utilisateur doivent être prises en compte dans le résumé.
            - Les instructions personnalisées de l’utilisateur pourraient définir la langue de sortie, sinon c'est le francais par défaut.
            - Ignore les segments non pertinents (blagues, bruits, apartés). Reste fidèle à la transcription.

            Rappels importants pour maximiser la qualité:
            - Ne jamais inventer d’informations. Ne pas ajouter de contenu absent de la vidéo.
            - Utiliser 'je'/'nous'/'vous'/'on' et éviter toute prise de distance (« il/elle… » au sujet de l’orateur).
            EOT;

            $messages = [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $this->customInstruction],
                ['role' => 'user', 'content' => 'Transcription (section):\n' . $transcription->contenu],
            ];
            $raw = $gateway->chatComplete([
                'type' => strtoupper($token->llm->getType()),
                'apiKey' => (string) $token->value,
                'model' => (string) ($token->llm->nomModel() ?? ''),
            ], $messages);
            $parsed = $gateway->extractJson($raw);
            $resume->titre = $parsed['titre'] ?? $resume->titre;
            $resume->contenu = isset($parsed['contenu']) ? trim($parsed['contenu']) : $resume->contenu;
            $resume->langue = $parsed['langue'] ?? $resume->langue;
            $resume->model_used = $token->llm?->model_version;
            $resume->is_processing = false;
            $resume->error_message = null;
            $resume->save();
        } catch (\Throwable $e) {
            Log::error('GenerateResumeJob failed', ['resume' => $resume->id, 'error' => $e->getMessage()]);
            $resume->is_processing = false;
            $resume->error_message = $e->getMessage();
            $resume->save();
        }
    }
}
