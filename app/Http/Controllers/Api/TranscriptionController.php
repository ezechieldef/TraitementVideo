<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranscriptionRequest;
use App\Models\MembreEntite;
use App\Models\Transcription;
use App\Models\User;
use App\Models\Video;
use App\Services\YoutubeTranscriptPlugin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TranscriptionController extends Controller
{
    public function __construct(public YoutubeTranscriptPlugin $plugin) {}

    /**
     * GET /api/videos/{video}/transcription/youtube
     * Refresh available tracks from YouTube and persist any missing transcriptions in DB.
     */
    public function fetchFromYouTube(Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);

        try {
            $tracks = $this->plugin->fetchAvailableTracks($video->youtube_id);

            return response()->json([
                'success' => true,
                'tracks' => $tracks,
            ]);
        } catch (\Throwable $e) {
            Log::error('YouTube transcript refresh failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Échec de la récupération depuis YouTube.',
            ], 500);
        }
    }

    /**
     * POST /api/videos/{video}/transcription
     * Save or update the transcription content (user-edited).
     */
    public function save(StoreTranscriptionRequest $request, Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);

        $data = $request->validated();

        $t = Transcription::query()
            ->where('video_id', $video->id)
            ->where('langue', $data['langue'])
            ->latest('id')
            ->first();

        if ($t) {
            $t->contenu = $data['contenu'];
            $t->save();
        } else {
            $t = Transcription::create([
                'video_id' => $video->id,
                'langue' => $data['langue'],
                'contenu' => $data['contenu'],
            ]);
        }

        return response()->json([
            'success' => true,
            'transcription' => [
                'id' => $t->id,
                'langue' => $t->langue,
                'contenu' => $t->contenu,
            ],
        ]);
    }

    /**
     * GET /api/videos/{video}/transcription
     * Return latest saved transcription for this video if any.
     */
    public function latest(Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);

        $t = Transcription::query()
            ->where('video_id', $video->id)
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'transcription' => $t ? [
                'id' => $t->id,
                'langue' => $t->langue,
                'contenu' => $t->contenu,
                'created_at' => $t->created_at?->toISOString(),
            ] : null,
        ]);
    }

    /**
     * GET /api/videos/{video}/transcription/languages
     * List distinct languages already stored in DB for this video.
     */
    public function listLanguages(Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);

        $langs = Transcription::query()
            ->where('video_id', $video->id)
            ->distinct()
            ->pluck('langue')
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'languages' => $langs,
        ]);
    }

    /**
     * GET /api/videos/{video}/transcription/{langue}
     * Return latest transcription for the given language if any.
     */
    public function showByLanguage(Video $video, string $langue): JsonResponse
    {
        $this->authorizeVideoAccess($video);

        $t = Transcription::query()
            ->where('video_id', $video->id)
            ->where('langue', $langue)
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'transcription' => $t ? [
                'id' => $t->id,
                'langue' => $t->langue,
                'contenu' => $t->contenu,
                'created_at' => $t->created_at?->toISOString(),
            ] : null,
        ]);
    }

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

        // Additionally, ensure not a PENDING invitation etc. OWNER not required for transcription.
        $membership = MembreEntite::query()
            ->where('entite_id', $video->entite_id)
            ->where('user_id', $user->id)
            ->first();
        if (! $membership) {
            abort(403);
        }
    }

    public function getAvailableTracks(Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);

        $tracks = $this->plugin->fetchAvailableTracks($video->youtube_id);

        return response()->json([
            'success' => true,
            'tracks' => $tracks,
        ]);
    }
}
