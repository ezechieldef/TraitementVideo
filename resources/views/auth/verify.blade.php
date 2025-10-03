@extends('auth.template')

@section('title', 'Vérification e-mail - ' . config('app.name'))
@section('subtitle', 'Confirmez votre adresse e-mail')
@section('header', 'Vérification d\'e-mail')

@section('content')
    @if (session('resent'))
        <div
            class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 p-4 text-green-800 dark:bg-green-900/20 dark:text-green-400">
            <i class="ti ti-circle-check text-xl"></i>
            <span class="text-sm">{{ __('A fresh verification link has been sent to your email address.') }}</span>
        </div>
    @endif

    <p class="text-sm text-gray-700 dark:text-gray-300">
        {{ __('Before proceeding, please check your email for a verification link.') }}
        {{ __('If you did not receive the email') }},
    </p>

    <form class="mt-4" method="POST" action="{{ route('verification.resend') }}">
        @csrf
        <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-3 font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-hidden focus:ring-2 focus:ring-red-500/50 dark:bg-red-500 dark:hover:bg-red-600">
            <i class="ti ti-mail-forward"></i>
            <span>{{ __('click here to request another') }}</span>
        </button>
    </form>
@endsection
