<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKeyTokenRequest;
use App\Http\Requests\UpdateKeyTokenEntiteRequest;
use App\Models\Entite;
use App\Models\KeyToken;
use App\Models\LLM;
use App\Models\User;
use App\Services\ApiKeyTester;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class KeyTokenController extends Controller
{
    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $entites = collect();
        if ($user instanceof User) {
            $entites = $user->entites()->get();
        }

        $keys = collect();
        if ($entites->isNotEmpty()) {
            $keys = KeyToken::query()
                ->with(['llm', 'entite'])
                ->whereIn('entite_id', $entites->pluck('id')->all())
                ->latest()
                ->get();
        }

        $llms = LLM::query()->orderBy('nom')->get();

        return view('keys.index', [
            'keys' => $keys,
            'llms' => $llms,
            'entites' => $entites,
        ]);
    }

    public function store(StoreKeyTokenRequest $request, ApiKeyTester $tester): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $entite = $user instanceof User ? $user->entites()->first() : null;

        if (! $entite instanceof Entite) {
            return redirect()->route('keys.index')->with('error', 'Aucune entité associée trouvée.');
        }

        $data = $request->validated();
        // Ensure the entité belongs to the authenticated user
        $entiteId = (int) ($data['entite_id'] ?? 0);
        $allowedEntiteIds = $user?->entites()->pluck('entites.id')->all() ?? [];
        if (! in_array($entiteId, $allowedEntiteIds, true)) {
            return back()->withErrors(['entite_id' => "Vous n'avez pas accès à cette entité."])->withInput();
        }

        // Test the key before saving
        if ($data['type'] === 'YOUTUBE') {
            $test = $tester->testYoutube($data['value']);
            if (! $test->success) {
                return back()->withErrors(['value' => $test->message])->withInput();
            }
        } elseif ($data['type'] === 'LLM') {
            $llm = LLM::query()->find($data['llm_id']);
            $modelName = $llm?->nom ?? 'gemini-pro';

            $test = $tester->testGemini($data['value'], $modelName);
            if (! $test->success) {
                return back()->withErrors(['value' => $test->message])->withInput();
            }
        }

        $token = new KeyToken;
        $token->entite_id = $entiteId;
        $token->type = $data['type'];
        $token->llm_id = $data['type'] === 'LLM' ? ($data['llm_id'] ?? null) : null;
        $token->value = $data['value'];
        $token->status = 'WORKING';
        $token->usage_limit_count = $data['usage_limit_count'] ?? null;
        $token->limit_periode_minutes = $data['limit_periode_minutes'] ?? null;
        $token->priority = $data['priority'] ?? 1;
        $token->save();

        return redirect()->route('keys.index')->with('success', 'Clé ajoutée avec succès.');
    }

    public function destroy(KeyToken $keyToken): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $allowedEntiteIds = $user instanceof User ? $user->entites()->pluck('entites.id')->all() : [];
        if (! in_array($keyToken->entite_id, $allowedEntiteIds, true)) {
            return redirect()->route('keys.index')->with('error', "Vous n'avez pas accès à cette clé.");
        }

        $keyToken->delete();

        return redirect()->route('keys.index')->with('success', 'Clé supprimée.');
    }

    public function retest(KeyToken $keyToken, ApiKeyTester $tester): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $allowedEntiteIds = $user instanceof User ? $user->entites()->pluck('entites.id')->all() : [];
        if (! in_array($keyToken->entite_id, $allowedEntiteIds, true)) {
            return redirect()->route('keys.index')->with('error', "Vous n'avez pas accès à cette clé.");
        }

        $result = null;
        if ($keyToken->type === 'YOUTUBE') {
            $result = $tester->testYoutube($keyToken->value);
        } elseif ($keyToken->type === 'LLM') {
            $modelName = $keyToken->llm?->nom ?? 'gemini-pro';

            $result = $tester->testGemini($keyToken->value, $modelName);
        }

        if ($result === null) {
            return redirect()->route('keys.index')->with('error', 'Type de clé inconnu.');
        }

        $keyToken->status = $result->success ? 'WORKING' : 'NOT_WORKING';
        $keyToken->last_used_at = now();
        $keyToken->save();

        return redirect()->route('keys.index')->with($result->success ? 'success' : 'error', $result->message);
    }

    public function updateEntite(KeyToken $keyToken, UpdateKeyTokenEntiteRequest $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $allowedEntiteIds = $user instanceof User ? $user->entites()->pluck('entites.id')->all() : [];
        if (! in_array($keyToken->entite_id, $allowedEntiteIds, true)) {
            return redirect()->route('keys.index')->with('error', "Vous n'avez pas accès à cette clé.");
        }

        $data = $request->validated();
        $targetEntiteId = (int) $data['entite_id'];
        if (! in_array($targetEntiteId, $allowedEntiteIds, true)) {
            return back()->withErrors(['entite_id' => "Vous n'avez pas accès à cette entité."]);
        }

        $keyToken->entite_id = $targetEntiteId;
        $keyToken->save();

        return redirect()->route('keys.index')->with('success', "L'entité propriétaire a été modifiée.");
    }
}
