@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold theme-title">Équipes / Entités</h1>
        </div>
        @if (isset($pendingInvites) && $pendingInvites->count())
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4">
                <h3 class="mb-2 font-semibold text-amber-900">Invitations en attente</h3>
                <ul class="space-y-2">
                    @foreach ($pendingInvites as $inv)
                        <li class="flex items-center justify-between rounded theme-muted px-3 py-2">
                            <div>
                                <div class="font-medium">{{ $inv->entite->titre }}</div>
                                <div class="text-xs theme-muted-text">Rôle: {{ $inv->role }}</div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ URL::temporarySignedRoute('entites.members.invitations.accept', now()->addDays(7), ['membreEntite' => $inv->id]) }}"
                                    class="rounded-md bg-green-600 px-3 py-1 text-white text-sm">Accepter</a>
                                <a href="{{ URL::temporarySignedRoute('entites.members.invitations.reject', now()->addDays(7), ['membreEntite' => $inv->id]) }}"
                                    class="rounded-md bg-red-600 px-3 py-1 text-white text-sm">Refuser</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="rounded border border-green-200 bg-green-50 p-3 text-green-800">{{ session('status') }}</div>
        @endif

        <div class="rounded-lg border theme-divider theme-surface p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium theme-title">Équipes</h2>
                <button type="button" onclick="document.getElementById('createTeamModal').showModal()"
                    class="rounded-md bg-slate-700 px-3 py-2 text-white">Créer un groupe / équipe</button>
            </div>

            <dialog id="createTeamModal" class="app-modal rounded-lg shadow-xl theme-surface theme-bpdy">
                <form method="POST" action="{{ route('entites.store') }}" class="w-[90vw] max-w-lg">
                    @csrf
                    <div class="border-b theme-divider px-5 py-4">
                        <h3 class="text-lg font-semibold theme-title">Créer un groupe / équipe</h3>
                    </div>
                    <div class="space-y-4 p-5 theme-body">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Nom</label>
                            <input type="text" name="titre" class="input w-full" placeholder="Nom de l'équipe"
                                required>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Type de contenu</label>
                            <select name="type_contenu" class="input w-full">
                                <option value="AUTRE" selected>Autre</option>
                                <option value="TUTORIEL">Tutoriel</option>
                                <option value="RELIGION">Religion</option>
                                <option value="EDUCATION">Éducation</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t theme-divider px-5 py-3">
                        <button type="button" class="rounded-md px-3 py-2 theme-muted-text hover-theme-muted"
                            onclick="document.getElementById('createTeamModal').close()">Annuler</button>
                        <button type="submit" class="rounded-md bg-slate-700 px-3 py-2 text-white">Créer</button>
                    </div>
                </form>
            </dialog>
        </div>

        <div class="space-y-6">
            @forelse ($entites as $entite)
                <div class="rounded-lg border theme-divider theme-surface theme-body p-4">
                    <div class="mb-3">
                        <h3 class="text-lg font-semibold">{{ $entite->titre }}</h3>
                        <p class="text-sm theme-muted-text">Type de contenu: {{ $entite->type_contenu }}</p>
                    </div>

                    <div class="mb-4">
                        <h4 class="mb-2 font-medium">Membres</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="theme-muted">
                                        <th class="px-3 py-2 font-medium">Membre</th>
                                        <th class="px-3 py-2 font-medium">Email</th>
                                        <th class="px-3 py-2 font-medium">Rôle</th>
                                        <th class="px-3 py-2 font-medium">Statut</th>
                                        <th class="px-3 py-2 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($entite->membreEntites as $m)
                                        <tr class="border-t theme-divider">
                                            <td class="px-3 py-2">{{ $m->user->name }}</td>
                                            <td class="px-3 py-2">{{ $m->user->email }}</td>
                                            <td class="px-3 py-2"><span class="uppercase">{{ $m->role }}</span></td>
                                            <td class="px-3 py-2">{{ $m->invite_status }}</td>
                                            <td class="px-3 py-2 text-right">
                                                @if ($m->invite_status === 'INVITED')
                                                    <form method="POST"
                                                        action="{{ route('entites.members.invitations.cancel', $m) }}"
                                                        onsubmit="return confirmDeletion(event, 'Retirer cette invitation ?', 'Cette action est irréversible.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="rounded-md bg-red-600 px-3 py-1 text-white text-xs">Retirer</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-3 py-3 text-center theme-muted-text">Aucun membre
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-2 font-medium">Inviter un membre</h4>
                        <form method="POST" action="{{ route('entites.members.invite', $entite) }}"
                            class="flex flex-col gap-3 md:flex-row">
                            @csrf
                            <div class="flex-1">
                                <label class="mb-1 block text-sm font-medium">Email</label>
                                <input type="email" name="email" placeholder="email@exemple.com" class="input w-full"
                                    required>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Rôle</label>
                                <select name="role" class="input">
                                    <option value="MEMBER">Membre</option>
                                    <option value="OWNER">Propriétaire</option>
                                </select>
                            </div>
                            <div class="self-end">
                                <label class="block text-sm font-medium opacity-0">&nbsp;</label>
                                <button type="submit" class="rounded-md bg-slate-700 px-3 py-2 text-white">Inviter</button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <p>Aucune entité pour le moment.</p>
            @endforelse
        </div>
    </div>
@endsection
