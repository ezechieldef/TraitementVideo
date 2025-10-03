<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListVideosRequest;
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
}
