@extends('auth.template')

@section('title', 'Inscription - ' . config('app.name'))
@section('subtitle', 'Créez votre compte gratuitement')
@section('header', 'Créer un compte')
@section('description', 'Remplissez le formulaire pour commencer')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="ti ti-user"></i> Nom complet
            </label>
            <div class="mt-1">
                <input id="name" name="name" type="text" autocomplete="name" required value="{{ old('name') }}"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 transition focus:border-red-500 focus:outline-hidden focus:ring-2 focus:ring-red-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 dark:focus:border-red-400"
                    placeholder="John Doe">
            </div>
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="ti ti-mail"></i> Adresse e-mail
            </label>
            <div class="mt-1">
                <input id="email" name="email" type="email" autocomplete="email" required
                    value="{{ old('email') }}"
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
                <input id="password" name="password" type="password" autocomplete="new-password" required
                    class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 transition focus:border-red-500 focus:outline-hidden focus:ring-2 focus:ring-red-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 dark:focus:border-red-400"
                    placeholder="••••••••">
            </div>
            @error('password')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="ti ti-lock-check"></i> Confirmer le mot de passe
            </label>
            <div class="mt-1">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                    required
                    class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 transition focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 dark:focus:border-blue-400"
                    placeholder="••••••••">
            </div>
            @error('password_confirmation')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Terms and Conditions -->
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input id="terms" name="terms" type="checkbox" required
                    class="size-4 rounded border-gray-300 text-red-600 focus:ring-2 focus:ring-red-500/20 dark:border-gray-600 dark:bg-gray-700">
            </div>
            <div class="ml-3">
                <label for="terms" class="text-sm text-gray-600 dark:text-gray-400">
                    J'accepte les
                    <a href="#" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                        conditions d'utilisation
                    </a>
                    et la
                    <a href="#" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                        politique de confidentialité
                    </a>
                </label>
            </div>
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-3 font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-hidden focus:ring-2 focus:ring-red-500/50 dark:bg-red-500 dark:hover:bg-red-600">
                <i class="ti ti-user-plus"></i>
                <span>Créer mon compte</span>
            </button>
        </div>
    </form>
@endsection

@section('footer')
    <div class="flex items-center justify-center gap-1 text-sm">
        <span class="text-gray-600 dark:text-gray-400">Vous avez déjà un compte ?</span>
        <a href="{{ route('login') }}"
            class="font-medium text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300">
            Se connecter
        </a>
    </div>
@endsection
