<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MembreEntite;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
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
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $video = Video::findOrFail($id);

        $this->authorizeVideoAccess($video);

        return response()->json($video->toArray());

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function refreshStep(string $id): \Illuminate\Http\JsonResponse
    {
        $video = Video::findOrFail($id);

        $this->authorizeVideoAccess($video);

        $step = 0;
        if ($video->transcriptions()->count() > 0) {
            $step = 1;
        }
        if ($step == 1 && $video->sections()->count() > 0) {
            $step = 2;
        }
        if ($step == 2 && $video->resumes()->count() > 0) {
            $step = 3;
        }
        if ($step == 3 && $video->resumes()->where('isApproved', true)->exists()) {
            $step = 4;
        }
        $video->step = $step;
        $video->save();
        // Logic to refresh the step of the video processing
        // For example, you might want to recheck the status of the video processing

        // Here we just return the current status as a placeholder
        return response()->json([
            'success' => true,
            'status' => $step,
        ]);

    }
}
