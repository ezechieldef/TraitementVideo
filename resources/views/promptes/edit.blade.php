@extends('layouts.app')

@section('title', 'Modifier prompte')
@section('page-title', 'Modifier prompte')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="theme-surface rounded-xl p-6">
            <form method="POST" action="{{ route('promptes.update', $prompte) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2 space-y-2">
                        <label class="block text-sm font-semibold theme-title">Type de prompte</label>
                        <p class="text-sm theme-muted-text">Ce prompte sera utilisé soit pour découper automatiquement la
                            vidéo
                            en sections, soit pour générer le résumé de vos vidéos. Choisissez l'option qui correspond à
                            votre
                            besoin.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label
                                class="flex items-start gap-3 rounded-lg border theme-divider p-3 cursor-pointer hover-theme-muted">
                                <input type="radio" name="type" value="SECTION" class="mt-1"
                                    {{ old('type', $prompte->type) === 'SECTION' ? 'checked' : '' }} required>
                                <span>
                                    <span class="font-medium theme-title">Sections</span>
                                    <span class="block text-sm theme-muted-text">Découper la vidéo en parties logiques
                                        (introduction, étapes, conclusion, etc.) pour faciliter la navigation et
                                        l’analyse.</span>
                                </span>
                            </label>
                            <label
                                class="flex items-start gap-3 rounded-lg border theme-divider p-3 cursor-pointer hover-theme-muted">
                                <input type="radio" name="type" value="RESUME" class="mt-1"
                                    {{ old('type', $prompte->type) === 'RESUME' ? 'checked' : '' }} required>
                                <span>
                                    <span class="font-medium theme-title">Résumé</span>
                                    <span class="block text-sm theme-muted-text">Générer un résumé clair et structuré des
                                        points
                                        essentiels de la vidéo.</span>
                                </span>
                            </label>
                        </div>
                        @error('type')
                            <div class="text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm theme-muted-text">Entité</label>
                        <select name="entite_id" class="w-full rounded-lg theme-input" required>
                            @foreach ($entites as $e)
                                <option value="{{ $e->id }}" @selected($prompte->entite_id === $e->id)>{{ $e->titre }}
                                </option>
                            @endforeach
                        </select>
                        @error('entite_id')
                            <div class="text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm theme-muted-text">Langue</label>
                        <select name="langue" class="w-full rounded-lg theme-input">
                            @php $l = old('langue', $prompte->langue); @endphp
                            <option value="fr" @selected($l === 'fr')>Français (fr)</option>
                            <option value="en" @selected($l === 'en')>English (en)</option>
                        </select>
                        @error('langue')
                            <div class="text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>



                <!-- Catégorie masquée -->
                <input type="hidden" name="categorie" value="" />

                <div>
                    <label class="block text-sm theme-muted-text">Titre</label>
                    @php $typeForPlaceholder = (old('type', $prompte->type) === 'RESUME') ? 'résumé' : 'sections'; @endphp
                    <input type="text" name="titre" value="{{ old('titre', $prompte->titre) }}"
                        class="w-full rounded-lg theme-input"
                        placeholder="Ex: Prompte de {{ $typeForPlaceholder }} pour mes vidéos" required />
                    @error('titre')
                        <div class="text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm theme-muted-text">Contenu du prompte</label>
                    <textarea name="contenu" rows="10" class="w-full rounded-lg theme-input"
                        placeholder="Décrivez précisément ce que doit faire ce prompte.
Exemples:
- Pour SECTIONS:
  • Analyse la vidéo et découpe en sections cohérentes.
  • Donne un titre court (<= 80 caractères) pour chaque section.
  • Ajoute un résumé de 2-3 phrases par section.
- Pour RESUME:
  • Résume la vidéo en un paragraphe clair.
  • Liste 3 à 5 points clés.
  • Termine par une conclusion synthétique."
                        required>{{ old('contenu', $prompte->contenu) }}</textarea>
                    @error('contenu')
                        <div class="text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_default" value="1" class="rounded"
                            @checked($prompte->is_default) />
                        <span class="text-sm theme-muted-text">Défaut</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="visible" value="1" class="rounded" @checked($prompte->visible) />
                        <span class="text-sm theme-muted-text">Visible</span>
                    </label>
                </div>

                <div class="flex justify-end">
                    <a href="{{ route('promptes.index', ['tab' => $prompte->type]) }}"
                        class="rounded-lg px-4 py-2 theme-muted-text hover-theme-muted">Annuler</a>
                    <button
                        class="ml-2 inline-flex items-center gap-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-4 py-2">
                        <i class="ti ti-device-floppy"></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
