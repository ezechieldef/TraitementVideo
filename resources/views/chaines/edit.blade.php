@extends('layouts.app')

@section('title', 'Modifier la chaîne')
@section('page-title', 'Modifier la chaîne')

@section('content')
    <div class="max-w-2xl theme-surface rounded-xl p-5">
        <form method="POST" action="{{ route('chaines.update', $chaine) }}" class="space-y-4" id="chaine-edit-form">
            @csrf
            @method('PUT')

            <label class="block text-sm theme-muted-text">Titre</label>
            <input type="text" name="titre" value="{{ old('titre', $chaine->titre) }}"
                class="w-full rounded-lg theme-input" required />
            @error('titre')
                <div class="text-sm text-red-600">{{ $message }}</div>
            @enderror

            <label class="block text-sm theme-muted-text">URL YouTube</label>
            <input type="url" name="youtube_url" value="{{ old('youtube_url', $chaine->youtube_url) }}"
                class="w-full rounded-lg theme-input" required />
            @error('youtube_url')
                <div class="text-sm text-red-600">{{ $message }}</div>
            @enderror



            <div class="flex justify-end gap-2">
                <a href="{{ route('chaines.index') }}"
                    class="rounded-lg px-4 py-2 theme-muted-text hover-theme-muted">Annuler</a>
                <button
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-4 py-2">
                    <i class="ti ti-device-floppy"></i>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>

@endsection
