@extends('layouts.app')

@section('title', 'Vidéos')
@section('page-title', 'Vidéos')

@php
    $active = strtolower($status ?? 'NEW');
    $active = in_array($active, ['new', 'processing', 'done']) ? $active : 'new';
    $filters = $filters ?? ['q' => null, 'date_debut' => null, 'date_fin' => null];

    function humanDuration(?int $secs): string
    {
        if (!$secs || $secs <= 0) {
            return '—';
        }
        $h = intdiv($secs, 3600);
        $m = intdiv($secs % 3600, 60);
        $s = $secs % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
@endphp

@section('content')
    <!-- Tabs + Import button -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
        <div class="flex gap-2 theme-surface rounded-lg py-2 px-2 ">
            <a href="{{ route('videos.index', ['status' => 'NEW'] + request()->except('page')) }}"
                class="px-3 py-1 rounded-lg {{ $active === 'new' ? 'bg-slate-500 text-white' : 'theme-body' }}">En
                attente</a>
            <a href="{{ route('videos.index', ['status' => 'PROCESSING'] + request()->except('page')) }}"
                class="px-3 py-1 rounded-lg {{ $active === 'processing' ? 'bg-slate-500 text-white' : 'theme-body' }}">En
                cours</a>
            <a href="{{ route('videos.index', ['status' => 'DONE'] + request()->except('page')) }}"
                class="px-3 py-1 rounded-lg {{ $active === 'done' ? 'bg-slate-500 text-white' : 'theme-body' }}">Traitées</a>
        </div>
        <a href="{{ route('videos.import') }}"
            class=" items-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 text-white px-3 py-2 inline-flex lg:hidden">
            <i class="ti ti-download"></i>
            Importer
        </a>
    </div>

    <!-- Filters card -->
    <div class="theme-surface rounded-xl p-4 mb-6">
        <form method="GET" action="{{ route('videos.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="status" value="{{ strtoupper($active) }}" />
            <div class="md:col-span-2">
                <label class="block text-sm theme-muted-text">Mot-clé</label>
                <input type="text" name="q" class="w-full rounded-lg theme-input" value="{{ $filters['q'] }}"
                    placeholder="Titre, URL, ID YouTube..." />
            </div>
            <div>
                <label class="block text-sm theme-muted-text">Date début</label>
                <input type="date" name="date_debut" class="w-full rounded-lg theme-input"
                    value="{{ $filters['date_debut'] }}" />
            </div>
            <div>
                <label class="block text-sm theme-muted-text">Date fin</label>
                <input type="date" name="date_fin" class="w-full rounded-lg theme-input"
                    value="{{ $filters['date_fin'] }}" />
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-600 hover:bg-slate-700 text-white px-4 py-2">
                    <i class="ti ti-filter"></i>
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Grid of videos -->
    @if ($videos->isEmpty())
        <div class="theme-muted rounded-lg p-6 text-center">Aucune vidéo.</div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($videos as $v)
                <div class="rounded-xl theme-surface overflow-hidden">
                    <div class="aspect-video bg-black/10">
                        @php
                            $thumb = $v->thumbnails;
                            // If old JSON present, fallback to a key else keep as-is string
                            if (is_string($thumb) && str_starts_with($thumb, '{')) {
                                $arr = json_decode($thumb, true);
                                $thumb = $arr['high']['url'] ?? ($arr['default']['url'] ?? '');
                            }
                            if (!$thumb) {
                                $thumb =
                                    'https://i.ytimg.com/vi/' . ($v->youtube_id ?? 'dQw4w9WgXcQ') . '/hqdefault.jpg';
                            }
                        @endphp
                        <img src="{{ $thumb }}" alt="{{ $v->titre }}" class="w-full h-full object-cover" />
                    </div>
                    <div class="p-4">
                        <div class="text-sm theme-muted-text mb-1">{{ humanDuration($v->duration) }}</div>
                        <h3 class="font-medium theme-title line-clamp-2">{{ $v->titre }}</h3>
                        <div class="mt-3 flex items-center justify-between">
                            <a href="{{ $v->url }}" target="_blank" class="text-slate-600 hover:underline">Ouvrir</a>
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-xs rounded px-2 py-1 theme-muted-text theme-muted">{{ strtolower($v->status) }}</span>
                                @if ($v->status === 'NEW')
                                    <a href="{{ route('videos.traiter', $v) }}"
                                        class="rounded-lg px-3 py-1 bg-slate-600 text-white hover:bg-slate-700">Traiter</a>
                                @elseif ($v->status === 'PROCESSING')
                                    <a href="{{ route('videos.traiter', $v) }}"
                                        class="rounded-lg px-3 py-1 bg-slate-600 text-white hover:bg-slate-700">Continuer
                                        traitement</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $videos->links() }}
        </div>
    @endif
@endsection
