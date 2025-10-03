@extends('layouts.app')

@section('title', 'Importer une vidéo')
@section('page-title', 'Importer une vidéo')

@php
    $analyze = $analyze ?? null;
    $entites = $entites ?? collect();
    $chaines = $chaines ?? collect();
    $defaultEntiteId = $entites->first()->id ?? null;
@endphp

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Import by URL -->
        <div class="theme-surface rounded-xl p-5">
            <h2 class="text-lg font-semibold theme-title mb-4">Importer via URL</h2>

            <form class="space-y-4" method="POST" action="{{ route('videos.import.analyze') }}">
                @csrf
                <label class="block text-sm theme-muted-text">URL de la vidéo</label>
                <input type="url" name="url" value="{{ old('url') }}"
                    placeholder="https://www.youtube.com/watch?v=..." class="w-full rounded-lg theme-input" required />
                @error('url')
                    <div class="text-sm text-red-600">{{ $message }}</div>
                @enderror

                <div class="flex justify-end">
                    <button
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-600 hover:bg-slate-700 text-white px-4 py-2">
                        <i class="ti ti-search"></i>
                        Analyser
                    </button>
                </div>
            </form>

            @if ($analyze && ($analyze['success'] ?? false))
                @php
                    $data = $analyze['data'];
                    $secs = (int) ($data['durationSeconds'] ?? 0);
                    $h = intdiv($secs, 3600);
                    $m = intdiv($secs % 3600, 60);
                    $s = $secs % 60;
                    $durationHuman = $secs > 0 ? sprintf('%02d:%02d:%02d', $h, $m, $s) : '—';
                @endphp
                <div class="mt-6 border-t theme-divider pt-4">
                    <h3 class="font-medium theme-title mb-3">Prévisualisation</h3>

                    <!-- Preview full width -->
                    <div class="aspect-video rounded-lg overflow-hidden bg-black/10">
                        <iframe class="w-full h-full" src="https://www.youtube.com/embed/{{ $data['id'] }}"
                            frameborder="0" allowfullscreen></iframe>
                    </div>
                    <p class="mt-2 text-sm theme-muted-text">Durée: {{ $durationHuman }}
                        @if (!empty($data['isLive']))
                            <span class="ml-2 inline-flex items-center gap-1 text-red-600"><i class="ti ti-broadcast"></i>
                                Live</span>
                        @endif
                    </p>

                    <!-- Import form below preview -->
                    <form class="mt-4 space-y-4" method="POST" action="{{ route('videos.import.url') }}">
                        @csrf
                        <input type="hidden" name="url" value="{{ $data['url'] }}" />

                        <label class="block text-sm theme-muted-text">Entité</label>
                        <select name="entite_id" class="w-full rounded-lg theme-input" required>
                            @foreach ($entites as $e)
                                <option value="{{ $e->id }}" @selected(old('entite_id', $defaultEntiteId) == $e->id)>
                                    {{ $e->titre }}
                                </option>
                            @endforeach
                        </select>
                        @error('entite_id')
                            <div class="text-sm text-red-600">{{ $message }}</div>
                        @enderror

                        <label class="block text-sm theme-muted-text">Titre</label>
                        <input type="text" name="titre" value="{{ old('titre', $data['title'] ?? '') }}"
                            class="w-full rounded-lg theme-input" />
                        @error('titre')
                            <div class="text-sm text-red-600">{{ $message }}</div>
                        @enderror

                        <div class="flex justify-end">
                            <button
                                class="inline-flex items-center gap-2 rounded-lg bg-green-600 hover:bg-green-700 text-white px-4 py-2">
                                <i class="ti ti-download"></i>
                                Importer
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <!-- Import from channel -->
        <div class="theme-surface rounded-xl p-5">
            <h2 class="text-lg font-semibold theme-title mb-4">Importer depuis une chaîne</h2>
            <form class="space-y-4" method="POST" action="{{ route('videos.import.channel') }}">
                @csrf
                <label class="block text-sm theme-muted-text">Entité</label>
                <select name="entite_id" class="w-full rounded-lg theme-input" required>
                    @foreach ($entites as $e)
                        <option value="{{ $e->id }}" @selected(old('entite_id', $defaultEntiteId) == $e->id)>
                            {{ $e->titre }}
                        </option>
                    @endforeach
                </select>
                @error('entite_id')
                    <div class="text-sm text-red-600">{{ $message }}</div>
                @enderror
                <label class="block text-sm theme-muted-text">Chaîne</label>
                <select name="chaine_id" class="w-full rounded-lg theme-input" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($chaines as $c)
                        <option value="{{ $c->id }}" @selected(old('chaine_id') == $c->id)>
                            {{ $c->titre }}
                        </option>
                    @endforeach
                </select>
                @error('chaine_id')
                    <div class="text-sm text-red-600">{{ $message }}</div>
                @enderror

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm theme-muted-text">Date début</label>
                        <input type="date" name="date_debut" class="w-full rounded-lg theme-input"
                            value="{{ old('date_debut') }}" />
                        @error('date_debut')
                            <div class="text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm theme-muted-text">Date fin</label>
                        <input type="date" name="date_fin" class="w-full rounded-lg theme-input"
                            value="{{ old('date_fin') }}" />
                        @error('date_fin')
                            <div class="text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="live" value="1" class="size-4" @checked(old('live')) />
                        <span>Live</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="uploaded" value="1" class="size-4" @checked(old('uploaded', true)) />
                        <span>Vidéo uploadée</span>
                    </label>
                </div>

                <div class="flex justify-end">
                    <button
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 hover:bg-green-700 text-white px-4 py-2">
                        <i class="ti ti-download"></i>
                        Importer
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
