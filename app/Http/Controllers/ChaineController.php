<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChaineRequest;
use App\Http\Requests\UpdateChaineRequest;
use App\Models\Chaine;
use App\Models\MembreEntite;
use App\Models\User;
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

    public function store(StoreChaineRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $entiteId = (int) $data['entite_id'];

        // Only owners can create for the entité
        $this->authorizeOwner($entiteId);

        $channelId = $data['channel_id'] ?? null;
        if ((! $channelId || $channelId === '') && ! empty($data['youtube_url'])) {
            $channelId = $this->extractChannelId($data['youtube_url']);
        }
        if (! $channelId) {
            return back()->withErrors(['channel_id' => 'Impossible de déterminer le Channel ID. Fournissez-le ou une URL /channel/UC...'])->withInput();
        }

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

    public function update(UpdateChaineRequest $request, Chaine $chaine): RedirectResponse
    {
        $this->authorizeOwner($chaine->entite_id);

        $data = $request->validated();
        $channelId = $data['channel_id'] ?? null;
        if ((! $channelId || $channelId === '') && ! empty($data['youtube_url'])) {
            $channelId = $this->extractChannelId($data['youtube_url']);
        }
        if (! $channelId) {
            return back()->withErrors(['channel_id' => 'Impossible de déterminer le Channel ID.'])->withInput();
        }

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

        if (! $isOwner) {
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
