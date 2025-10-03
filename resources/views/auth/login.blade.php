@extends('auth.template')

@section('title', 'Connexion - ' . config('app.name'))
@section('subtitle', 'Connectez-vous pour accéder à votre compte')
@section('header', 'Connexion')
@section('description', 'Entrez vos identifiants pour vous connecter')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="ti ti-mail"></i> Adresse e-mail
            </label>
            <div class="mt-1">
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 transition focus:border-red-500 focus:outline-hidden focus:ring-2 focus:ring-red-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 dark:focus:border-red-400"
                    placeholder="votre@email.com">
            </div>
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="ti ti-lock"></i> Mot de passe
            </label>
            <div class="mt-1">
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 transition focus:border-red-500 focus:outline-hidden focus:ring-2 focus:ring-red-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 dark:focus:border-red-400"
                    placeholder="••••••••">
            </div>
            @error('password')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input id="remember_me" name="remember" type="checkbox"
                    class="size-4 rounded border-gray-300 text-red-600 focus:ring-2 focus:ring-red-500/20 dark:border-gray-600 dark:bg-gray-700">
                <label for="remember_me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Se souvenir de moi
                </label>
            </div>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                    class="text-sm font-medium text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300">
                    Mot de passe oublié ?
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-3 font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-hidden focus:ring-2 focus:ring-red-500/50 dark:bg-red-500 dark:hover:bg-red-600">
                <i class="ti ti-login"></i>
                <span>Se connecter</span>
            </button>
        </div>
    </form>
@endsection

@section('footer')
    <div class="flex items-center justify-center gap-1 text-sm">
        <span class="text-gray-600 dark:text-gray-400">Vous n'avez pas de compte ?</span>
        <a href="{{ route('register') }}"
            class="font-medium text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300">
            Créer un compte
        </a>
    </div>
@endsection
