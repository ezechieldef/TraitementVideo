@extends('layouts.app')

@section('title', 'Modifier la chaîne')
@section('page-title', 'Modifier la chaîne')

@section('content')
    <div class="max-w-2xl theme-surface rounded-xl p-5">
    <form method="POST" action="{{ route('chaines.update', $chaine) }}" class="space-y-4" id="chaine-edit-form">
            @csrf
            @method('PUT')

            <label class="block text-sm theme-muted-text">Titre</label>
            <input type="text" name="titre" value="{{ old('titre', $chaine->titre) }}" class="w-full rounded-lg theme-input" required />
            @error('titre')<div class="text-sm text-red-600">{{ $message }}</div>@enderror

            <label class="block text-sm theme-muted-text">URL YouTube (optionnel)</label>
            <input type="url" name="youtube_url" value="{{ old('youtube_url', $chaine->youtube_url) }}" class="w-full rounded-lg theme-input" required />
            @error('youtube_url')<div class="text-sm text-red-600">{{ $message }}</div>@enderror

            <label class="block text-sm theme-muted-text">Channel ID (optionnel)</label>
            <input type="text" name="channel_id" value="{{ old('channel_id', $chaine->channel_id) }}" class="w-full rounded-lg theme-input" required />
            @error('channel_id')<div class="text-sm text-red-600">{{ $message }}</div>@enderror

            <div class="flex justify-end gap-2">
                <a href="{{ route('chaines.index') }}" class="rounded-lg px-4 py-2 theme-muted-text hover-theme-muted">Annuler</a>
                <button class="inline-flex items-center gap-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-4 py-2">
                    <i class="ti ti-device-floppy"></i>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
    <script>
        (function() {
            const urlInput = document.querySelector('#chaine-edit-form input[name="youtube_url"]');
            const channelIdInput = document.querySelector('#chaine-edit-form input[name="channel_id"]');
            if (!urlInput || !channelIdInput) return;

            function extractChannelId(url) {
                if (!url) return '';
                const m = url.match(/\/channel\/([A-Za-z0-9_-]+)/);
                return m ? m[1] : '';
            }

            function maybeFillChannelId() {
                const cid = extractChannelId(urlInput.value.trim());
                if (cid && !channelIdInput.value) {
                    channelIdInput.value = cid;
                }
            }

            urlInput.addEventListener('change', maybeFillChannelId);
            urlInput.addEventListener('paste', () => setTimeout(maybeFillChannelId, 0));
            urlInput.addEventListener('blur', maybeFillChannelId);
        })();
    </script>
@endsection
