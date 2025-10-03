@extends('layouts.app')

@section('page-title', 'Tableau de bord')

@section('content')
    <div class="mx-auto max-w-7xl">
        @if (session('status'))
            <div class="mb-6 rounded-lg theme-surface p-4">
                <span class="theme-body">{{ session('status') }}</span>
            </div>
        @endif

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl theme-surface p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm theme-muted-text">Bienvenue</p>
                        <h3 class="mt-1 text-xl font-semibold theme-title">
                            {{ auth()->user()->name ?? 'Utilisateur' }}</h3>
                    </div>
                    <i class="ti ti-home text-3xl text-red-600"></i>
                </div>
                <p class="mt-4 text-sm theme-body">Vous êtes connecté avec succès.</p>
            </div>
        </div>
    </div>
@endsection
