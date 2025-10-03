<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChaineRequest;
use App\Http\Requests\UpdateChaineRequest;
use App\Models\Chaine;
use App\Models\KeyToken;
use App\Models\MembreEntite;
use App\Models\User;
use App\Services\YouTubeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ChaineController extends Controller
{
    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $entites = $user instanceof User ? $user->entites()->get() : collect();
        $entiteIds = $entites->pluck('id')->all();

        $chaines = Chaine::query()
            ->with('entite')
            ->whereIn('entite_id', $entiteIds)
            ->orderBy('titre')
            ->paginate(12);

        // Determine entités where the current user is OWNER
        $ownerEntiteIds = MembreEntite::query()
            ->where('user_id', $user?->id)
            ->where('role', 'OWNER')
            ->where('invite_status', 'ACCEPTED')
            ->pluck('entite_id')
            ->all();

        return view('chaines.index', [
            'entites' => $entites,
            'chaines' => $chaines,
            'ownerEntiteIds' => $ownerEntiteIds,
        ]);
    }

    public function store(StoreChaineRequest $request, YouTubeService $youTube): RedirectResponse
    {
        $data = $request->validated();
        $entiteId = (int) $data['entite_id'];

        // Only owners can create for the entité
        $this->authorizeOwner($entiteId);

        $channelId = $data['channel_id'] ?? null;
        if ((!$channelId || $channelId === '') && !empty($data['youtube_url'])) {
            // Try lightweight extraction from URL
            $channelId = $this->extractChannelId($data['youtube_url']);
        }
        // If still empty, try resolve via API if a key exists for the entité
        if (!$channelId) {
            $apiKey = KeyToken::query()
                ->where('entite_id', $entiteId)
                ->where('type', 'YOUTUBE')
                ->where('status', 'WORKING')
                ->orderByDesc('priority')
                ->value('value');
            if ($apiKey) {
                $res = $youTube->resolveChannelId($data['youtube_url'], $apiKey);
                if (($res['success'] ?? false) && !empty($res['channelId'])) {
                    $channelId = $res['channelId'];
                }
            }
        }
        // If still empty, we allow null per new behavior

        $chaine = new Chaine;
        $chaine->entite_id = $entiteId;
        $chaine->titre = $data['titre'];
        $chaine->channel_id = $channelId;
        $chaine->youtube_url = $data['youtube_url'] ?? null;
        $chaine->save();

        return redirect()->route('chaines.index')->with('success', 'Chaîne créée.');
    }

    public function edit(Chaine $chaine): View
    {
        $this->authorizeOwner($chaine->entite_id);

        /** @var User|null $user */
        $user = Auth::user();
        $entites = $user instanceof User ? $user->entites()->get() : collect();

        return view('chaines.edit', [
            'chaine' => $chaine,
            'entites' => $entites,
        ]);
    }

    public function update(UpdateChaineRequest $request, Chaine $chaine, YouTubeService $youTube): RedirectResponse
    {
        $this->authorizeOwner($chaine->entite_id);

        $data = $request->validated();
        $channelId = $data['channel_id'] ?? null;
        if ((!$channelId || $channelId === '') && !empty($data['youtube_url'])) {
            $channelId = $this->extractChannelId($data['youtube_url']);
        }
        if (!$channelId && !empty($data['youtube_url'])) {
            // Try resolving with API if available
            $apiKey = KeyToken::query()
                ->where('entite_id', $chaine->entite_id)
                ->where('type', 'YOUTUBE')
                ->where('status', 'WORKING')
                ->orderByDesc('priority')
                ->value('value');
            if ($apiKey) {
                $res = $youTube->resolveChannelId($data['youtube_url'], $apiKey);
                if (($res['success'] ?? false) && !empty($res['channelId'])) {
                    $channelId = $res['channelId'];
                }
            }
        }
        // If still null, we keep it null; import process will try to resolve later

        $chaine->titre = $data['titre'];
        $chaine->channel_id = $channelId;
        $chaine->youtube_url = $data['youtube_url'] ?? null;
        $chaine->save();

        return redirect()->route('chaines.index')->with('success', 'Chaîne mise à jour.');
    }

    public function destroy(Chaine $chaine): RedirectResponse
    {
        $this->authorizeOwner($chaine->entite_id);
        $chaine->delete();

        return redirect()->route('chaines.index')->with('success', 'Chaîne supprimée.');
    }

    private function authorizeOwner(int $entiteId): void
    {
        $userId = Auth::id();
        $isOwner = MembreEntite::query()
            ->where('entite_id', $entiteId)
            ->where('user_id', $userId)
            ->where('role', 'OWNER')
            ->where('invite_status', 'ACCEPTED')
            ->exists();

        if (!$isOwner) {
            abort(403, 'Action non autorisée.');
        }
    }

    private function extractChannelId(string $url): ?string
    {
        // Matches /channel/UCxxxx or full URL containing /channel/UC...
        if (preg_match('#/channel/([A-Za-z0-9_-]+)#', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
