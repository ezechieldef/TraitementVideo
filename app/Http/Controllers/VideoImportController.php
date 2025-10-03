<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyzeVideoUrlRequest;
use App\Http\Requests\ImportFromChannelRequest;
use App\Http\Requests\ImportVideoFromUrlRequest;
use App\Models\Chaine;
use App\Models\KeyToken;
use App\Models\User;
use App\Models\Video;
use App\Services\YouTubeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VideoImportController extends Controller
{
    public function __construct(private YouTubeService $youTube)
    {
    }

    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $entites = $user instanceof User ? $user->entites()->get() : collect();

        $chaineQuery = Chaine::query();
        if ($entites->isNotEmpty()) {
            $chaineQuery->whereIn('entite_id', $entites->pluck('id')->all());
        } else {
            $chaineQuery->whereRaw('1=0');
        }

        $chaines = $chaineQuery->orderBy('titre')->get();

        $analyze = session('analyze');

        return view('videos.import', [
            'entites' => $entites,
            'chaines' => $chaines,
            'analyze' => $analyze,
        ]);
    }

    public function analyzeUrl(AnalyzeVideoUrlRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $url = $data['url'];

        /** @var User|null $user */
        $user = Auth::user();
        $apiKey = $this->getYoutubeKeyForUser($user);

        $info = $this->youTube->fetchVideoInfoFromUrl($url, $apiKey);

        if (!$info['success']) {
            return back()->withErrors(['url' => $info['message']])->withInput();
        }

        return redirect()->route('videos.import')->with('analyze', $info);
    }

    public function storeFromUrl(ImportVideoFromUrlRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = Auth::user();
        $allowedEntiteIds = $user instanceof User ? $user->entites()->pluck('entites.id')->all() : [];
        $entiteId = (int) $data['entite_id'];
        if (!in_array($entiteId, $allowedEntiteIds, true)) {
            return back()->withErrors(['entite_id' => "Vous n'avez pas accès à cette entité."])->withInput();
        }

        $apiKey = $this->getYoutubeKeyForUser($user);

        $info = $this->youTube->fetchVideoInfoFromUrl($data['url'], $apiKey);
        if (!$info['success']) {
            return back()->withErrors(['url' => $info['message']])->withInput();
        }

        $payload = $info['data'];

        $video = Video::query()->firstOrNew([
            'entite_id' => $entiteId,
            'youtube_id' => $payload['id'],
        ]);

        $video->fill([
            'titre' => $data['titre'] !== '' ? $data['titre'] : ($payload['title'] ?? ''),
            'url' => $payload['url'] ?? $data['url'],
            'status' => 'NEW',
            // Store only the hqdefault thumbnail URL
            'thumbnails' => 'https://i.ytimg.com/vi/' . $payload['id'] . '/hqdefault.jpg',
            'published_at' => $payload['published_at'] ?? null,
            'duration' => $payload['durationSeconds'] ?? null,
            'langue' => $payload['language'] ?? null,
            'step' => 0,
            'type_contenu' => ($payload['isLive'] ?? false) ? 'LIVE' : 'VIDEO',
        ]);

        $video->save();

        return redirect()->route('videos.import')->with('success', 'Vidéo importée avec succès.');
    }

    public function importFromChannel(ImportFromChannelRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = Auth::user();
        $allowedEntiteIds = $user instanceof User ? $user->entites()->pluck('entites.id')->all() : [];
        $entiteId = (int) ($data['entite_id'] ?? 0);
        if (!in_array($entiteId, $allowedEntiteIds, true)) {
            return back()->withErrors(['entite_id' => "Vous n'avez pas accès à cette entité."])->withInput();
        }

        $chaine = Chaine::query()->find($data['chaine_id']);
        if (!$chaine instanceof Chaine) {
            return back()->withErrors(['chaine_id' => 'Chaîne introuvable.']);
        }
        if (!in_array($chaine->entite_id, $allowedEntiteIds, true)) {
            return back()->withErrors(['chaine_id' => "Vous n'avez pas accès à cette chaîne."]);
        }

        $apiKey = $this->getYoutubeKeyForUser($user);
        // If the chaine has no real channel_id yet, try resolving it now using youtube_url, update and continue
        if ((!$chaine->channel_id || !str_starts_with((string) $chaine->channel_id, 'UC')) && $chaine->youtube_url && $apiKey) {
            $res = $this->youTube->resolveChannelId($chaine->youtube_url, $apiKey);
            if (($res['success'] ?? false) && !empty($res['channelId'])) {
                $chaine->channel_id = $res['channelId'];
                $chaine->save();
            }
        }
        // If we still don't have a valid channel id, stop with a user-friendly message
        if (!$chaine->channel_id || !str_starts_with((string) $chaine->channel_id, 'UC')) {
            return back()->withErrors(['chaine_id' => "Impossible de résoudre l'identifiant réel de la chaîne. Ajoutez une clé YouTube à l'entité ou renseignez l'ID UC... dans la fiche chaîne."]);
        }
        $publishedAfter = $data['date_debut'] ? (new \DateTimeImmutable($data['date_debut'] . ' 00:00:00'))->format(DATE_RFC3339) : null;
        $publishedBefore = $data['date_fin'] ? (new \DateTimeImmutable($data['date_fin'] . ' 23:59:59'))->format(DATE_RFC3339) : null;

        $wantLive = (bool) ($data['live'] ?? false);
        $wantUploaded = (bool) ($data['uploaded'] ?? false);

        $results = $this->youTube->searchChannelVideos(
            channelId: (string) $chaine->channel_id,
            apiKey: $apiKey,
            publishedAfter: $publishedAfter,
            publishedBefore: $publishedBefore,
            includeLive: $wantLive,
            includeUploaded: $wantUploaded,
        );

        if (!$results['success']) {
            return back()->withErrors(['chaine_id' => $results['message'] ?? 'Erreur lors de la récupération des vidéos.']);
        }

        $imported = 0;
        foreach ($results['items'] as $item) {
            $v = Video::query()->firstOrNew([
                'entite_id' => $entiteId,
                'youtube_id' => $item['id'],
            ]);
            $v->fill([
                'titre' => $item['title'] ?? '',
                'url' => 'https://www.youtube.com/watch?v=' . $item['id'],
                'status' => 'NEW',
                // Store only the hqdefault thumbnail URL (uniform with single video import)
                'thumbnails' => 'https://i.ytimg.com/vi/' . $item['id'] . '/hqdefault.jpg',
                'published_at' => $item['published_at'] ?? null,
                'duration' => $item['durationSeconds'] ?? null,
                'langue' => $item['language'] ?? null,
                'step' => 0,
                'type_contenu' => ($item['isLive'] ?? false) ? 'LIVE' : 'VIDEO',
            ]);
            if ($v->isDirty()) {
                $v->save();
                $imported++;
            } elseif (!$v->exists) {
                $v->save();
                $imported++;
            }
        }

        return redirect()->route('videos.import')->with('success', $imported . ' vidéo(s) importée(s) depuis la chaîne.');
    }

    private function getYoutubeKeyForUser(?User $user): ?string
    {
        if (!$user instanceof User) {
            return null;
        }
        $entiteIds = $user->entites()->pluck('entites.id')->all();
        $token = KeyToken::query()
            ->whereIn('entite_id', $entiteIds)
            ->where('type', 'YOUTUBE')
            ->where('status', 'WORKING')
            ->orderByDesc('priority')
            ->first();

        return $token?->value;
    }
}
