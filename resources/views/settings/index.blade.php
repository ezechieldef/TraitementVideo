@extends('layouts.app')

@section('title', 'Paramètres')

@section('content')
    <div class="mx-auto max-w-7xl space-y-8">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold theme-title">Paramètres</h1>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile -->
                <section class="theme-surface rounded-lg p-6 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold theme-title">
                        <i class="ti ti-user-cog"></i> Profil
                    </h2>
                    <form method="POST" action="{{ route('settings.profile.update') }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="mb-1 block text-sm font-medium theme-body">Nom</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500" />
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium theme-body">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500" />
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex justify-end">
                            <button
                                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">
                                <i class="ti ti-device-floppy"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Password -->
                <section class="theme-surface rounded-lg p-6 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold theme-title">
                        <i class="ti ti-lock"></i> Mot de passe
                    </h2>
                    <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="mb-1 block text-sm font-medium theme-body">Mot de passe
                                actuel</label>
                            <input type="password" name="current_password"
                                class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500" />
                            @error('current_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium theme-body">Nouveau mot de
                                passe</label>
                            <input type="password" name="password"
                                class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500" />
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium theme-body">Confirmation</label>
                            <input type="password" name="password_confirmation"
                                class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500" />
                        </div>
                        <div class="flex justify-end">
                            <button
                                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">
                                <i class="ti ti-device-floppy"></i> Mettre à jour
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Sessions -->
                <section class="theme-surface rounded-lg p-6 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold theme-title">
                        <i class="ti ti-devices"></i> Sessions actives
                    </h2>
                    @if ($sessions && $sessions->count())
                        <div class="space-y-2">
                            @foreach ($sessions as $session)
                                <div class="flex items-center justify-between rounded-lg theme-muted p-3">
                                    <div class="text-sm theme-body">
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                            <span class="inline-flex items-center gap-1">
                                                <i class="ti ti-browser"></i>
                                                <span>
                                                    @php
                                                        $ua = $session->user_agent ?? '';
                                                        $browser = 'Navigateur';
                                                        if (stripos($ua, 'Chrome') !== false) {
                                                            $browser = 'Chrome';
                                                        } elseif (stripos($ua, 'Firefox') !== false) {
                                                            $browser = 'Firefox';
                                                        } elseif (
                                                            stripos($ua, 'Safari') !== false &&
                                                            stripos($ua, 'Chrome') === false
                                                        ) {
                                                            $browser = 'Safari';
                                                        } elseif (stripos($ua, 'Edg') !== false) {
                                                            $browser = 'Edge';
                                                        }
                                                    @endphp
                                                    {{ $browser }}
                                                </span>
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <i class="ti ti-map-pin"></i>
                                                <span>{{ $session->ip_address ?? '—' }}</span>
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <i class="ti ti-clock"></i>
                                                <span>
                                                    {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                    @if ($session->id === session()->getId())
                                        <span class="rounded bg-green-100 px-2 py-1 text-xs text-green-700">Cette
                                            session</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm theme-muted-text">Aucune session trouvée.</p>
                    @endif

                    <form method="POST" action="{{ route('settings.sessions.logout-others') }}" class="mt-4 space-y-3">
                        @csrf
                        <label class="block text-sm theme-body">Confirmez avec votre mot de passe</label>
                        <input name="current_password" type="password"
                            class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500" />
                        @error('current_password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="flex justify-end">
                            <button
                                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">
                                <i class="ti ti-logout-2"></i> Déconnecter les autres sessions
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- Right column -->
            <div class="space-y-6">
                <!-- Two Factor Auth -->
                <section class="theme-surface rounded-lg p-6 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold theme-title">
                        <i class="ti ti-shield-lock"></i> Double authentification
                    </h2>
                    @if ($user->two_factor_enabled)
                        <p class="mb-4 text-sm text-green-700">Activée depuis
                            {{ $user->two_factor_confirmed_at?->diffForHumans() ?? '—' }}</p>
                        <form method="POST" action="{{ route('settings.2fa.disable') }}">
                            @csrf
                            @method('DELETE')
                            <button
                                class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300">
                                <i class="ti ti-shield-x"></i> Désactiver 2FA
                            </button>
                        </form>
                    @else
                        <p class="mb-3 text-sm theme-muted-text">Activez la double authentification pour sécuriser votre
                            compte.</p>
                        @if (!$user->two_factor_secret)
                            <form method="POST" action="{{ route('settings.2fa.enable') }}">
                                @csrf
                                <button
                                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">
                                    <i class="ti ti-shield-plus"></i> Générer une clé 2FA
                                </button>
                            </form>
                        @else
                            <div class="mb-3">
                                <p class="text-sm theme-body">Scannez ce QR avec Google Authenticator (ou équivalent). Si
                                    vous ne pouvez pas scanner, utilisez le code ci-dessous.</p>
                                <div class="text-center">

                                    <div class="mt-3 inline-block rounded-lg bg-white p-3 shadow theme-ring ">
                                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)->margin(1)->generate($otpAuthUrl) !!}
                                    </div>
                                </div>
                                <div
                                    class="mt-3 inline-flex items-center gap-2 rounded-lg theme-muted p-2 text-xs theme-body w-full">
                                    <i class="ti ti-key"></i>
                                    <span class="select-all break-all">{{ strtoupper($user->two_factor_secret) }}</span>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('settings.2fa.confirm') }}" class="space-y-3">
                                @csrf
                                <label class="block text-sm theme-body">Entrez le code à 6 chiffres</label>
                                <input name="code" type="text" inputmode="numeric" pattern="[0-9]*"
                                    maxlength="6"
                                    class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500" />
                                @error('code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="flex justify-end">
                                    <button
                                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">
                                        <i class="ti ti-shield-check"></i> Confirmer 2FA
                                    </button>
                                </div>
                            </form>
                        @endif
                    @endif
                </section>

                <!-- Danger zone -->
                <section class="theme-danger rounded-lg p-6 shadow-sm">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-red-800">
                        <i class="ti ti-alert-triangle"></i> Zone de danger
                    </h2>
                    <p class="mb-3 text-sm text-red-700">Supprimer votre compte est irréversible.</p>
                    <form method="POST" action="{{ route('settings.destroy') }}" class="space-y-3">
                        @csrf
                        @method('DELETE')
                        <label class="block text-sm text-red-800">Confirmez avec votre mot de
                            passe</label>
                        <input name="password" type="password"
                            class="w-full rounded-lg border-red-300 focus:border-red-600 focus:ring-red-600" />
                        @error('password')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                        <div class="flex justify-end">
                            <button
                                class="inline-flex items-center gap-2 rounded-lg bg-red-700 px-4 py-2 text-white hover:bg-red-800">
                                <i class="ti ti-trash"></i> Supprimer mon compte
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
