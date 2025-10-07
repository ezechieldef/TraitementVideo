<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Models\KeyToken;
use App\Models\MembreEntite;
use App\Models\Section;
use App\Models\Transcription;
use App\Models\User;
use App\Models\Video;
use App\Services\LlmGateway;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
    private function authorizeVideoAccess(Video $video): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $entiteIds = $user->entites()->pluck('entites.id')->all();
        if (! in_array($video->entite_id, $entiteIds, true)) {
            abort(403, 'Action non autorisée.');
        }

        $membership = MembreEntite::query()
            ->where('entite_id', $video->entite_id)
            ->where('user_id', $user->id)
            ->first();
        if (! $membership) {
            abort(403);
        }
    }

    /**
     * GET /api/videos/{video}/sections
     */
    public function index(Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);

        /** @var Collection<int, Section> $sections */
        $sections = $video->sections()->withCount('resumes')->orderBy('ordre')->orderBy('debut')->get();

        return response()->json([
            'success' => true,
            'sections' => $sections->map(fn (Section $s) => [
                'id' => $s->id,
                'titre' => $s->titre,
                'langue' => $s->langue,
                'debut' => $s->debut,
                'fin' => $s->fin,
                'longueur' => $s->longueur,
                'ordre' => $s->ordre,
                'resumes_count' => $s->resumes_count,
            ]),
        ]);
    }

    /**
     * POST /api/videos/{video}/sections
     */
    public function store(StoreSectionRequest $request, Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);

        $data = $request->validated();
        $s = new Section;
        $s->video_id = $video->id;
        $s->titre = $data['titre'] ?? null;
        $s->langue = $data['langue'] ?? null;
        $s->debut = (int) $data['debut'];
        $s->fin = (int) $data['fin'];
        $s->longueur = max(0, $s->fin - $s->debut);
        $s->ordre = $data['ordre'] ?? ($video->sections()->max('ordre') + 1);
        $s->save();

        if (($data['extract'] ?? false) === true) {
            $this->extractTranscriptionIntoSection($video, $s);
        }

        return response()->json([
            'success' => true,
            'section' => [
                'id' => $s->id,
                'titre' => $s->titre,
                'langue' => $s->langue,
                'debut' => $s->debut,
                'fin' => $s->fin,
                'longueur' => $s->longueur,
                'ordre' => $s->ordre,
            ],
        ], 201);
    }

    /**
     * PUT /api/videos/{video}/sections/{section}
     */
    public function update(UpdateSectionRequest $request, Video $video, Section $section): JsonResponse
    {
        $this->authorizeVideoAccess($video);
        if ($section->video_id !== $video->id) {
            abort(404);
        }

        $data = $request->validated();
        $section->titre = $data['titre'] ?? $section->titre;
        if (array_key_exists('langue', $data)) {
            $section->langue = $data['langue'];
        }
        $section->debut = (int) $data['debut'];
        $section->fin = (int) $data['fin'];
        $section->longueur = max(0, $section->fin - $section->debut);
        if (array_key_exists('ordre', $data)) {
            $section->ordre = (int) $data['ordre'];
        }
        $section->save();

        if (($data['extract'] ?? false) === true) {
            $this->extractTranscriptionIntoSection($video, $section);
        }

        return response()->json([
            'success' => true,
            'section' => [
                'id' => $section->id,
                'titre' => $section->titre,
                'langue' => $section->langue,
                'debut' => $section->debut,
                'fin' => $section->fin,
                'longueur' => $section->longueur,
                'ordre' => $section->ordre,
            ],
        ]);
    }

    /**
     * DELETE /api/videos/{video}/sections/{section}
     */
    public function destroy(Video $video, Section $section): JsonResponse
    {
        $this->authorizeVideoAccess($video);
        if ($section->video_id !== $video->id) {
            abort(404);
        }
        $section->delete();

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/videos/{video}/sections/auto
     * Auto split by simple rule: split every N seconds or using existing transcription timestamps.
     */
    public function auto(Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);
        $v = Validator::make(request()->all(), [
            'custom_instruction' => ['required', 'string', 'max:2000'],
            // 'prompt' => ['sometimes', 'string', 'max:2000'],

            'token_id' => ['required', 'integer', 'exists:key_tokens,id'],
            'langue' => ['required', 'string', 'max:10'],
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        // Inputs (LLM not executed here yet, but we persist the user's custom instruction)
        $customInstruction = (string) request()->string('custom_instruction', '');

        $tokenId = request()->input('token_id');
        $selectedLangue = (string) request()->input('langue', '');
        if ($tokenId === null) {
            return response()->json(['success' => false, 'message' => 'Aucun jeton sélectionné.'], 422);
        }

        // Récupérer un token valide pour cet LLM parmi les entités de l'utilisateur courant
        /** @var User $user */
        $user = Auth::user();
        $entiteIds = $user->entites()->pluck('entites.id')->all();
        if ($tokenId !== null) {
            $token = KeyToken::query()
                ->with('llm')
                ->whereIn('entite_id', $entiteIds)
                ->where('id', (int) $tokenId)
                ->whereNotNull('value')
                ->first();
            if (! $token || ! $token->llm) {
                return response()->json(['success' => false, 'message' => 'Jeton API invalide ou non autorisé.'], 422);
            }
        } else {
            $token = KeyToken::query()
                ->with('llm')
                ->whereIn('entite_id', $entiteIds)

                ->whereNotNull('value')
                ->orderByDesc('priority')
                ->first();
            if (! $token || ! $token->llm) {
                return response()->json(['success' => false, 'message' => 'Aucun jeton valide pour cet LLM.'], 422);
            }
        }

        // Construire messages pour le modèle (system + user)
        $system = 'Tu es un service qui découpe une vidéo en sections à partir d\'une transcription. Réponds STRICTEMENT en JSON avec le schéma: {"sections":[{"titre":"string","debut":"HH:MM:SS","fin":"HH:MM:SS", "langue":"string(len:2)"}]}';
        $system .= "\nRègles:\n"
            ."- Assure-toi que 0 <= debut < fin.\n"
            ."- debut/fin doivent correspondre à des timestamps existants dans la transcription.\n"
            ."- Ne pas dupliquer/chevaucher fortement les sections.\n"
            ."- Si le texte est court, peux renvoyer 1-2 sections pertinentes.\n"
            .'Langue de sortie : '.($selectedLangue ?: 'français');

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $customInstruction],
        ];

        // Charger la dernière transcription (langue de la vidéo en priorité)
        $transcription = Transcription::query()->where('video_id', $video->id)
            ->where('langue', $selectedLangue)->latest('id')->first()
            ?? Transcription::query()->where('video_id', $video->id)->latest('id')->first();
        if (! $transcription) {
            return response()->json(['success' => false, 'message' => 'Aucune transcription disponible pour cette vidéo.'], 422);
        }

        $messages[] = ['role' => 'user', 'content' => "Transcription:\n".$transcription->contenu];

        // Appel LLM
        $gateway = new LlmGateway;
        try {
            $content = $gateway->chatComplete([
                'tokenInfo' => (string) $token,
                'type' => strtoupper($token->llm->getType()),
                'apiKey' => (string) $token->value,
                'model' => (string) ($token->llm->nomModel() ?? ''),
            ], $messages);
        } catch (\Throwable $e) {
            Log::error('LLM chatComplete failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Échec de l\'appel LLM. '.$e->getMessage()], 500);
        }

        // Parser le JSON pour créer les sections exactes
        try {
            $data = $gateway->extractJson($content);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Réponse LLM invalide (JSON requis).'], 422);
        }

        $items = (array) ($data['sections'] ?? []);
        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'Aucune section retournée par le LLM.'], 422);
        }

        $created = [];
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
                'langue' => $selectedLangue ?: ($video->langue ? explode('-', (string) $video->langue)[0] : null),
                'debut' => $debut,
                'fin' => $fin,
                'longueur' => max(0, $fin - $debut),
                'ordre' => ++$ordreBase,
                'isFromCron' => false,
                'custom_instruction' => $customInstruction ?: null,
            ]);
            $this->extractTranscriptionIntoSection($video, $s);
            $created[] = [
                'id' => $s->id,
                'titre' => $s->titre,
                'debut' => $s->debut,
                'fin' => $s->fin,
                'longueur' => $s->longueur,
                'ordre' => $s->ordre,
            ];
        }

        if (empty($created)) {
            return response()->json(['success' => false, 'message' => 'Aucune section valide n\'a été créée.'], 422);
        }

        return response()->json(['success' => true, 'sections' => $created], 201);
    }

    private function extractTranscriptionIntoSection(Video $video, Section $section): void
    {
        // Pick the latest transcription for the video's base language if possible, else any latest.
        $tQuery = Transcription::query()->where('video_id', $video->id);
        if ($section->langue) {
            $base = explode('-', (string) $section->langue)[0];
            $tQuery->where('langue', $base);
        } elseif ($video->langue) {
            $base = explode('-', (string) $video->langue)[0];
            $tQuery->where('langue', $base);
        }
        $t = $tQuery->latest('id')->first() ?? Transcription::query()->where('video_id', $video->id)->latest('id')->first();
        if (! $t) {
            return;
        }

        $extract = $this->sliceTranscription($t->contenu ?? '', $section->debut, $section->fin);
        $section->transcription = $extract;
        $section->save();
    }

    /**
     * Extract text lines between two bounds from a transcription formatted like:
     * [HH:MM:SS] text
     */
    private function sliceTranscription(string $contenu, int $startSec, int $endSec): string
    {
        $lines = preg_split("/\r?\n/", (string) $contenu) ?: [];
        $result = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (! preg_match('/^\[([0-9]{2}):([0-9]{2}):([0-9]{2})\]\s*(.+)$/', $line, $m)) {
                continue;
            }
            $sec = ((int) $m[1]) * 3600 + ((int) $m[2]) * 60 + ((int) $m[3]);
            if ($sec >= $startSec && $sec <= $endSec) {
                $result[] = $line;
            }
        }

        return implode("\n", $result);
    }

    private function hmsToSec(string $hms): int
    {
        $parts = explode(':', $hms);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        $s = (int) ($parts[2] ?? 0);

        return max(0, $h * 3600 + $m * 60 + $s);
    }

    private function truncateUtf8ByBytes(string $text, int $maxBytes): string
    {
        // Ajoute un marqueur pour indiquer que c'est tronqué sans dépasser la limite
        $suffix = "\n[...TRONQUÉ POUR LIMITE DE TAILLE...]";
        if (strlen($text) <= $maxBytes) {
            return $text;
        }
        $limit = max(0, $maxBytes - strlen($suffix));
        // mb_strcut découpe par octets en respectant les frontières d'UTF-8
        if (function_exists('mb_strcut')) {
            $cut = mb_strcut($text, 0, $limit, 'UTF-8');
        } else {
            $cut = substr($text, 0, $limit);
        }

        return $cut.$suffix;
    }
}
