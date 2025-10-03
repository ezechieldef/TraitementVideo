<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\LogoutOtherSessionsRequest;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\TwoFactorConfirmRequest;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $sessions = collect();
        if (Schema::hasTable('sessions')) {
            $sessions = DB::table('sessions')
                ->when(function () {
                    return config('session.driver') === 'database';
                })
                ->where('user_id', $user->getKey())
                ->orderByDesc('last_activity')
                ->get();
        }

        $twoFactorSecret = $user->two_factor_secret ?? null;
        $otpAuthUrl = null;
        if ($twoFactorSecret) {
            $otpAuthUrl = $this->twoFactor->makeOtpauthUrl($user, $twoFactorSecret, config('app.name'));
        }

        return view('settings.index', [
            'user' => $user,
            'sessions' => $sessions,
            'otpAuthUrl' => $otpAuthUrl,
        ]);
    }

    public function updateProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->fill($request->only(['name', 'email']));
        $user->save();

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        // Déconnecter les autres sessions en validant le mot de passe actuel
        Auth::logoutOtherDevices($request->validated('current_password'));

        // Mettre à jour le mot de passe
        $user->password = Hash::make($request->validated('password'));
        $user->save();

        return back()->with('success', 'Mot de passe mis à jour.');
    }

    public function logoutOtherSessions(LogoutOtherSessionsRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        Auth::logoutOtherDevices($request->validated('current_password'));

        if (Schema::hasTable('sessions') && config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->getKey())
                ->where('id', '!=', session()->getId())
                ->delete();
        }

        return back()->with('success', 'Autres sessions déconnectées.');
    }

    public function twoFactorEnable(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->two_factor_enabled) {
            return back()->with('error', 'La double authentification est déjà activée.');
        }

        $secret = $this->twoFactor->generateSecret();
        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = false;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return back()->with('success', 'Clé 2FA générée. Scannez le QR et confirmez avec un code.');
    }

    public function twoFactorConfirm(TwoFactorConfirmRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $secret = $user->two_factor_secret;
        if (! $secret) {
            return back()->with('error', 'Aucune clé 2FA à confirmer.');
        }

        $code = $request->validated('code');
        if (! $this->twoFactor->verifyCode($secret, $code)) {
            return back()->with('error', 'Code 2FA invalide.');
        }

        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->save();

        return back()->with('success', 'Double authentification activée.');
    }

    public function twoFactorDisable(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return back()->with('success', 'Double authentification désactivée.');
    }

    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Compte supprimé.');
    }
}
