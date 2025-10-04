@extends('layouts.app')

@section('title', 'Promptes')
@section('page-title', 'Promptes')

@section('content')
    @php $tab = $activeTab ?? 'SECTION'; @endphp

    <!-- Tabs + New button (separate header) -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
        <div class="flex gap-2 theme-surface rounded-lg py-2 px-2 ">
            <a href="{{ route('promptes.index', ['tab' => 'SECTION'] + request()->except('page')) }}"
                class="px-3 py-1 rounded-lg {{ $tab === 'SECTION' ? 'bg-slate-500 text-white' : 'theme-body' }}">SECTION</a>
            <a href="{{ route('promptes.index', ['tab' => 'RESUME'] + request()->except('page')) }}"
                class="px-3 py-1 rounded-lg {{ $tab === 'RESUME' ? 'bg-slate-500 text-white' : 'theme-body' }}">RESUME</a>
        </div>
        <a href="{{ route('promptes.create', ['tab' => $tab] + request()->except('page')) }}"
            class="inline-flex items-center gap-2 rounded-lg bg-green-600 hover:bg-green-700 text-white px-3 py-2">
            <i class="ti ti-plus"></i>
            Nouveau prompte
        </a>
    </div>

    @if ($promptes->isEmpty())
        <div class="theme-muted rounded-lg p-6 text-center">Aucun prompte.</div>
    @else
        <div class="theme-surface rounded-xl p-0 overflow-hidden px-5 py-3">
            <div class="overflow-x-auto">
                <table class="w-full text-sm rounded-md mb-3">
                    <thead class="theme-muted text-left rounded-md">
                        <tr>
                            <th class="px-4 py-3">Titre</th>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">Entité</th>
                            <th class="px-4 py-3">Langue</th>
                            <th class="px-4 py-3">Visible</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="theme-body">
                        @foreach ($promptes as $p)
                            <tr class="border-t theme-divider">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium theme-title">{{ $p->titre }}</div>
                                    <div class="text-xs theme-muted-text">
                                        {{ \Illuminate\Support\Str::limit($p->contenu, 80) }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">{{ $p->categorie ?? '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ $p->entite->titre ?? 'Global' }}</td>
                                <td class="px-4 py-3 align-top">{{ $p->langue ?? '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ $p->visible ? 'Oui' : 'Non' }}</td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center justify-end gap-2">
                                        @php $isOwner = isset($ownerEntiteIds) && $p->entite_id && in_array($p->entite_id, $ownerEntiteIds, true); @endphp
                                        @if ($isOwner)
                                            <a href="{{ route('promptes.edit', $p) }}"
                                                class="rounded-lg px-3 py-1 theme-muted-text hover-theme-muted">Modifier</a>
                                            <form method="POST" action="{{ route('promptes.destroy', $p) }}"
                                                onsubmit="return confirmDeletion(event, 'Supprimer ce prompte ?', 'Cette action est irréversible.')">
                                                @csrf @method('DELETE')
                                                <button
                                                    class="rounded-lg px-3 py-1 bg-red-600 text-white hover:bg-red-700">Supprimer</button>
                                            </form>
                                        @else
                                            <span class="text-xs theme-muted-text">—</span>
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
            {{ $promptes->links() }}
        </div>
    @endif

@endsection
