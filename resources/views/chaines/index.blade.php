@extends('layouts.app')

@section('title', 'Chaînes')
@section('page-title', 'Chaînes')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Create form -->
        <div class="theme-surface rounded-xl p-5 lg:col-span-1">
            <h2 class="text-lg font-semibold theme-title mb-4">Nouvelle chaîne</h2>
            <form method="POST" action="{{ route('chaines.store') }}" class="space-y-4" id="chaine-create-form">
                @csrf
                <label class="block text-sm theme-muted-text">Entité</label>
                <select name="entite_id" class="w-full rounded-lg theme-input" required>
                    @foreach ($entites as $e)
                        <option value="{{ $e->id }}">{{ $e->titre }}</option>
                    @endforeach
                </select>
                @error('entite_id')
                    <div class="text-sm text-red-600">{{ $message }}</div>
                @enderror

                <label class="block text-sm theme-muted-text">Titre</label>
                <input type="text" name="titre" value="{{ old('titre') }}" class="w-full rounded-lg theme-input"
                    required />
                @error('titre')
                    <div class="text-sm text-red-600">{{ $message }}</div>
                @enderror

                <label class="block text-sm theme-muted-text">URL YouTube </label>
                <input type="url" name="youtube_url" value="{{ old('youtube_url') }}"
                    class="w-full rounded-lg theme-input" placeholder="https://www.youtube.com/channel/UC..." required />
                @error('youtube_url')
                    <div class="text-sm text-red-600">{{ $message }}</div>
                @enderror



                <div class="flex justify-end">
                    <button
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 hover:bg-green-700 text-white px-4 py-2">
                        <i class="ti ti-plus"></i>
                        Ajouter
                    </button>
                </div>
            </form>
        </div>

        <!-- List -->
        <div class="lg:col-span-2">
            @if ($chaines->isEmpty())
                <div class="theme-muted rounded-xl p-6">Aucune chaîne.</div>
            @else
                <div class="theme-surface rounded-xl p-0 overflow-hidden px-5">
                    <div class=" py-4 border-b theme-divider">
                        <h2 class="text-lg font-semibold theme-title">Chaînes</h2>
                        <p class="mt-1 text-sm theme-muted-text">Liste de vos chaînes disponibles pour l'import.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm rounded-md mb-3">
                            <thead class="theme-muted text-left rounded-md">
                                <tr>
                                    <th class="px-4 py-3">Titre</th>
                                    <th class="px-4 py-3">Entité</th>
                                    <th class="px-4 py-3">Channel ID</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="theme-body">
                                @foreach ($chaines as $c)
                                    <tr class="border-t theme-divider">
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium theme-title">{{ $c->titre }}</div>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <span class="theme-muted-text">{{ $c->entite->titre ?? '—' }}</span>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <code class="text-xs">{{ $c->channel_id }}</code>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <div class="flex items-center justify-end gap-2">
                                                @if ($c->youtube_url)
                                                    <a href="{{ $c->youtube_url }}" target="_blank"
                                                        class="rounded-lg px-3 py-1 theme-muted-text hover-theme-muted">Visiter
                                                        la chaîne</a>
                                                @endif
                                                @php $isOwner = isset($ownerEntiteIds) && in_array($c->entite_id, $ownerEntiteIds, true); @endphp
                                                @if ($isOwner)
                                                    <form method="POST" action="{{ route('chaines.destroy', $c) }}"
                                                        onsubmit="return confirmDeletion(event, 'Supprimer cette chaîne ?', 'Cette action est irréversible.')">
                                                        @csrf @method('DELETE')
                                                        <button
                                                            class="rounded-lg px-3 py-1 bg-red-600 text-white hover:bg-red-700">Supprimer</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-6">
                    {{ $chaines->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection
