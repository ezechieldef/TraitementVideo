@extends('auth.template')

@section('title', 'Mot de passe oublié - ' . config('app.name'))
@section('subtitle', 'Réinitialisez votre mot de passe')
@section('header', 'Mot de passe oublié')
@section('description', 'Entrez votre adresse e-mail pour recevoir un lien de réinitialisation')

@section('content')
    @if (session('status'))
        <div
            class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 p-4 text-green-800 dark:bg-green-900/20 dark:text-green-400">
            <i class="ti ti-circle-check text-xl"></i>
            <span class="text-sm">{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="ti ti-mail"></i> Adresse e-mail
            </label>
            <div class="mt-1">
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    autocomplete="email" autofocus
                    class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 transition focus:border-red-500 focus:outline-hidden focus:ring-2 focus:ring-red-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 dark:focus:border-red-400">
            </div>
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-3 font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-hidden focus:ring-2 focus:ring-red-500/50 dark:bg-red-500 dark:hover:bg-red-600">
                <i class="ti ti-send"></i>
                <span>Envoyer le lien</span>
            </button>
        </div>
    </form>
@endsection
