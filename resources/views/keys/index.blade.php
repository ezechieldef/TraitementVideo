@extends('layouts.app')

@section('title', "Clés d'API")
@section('page-title', "Clés d'API")

@section('content')
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold theme-title">Clés d'API</h1>
            <button type="button" onclick="document.getElementById('createKeyModal').showModal()"
                class="rounded-md bg-slate-700 px-3 py-2 text-white">Nouvelle clé</button>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <div class="mb-1 font-semibold">Erreurs de validation</div>
                <ul class="list-disc pl-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-lg border theme-divider theme-surface theme-body p-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="theme-muted">
                            <th class="px-3 py-2 font-medium">Entité</th>
                            <th class="px-3 py-2 font-medium">Type</th>
                            <th class="px-3 py-2 font-medium">LLM</th>
                            <th class="px-3 py-2 font-medium">Priorité</th>
                            <th class="px-3 py-2 font-medium">Statut</th>
                            <th class="px-3 py-2 font-medium">Dernier usage</th>
                            <th class="px-3 py-2 font-medium">Quota utilisé</th>
                            <th class="px-3 py-2 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($keys as $key)
                            <tr class="border-t theme-divider">
                                <td class="px-3 py-2">{{ $key->entite?->titre ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    <span
                                        class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-xs theme-body">
                                        {{ $key->type }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">{{ $key->llm?->nom ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $key->priority }}</td>
                                <td class="px-3 py-2">{{ $key->status }}</td>
                                <td class="px-3 py-2">{{ $key->last_used_at?->diffForHumans() ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $key->quota_used }}</td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                            class="rounded-md px-3 py-1 text-xs theme-muted hover-theme-muted"
                                            onclick="document.getElementById('editEntiteModal-{{ $key->id }}').showModal()">
                                            Modifier entité
                                        </button>
                                        <form method="POST" action="{{ route('keys.retest', $key) }}">
                                            @csrf
                                            <button type="submit"
                                                class="rounded-md px-3 py-1 text-xs theme-muted hover-theme-muted">
                                                Re-tester
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('keys.destroy', $key) }}"
                                            onsubmit="return confirmDeletion(event, 'Supprimer ?', 'Cette action est irréversible.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-md bg-red-600 px-3 py-1 text-xs text-white">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="8" class="p-0">
                                    <dialog id="editEntiteModal-{{ $key->id }}"
                                        class="app-modal rounded-lg shadow-xl theme-surface theme-body">
                                        <form method="POST" action="{{ route('keys.update-entite', $key) }}"
                                            class="w-[90vw] max-w-md">
                                            @csrf
                                            @method('PUT')
                                            <div class="border-b theme-divider px-5 py-4">
                                                <h3 class="text-lg font-semibold theme-title">Changer l'entité propriétaire
                                                </h3>
                                            </div>
                                            <div class="space-y-4 p-5">
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium">Entité</label>
                                                    <select name="entite_id" class="input w-full" required>
                                                        @foreach ($entites as $e)
                                                            <option value="{{ $e->id }}"
                                                                @selected($e->id === $key->entite_id)>{{ $e->titre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div
                                                class="flex items-center justify-end gap-2 border-t theme-divider px-5 py-3">
                                                <button type="button"
                                                    class="rounded-md px-3 py-2 theme-muted-text hover-theme-muted"
                                                    onclick="document.getElementById('editEntiteModal-{{ $key->id }}').close()">Annuler</button>
                                                <button type="submit"
                                                    class="rounded-md bg-slate-700 px-3 py-2 text-white">Enregistrer</button>
                                            </div>
                                        </form>
                                    </dialog>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-3 text-center theme-muted-text">Aucune clé configurée</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <dialog id="createKeyModal" class="app-modal rounded-lg shadow-xl theme-surface theme-body">
            <form method="POST" action="{{ route('keys.store') }}" class="w-[90vw] max-w-xl">
                @csrf
                <div class="border-b theme-divider px-5 py-4">
                    <h3 class="text-lg font-semibold theme-title">Nouvelle clé API</h3>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Entité</label>
                        <select name="entite_id" class="input w-full" required>
                            <option value="">Sélectionner…</option>
                            @foreach ($entites as $e)
                                <option value="{{ $e->id }}" @selected((string) old('entite_id') === (string) $e->id)>
                                    {{ $e->titre }}
                                </option>
                            @endforeach
                        </select>
                        @error('entite_id')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Type</label>
                        <select name="type" id="keyType" class="input w-full" required onchange="onKeyTypeChange()">
                            <option value="YOUTUBE" @selected(old('type', 'YOUTUBE') === 'YOUTUBE')>YouTube</option>
                            <option value="LLM" @selected(old('type') === 'LLM')>LLM</option>
                        </select>
                    </div>

                    <div id="llmSelectWrapper" style="display:none">
                        <label class="mb-1 block text-sm font-medium">Modèle LLM</label>
                        <select name="llm_id" class="input w-full">
                            <option value="">Sélectionner…</option>
                            @foreach ($llms as $llm)
                                <option value="{{ $llm->id }}" @selected((string) old('llm_id') === (string) $llm->id)>
                                    {{ $llm->nom }}@if ($llm->model_version)
                                        ({{ $llm->model_version }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs theme-muted-text">Requis uniquement pour les clés de type LLM.</p>
                        @error('llm_id')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Clé</label>
                        <input type="password" name="value" class="input w-full" placeholder="Votre clé secrète"
                            required>
                        @error('value')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Limite d'usage</label>
                            <input type="number" name="usage_limit_count" class="input w-full" min="1"
                                placeholder="ex: 100" value="{{ old('usage_limit_count') }}">
                            @error('usage_limit_count')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Période (min)</label>
                            <input type="number" name="limit_periode_minutes" class="input w-full" min="1"
                                placeholder="ex: 60" value="{{ old('limit_periode_minutes') }}">
                            @error('limit_periode_minutes')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Priorité</label>
                            <input type="number" name="priority" class="input w-full" min="1"
                                value="{{ old('priority', 1) }}">
                            @error('priority')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t theme-divider px-5 py-3">
                    <button type="button" class="rounded-md px-3 py-2 theme-muted-text hover-theme-muted"
                        onclick="document.getElementById('createKeyModal').close()">Annuler</button>
                    <button type="submit" class="rounded-md bg-slate-700 px-3 py-2 text-white">Enregistrer</button>
                </div>
            </form>
        </dialog>
    </div>

    <script>
        function onKeyTypeChange() {
            const type = document.getElementById('keyType').value;
            const llm = document.getElementById('llmSelectWrapper');
            llm.style.display = type === 'LLM' ? 'block' : 'none';
        }
        // initialize on open
        onKeyTypeChange();
        @if ($errors->any())
            // Auto-open modal if validation failed
            document.addEventListener('DOMContentLoaded', function() {
                const dlg = document.getElementById('createKeyModal');
                if (dlg && typeof dlg.showModal === 'function') {
                    dlg.showModal();
                }
            });
        @endif
    </script>
@endsection
