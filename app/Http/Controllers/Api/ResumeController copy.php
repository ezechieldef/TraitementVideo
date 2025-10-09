<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateResumeRequest;
use App\Models\KeyToken;
use App\Models\MembreEntite;
use App\Models\Resume;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ResumeController extends Controller
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
     * GET /api/videos/{video}/sections/{section}/resumes
     */
    public function index(Video $video, Section $section): JsonResponse
    {
        $this->authorizeVideoAccess($video);
        if ($section->video_id !== $video->id) {
            abort(404);
        }

        $items = $section->resumes()->latest('id')->get();

        return response()->json([
            'success' => true,
            'resumes' => $items->map(fn (Resume $r) => [
                'id' => $r->id,
                'titre' => $r->titre,
                'contenu' => $r->contenu,
                'langue' => $r->langue,
                'isApproved' => (bool) $r->isApproved,
                'isExported' => (bool) $r->isExported,
                'is_processing' => (bool) $r->is_processing,
                'error_message' => $r->error_message,
                'model_used' => $r->model_used,
            ]),
        ]);
    }

    /**
     * POST /api/videos/{video}/sections/{section}/resumes/generate
     */
    public function generate(GenerateResumeRequest $request, Video $video, Section $section): JsonResponse
    {
        $this->authorizeVideoAccess($video);
        if ($section->video_id !== $video->id) {
            abort(404);
        }

        $data = $request->validated();
        $langue = (string) ($data['langue'] ?? $section->langue ?? $video->langue ?? '');

        /** @var User $user */
        $user = Auth::user();
        $entiteIds = $user->entites()->pluck('entites.id')->all();
        $token = KeyToken::query()
            ->with('llm')
            ->whereIn('entite_id', $entiteIds)
            ->where('id', (int) $data['token_id'])
            ->whereNotNull('value')
            ->first();
        if (! $token || ! $token->llm) {
            return response()->json(['success' => false, 'message' => 'Jeton API invalide ou non autorisé.'], 422);
        }

        // Create placeholder resume (processing)
        $resume = new Resume;
        $resume->video_id = $video->id;
        $resume->section_id = $section->id;
        $resume->titre = 'Génération en cours…';
        $resume->contenu = '';
        $resume->langue = $langue;
        $resume->isApproved = false;
        $resume->isExported = false;
        $resume->is_processing = true;
        $resume->model_used = null;
        $resume->save();

        \App\Jobs\GenerateResumeJob::dispatch($resume->id, (int) $token->id, (string) $data['custom_instruction']);

        return response()->json([
            'success' => true,
            'queued' => true,
            'resume' => [
                'id' => $resume->id,
                'titre' => $resume->titre,
                'contenu' => $resume->contenu,
                'langue' => $resume->langue,
                'isApproved' => (bool) $resume->isApproved,
                'isExported' => (bool) $resume->isExported,
                'is_processing' => (bool) $resume->is_processing,
                'error_message' => $resume->error_message,
                'model_used' => $resume->model_used,
            ],
        ], 202);
    }

    private function sliceTranscript(string $contenu, int $startSec, int $endSec): string
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

    /**
     * DELETE /api/videos/{video}/sections/{section}/resumes/{resume}
     */
    public function destroy(Video $video, Section $section, Resume $resume): JsonResponse
    {
        $this->authorizeVideoAccess($video);
        if ($section->video_id !== $video->id || $resume->video_id !== $video->id || $resume->section_id !== $section->id) {
            abort(404);
        }

        $resume->delete();

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/videos/{video}/sections/{section}/resumes/{resume}/approve
     * Body: { keep_others: bool|null, scope?: 'section'|'video' }
     * If keep_others = false: delete other resumes (by scope) and mark this one approved.
     * If keep_others = true: just mark this one approved; others left intact.
     */
    public function approve(Video $video, Section $section, Resume $resume): JsonResponse
    {
        $this->authorizeVideoAccess($video);
        if ($section->video_id !== $video->id || $resume->video_id !== $video->id || $resume->section_id !== $section->id) {
            abort(404);
        }

        $data = request()->validate([
            'keep_others' => ['nullable', 'boolean'],
            'scope' => ['nullable', 'in:section,video'], // future flexibility
        ]);
        $keep = (bool) ($data['keep_others'] ?? false);
        $scope = $data['scope'] ?? 'section';

        // Approve target resume
        $resume->isApproved = true;
        $resume->save();

        $deleted = 0;
        if (! $keep) {
            $query = Resume::query()->where('video_id', $video->id)->where('id', '!=', $resume->id);
            if ($scope === 'section') {
                $query->where('section_id', $section->id);
            }
            $deleted = (int) $query->delete();
        }

        return response()->json([
            'success' => true,
            'approved_id' => $resume->id,
            'deleted_others' => ! $keep,
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * GET /api/videos/{video}/resumes
     * Returns all resumes of the video (ordered: approved first, then newest).
     */
    public function listAll(Video $video): JsonResponse
    {
        $this->authorizeVideoAccess($video);
        $items = Resume::query()
            ->where('video_id', $video->id)
            ->orderByDesc('isApproved')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'resumes' => $items->map(fn (Resume $r) => [
                'id' => $r->id,
                'section_id' => $r->section_id,
                'titre' => $r->titre,
                'contenu' => $r->contenu,
                'langue' => $r->langue,
                'isApproved' => (bool) $r->isApproved,
                'isExported' => (bool) $r->isExported,
                'is_processing' => (bool) $r->is_processing,
                'error_message' => $r->error_message,
                'model_used' => $r->model_used,
            ]),
        ]);
    }

    /**
     * PUT /api/videos/{video}/resumes/{resume}
     */
    public function update(Video $video, Resume $resume): JsonResponse
    {
        $this->authorizeVideoAccess($video);
        if ($resume->video_id !== $video->id) {
            abort(404);
        }
        $data = request()->validate([
            'titre' => ['nullable', 'string', 'max:100'],
            'contenu' => ['nullable', 'string'],
        ]);
        if (array_key_exists('titre', $data)) {
            $resume->titre = $data['titre'] ?? $resume->titre;
        }
        if (array_key_exists('contenu', $data)) {
            $resume->contenu = $data['contenu'] ?? $resume->contenu;
        }
        $resume->save();

        return response()->json([
            'success' => true,
            'resume' => [
                'id' => $resume->id,
                'titre' => $resume->titre,
                'contenu' => $resume->contenu,
                'langue' => $resume->langue,
                'isApproved' => (bool) $resume->isApproved,
                'isExported' => (bool) $resume->isExported,
                'is_processing' => (bool) $resume->is_processing,
                'error_message' => $resume->error_message,
                'model_used' => $resume->model_used,
            ],
        ]);
    }
}
