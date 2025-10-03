@extends('auth.template')

@section('title', 'Confirmer le mot de passe - ' . config('app.name'))
@section('subtitle', 'Veuillez confirmer votre mot de passe')
@section('header', 'Confirmation requise')

@section('content')
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
        {{ __('Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="ti ti-lock"></i> Mot de passe
            </label>
            <div class="mt-1">
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 transition focus:border-red-500 focus:outline-hidden focus:ring-2 focus:ring-red-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 dark:focus:border-red-400">
            </div>
            @error('password')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <button type="submit"
                class="flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-3 font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-hidden focus:ring-2 focus:ring-red-500/50 dark:bg-red-500 dark:hover:bg-red-600">
                <i class="ti ti-lock-check"></i>
                <span>Confirmer</span>
            </button>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-red-600 hover:text-red-500 dark:text-red-400"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot Your Password?') }}
                </a>
            @endif
        </div>
    </form>
@endsection
