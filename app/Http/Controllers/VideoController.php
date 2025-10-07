<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListVideosRequest;
use App\Models\KeyToken;
use App\Models\LLM;
use App\Models\Prompte;
use App\Models\User;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
{
    public function index(ListVideosRequest $request): View
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = Auth::user();
        $entiteIds = $user instanceof User ? $user->entites()->pluck('entites.id')->all() : [];

        $status = strtoupper($data['status'] ?? 'NEW');
        if (! in_array($status, ['NEW', 'PROCESSING', 'DONE'], true)) {
            $status = 'NEW';
        }

        $query = Video::query()
            ->whereIn('entite_id', $entiteIds)
            ->where('status', $status)
            ->latest('published_at')
            ->latest();

        $q = $data['q'] ?? null;
        if ($q) {
            $query->where(function ($sub) use ($q): void {
                $sub->where('titre', 'like', '%'.$q.'%')
                    ->orWhere('url', 'like', '%'.$q.'%')
                    ->orWhere('youtube_id', 'like', '%'.$q.'%');
            });
        }

        $dateDebut = $data['date_debut'] ?? null;
        $dateFin = $data['date_fin'] ?? null;
        if ($dateDebut) {
            $query->whereDate('published_at', '>=', $dateDebut);
        }
        if ($dateFin) {
            $query->whereDate('published_at', '<=', $dateFin);
        }

        $videos = $query->paginate(12)->withQueryString();

        return view('videos.index', [
            'videos' => $videos,
            'status' => $status,
            'filters' => [
                'q' => $q,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ],
        ]);
    }

    public function traiter(Video $video): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $entiteIds = $user instanceof User ? $user->entites()->pluck('entites.id')->all() : [];
        if (! in_array($video->entite_id, $entiteIds, true)) {
            abort(403, 'Action non autorisée.');
        }
        // Génère un nouveau token si aucun n'existe ou si le token actuel expire dans moins de 1 heure
        $token = $user->currentAccessToken();
        if ($token && $token->expires_at && $token->expires_at->diffInMinutes(now()) > 60) {
            $authToken = $token->plain_text_token;
        } else {
            $authToken = $user->createToken('default', [], now()->addDay())->plainTextToken;
        }
        $promptesSection = Prompte::where('type', 'section')
            ->where(function ($query) use ($user): void {
                $query->whereIn('entite_id', $user->entites()->pluck('entites.id'))
                    ->orWhereNull('entite_id');
            })
            ->get();
        $promptesResume = Prompte::where('type', 'resume')
            ->where(function ($query) use ($user): void {
                $query->whereIn('entite_id', $user->entites()->pluck('entites.id'))
                    ->orWhereNull('entite_id');
            })
            ->get();

        // LLM configurés pour les entités de l'utilisateur (on ne renvoie pas les clés !)
        $entiteIds = $user->entites()->pluck('entites.id')->all();
        $tokens = KeyToken::query()
            ->with(['llm', 'entite'])
            ->whereIn('entite_id', $entiteIds)
            ->whereNotNull('value')
            ->get();
        $llmsConfigured = $tokens
            ->filter(fn ($t) => $t->llm instanceof LLM)
            ->groupBy('llm_id')
            ->map(function ($group) {
                /** @var \App\Models\KeyToken $first */
                $first = $group->first();

                return [
                    'llm_id' => $first->llm_id,
                    'name' => $first->llm?->nom,
                    'model_version' => $first->llm?->model_version,
                    'token_count' => $group->count(),
                ];
            })
            ->values()
            ->all();

        // Tokens configurés (sélection fine de la clé à utiliser)
        $tokensConfigured = $tokens
            ->filter(fn ($t) => $t->llm instanceof LLM)
            ->map(function ($t) {
                /** @var \App\Models\KeyToken $t */
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'llm_id' => $t->llm_id,
                    'llm_name' => $t->llm?->nom,
                    'model_version' => $t->llm?->model_version,
                    'entite_id' => $t->entite_id,
                    'entite_titre' => $t->entite?->titre,
                    'status' => $t->status,
                    'priority' => $t->priority,
                ];
            })
            ->values()
            ->all();

        return view('videos.traiter', [
            'video' => $video,
            'authToken' => $authToken,
            'promptesSection' => $promptesSection,
            'promptesResume' => $promptesResume,
            'llmsConfigured' => $llmsConfigured,
            'tokensConfigured' => $tokensConfigured,
        ]);
    }
}
