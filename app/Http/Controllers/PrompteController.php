<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrompteRequest;
use App\Http\Requests\UpdatePrompteRequest;
use App\Models\MembreEntite;
use App\Models\Prompte;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PrompteController extends Controller
{
    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $entites = $user instanceof User ? $user->entites()->get() : collect();
        $entiteIds = $entites->pluck('id')->all();

        $tab = strtoupper((string) request()->query('tab', 'SECTION'));
        if (!in_array($tab, ['SECTION', 'RESUME'], true)) {
            $tab = 'SECTION';
        }

        $promptes = Prompte::query()
            ->with('entite')
            ->where(function ($q) use ($entiteIds): void {
                $q->whereIn('entite_id', $entiteIds)
                    ->orWhereNull('entite_id');
            })
            ->where('type', $tab)
            ->orderBy('titre')
            ->paginate(12)
            ->appends(['tab' => $tab]);

        // Entités où l'utilisateur courant est OWNER
        $ownerEntiteIds = MembreEntite::query()
            ->where('user_id', $user?->id)
            ->where('role', 'OWNER')
            ->where('invite_status', 'ACCEPTED')
            ->pluck('entite_id')
            ->all();

        return view('promptes.index', [
            'entites' => $entites,
            'promptes' => $promptes,
            'ownerEntiteIds' => $ownerEntiteIds,
            'activeTab' => $tab,
        ]);
    }

    public function create(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $entites = $user instanceof User ? $user->entites()->get() : collect();
        $tab = strtoupper((string) request()->query('tab', 'SECTION'));
        if (!in_array($tab, ['SECTION', 'RESUME'], true)) {
            $tab = 'SECTION';
        }

        return view('promptes.create', [
            'entites' => $entites,
            'activeTab' => $tab,
        ]);
    }

    public function store(StorePrompteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $entiteId = (int) $data['entite_id'];

        $this->authorizeOwner($entiteId);

        $prompte = new Prompte;
        $prompte->entite_id = $entiteId;
        $prompte->type = strtoupper($data['type']);
        // Catégorie masquée: on force à chaîne vide par défaut
        $prompte->categorie = '';
        $prompte->titre = $data['titre'];
        $prompte->contenu = $data['contenu'];
        $prompte->langue = $data['langue'] ?? null;
        $prompte->is_default = $request->boolean('is_default');
        // Par défaut visible à true si non spécifié à la création
        $prompte->visible = $request->has('visible') ? $request->boolean('visible') : true;
        $prompte->save();

        return redirect()->route('promptes.index', ['tab' => $prompte->type])
            ->with('success', 'Prompte créé.');
    }

    public function edit(Prompte $prompte): View
    {
        // Les promptes globaux (entite_id null) sont uniquement consultables, pas éditables
        if ($prompte->entite_id === null) {
            abort(403, 'Action non autorisée.');
        }

        $this->authorizeOwner((int) $prompte->entite_id);

        /** @var User|null $user */
        $user = Auth::user();
        $entites = $user instanceof User ? $user->entites()->get() : collect();

        return view('promptes.edit', [
            'prompte' => $prompte,
            'entites' => $entites,
        ]);
    }

    public function update(UpdatePrompteRequest $request, Prompte $prompte): RedirectResponse
    {
        if ($prompte->entite_id === null) {
            abort(403, 'Action non autorisée.');
        }

        $this->authorizeOwner((int) $prompte->entite_id);

        $data = $request->validated();

        // Ne pas autoriser le changement d'entité si l'utilisateur n'est pas owner de la cible
        $newEntiteId = (int) ($data['entite_id'] ?? $prompte->entite_id);
        if ($newEntiteId !== (int) $prompte->entite_id) {
            $this->authorizeOwner($newEntiteId);
            $prompte->entite_id = $newEntiteId;
        }

        $prompte->type = strtoupper($data['type']);
        $prompte->categorie = $data['categorie'] ?? null;
        $prompte->titre = $data['titre'];
        $prompte->contenu = $data['contenu'];
        $prompte->langue = $data['langue'] ?? null;
        $prompte->is_default = $request->boolean('is_default');
        $prompte->visible = $request->boolean('visible');
        $prompte->save();

        return redirect()->route('promptes.index', ['tab' => $prompte->type])
            ->with('success', 'Prompte mis à jour.');
    }

    public function destroy(Prompte $prompte): RedirectResponse
    {
        if ($prompte->entite_id === null) {
            abort(403, 'Action non autorisée.');
        }

        $this->authorizeOwner((int) $prompte->entite_id);

        $tab = $prompte->type;
        $prompte->delete();

        return redirect()->route('promptes.index', ['tab' => $tab])
            ->with('success', 'Prompte supprimé.');
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
}
