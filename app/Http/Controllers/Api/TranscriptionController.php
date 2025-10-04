<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Video;
use App\Models\MembreEntite;
use App\Models\Transcription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\YouTubeTranscriptService;
use App\Http\Requests\StoreTranscriptionRequest;

class TranscriptionController extends Controller
{
    public function __construct(private YouTubeTranscriptService $yt)
    {
    }

    /**
     * GET /api/videos/{video}/transcription/youtube
     * Fetch transcript from YouTube and persist as latest transcription for the video.
     */
    public function fetchFromYouTube(Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);

        $langPref = ['fr', 'en'];
        try {
            $result = $this->yt->fetch($video->youtube_id, $langPref);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $lines = $result['lines'];
        // Compose textarea-like content: one line per caption, prefixed by seconds integer
        $content = collect($lines)
            ->map(function (array $l): string {
                $sec = (int) round($l['start']);

                return $sec . ' ' . $l['text'];
            })
            ->implode("\n");

        // Store as a new transcription row (keep history)
        $t = Transcription::create([
            'video_id' => $video->id,
            'langue' => $result['lang'],
            'contenu' => $content,
        ]);

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
     * POST /api/videos/{video}/transcription
     * Save or update the transcription content (user-edited).
     */
    public function save(StoreTranscriptionRequest $request, Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);

        $data = $request->validated();

        $t = Transcription::create([
            'video_id' => $video->id,
            'langue' => $data['langue'],
            'contenu' => $data['contenu'],
        ]);

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

    private function authorizeVideoAccess(Video $video): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $entiteIds = $user->entites()->pluck('entites.id')->all();
        if (!in_array($video->entite_id, $entiteIds, true)) {
            abort(403, 'Action non autorisée.');
        }

        // Additionally, ensure not a PENDING invitation etc. OWNER not required for transcription.
        $membership = MembreEntite::query()
            ->where('entite_id', $video->entite_id)
            ->where('user_id', $user->id)
            ->first();
        if (!$membership) {
            abort(403);
        }
    }
}
