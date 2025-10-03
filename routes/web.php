<?php

use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');

    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/settings/sessions/logout-others', [SettingsController::class, 'logoutOtherSessions'])->name('settings.sessions.logout-others');

    Route::post('/settings/2fa/enable', [SettingsController::class, 'twoFactorEnable'])->name('settings.2fa.enable');
    Route::post('/settings/2fa/confirm', [SettingsController::class, 'twoFactorConfirm'])->name('settings.2fa.confirm');
    Route::delete('/settings/2fa/disable', [SettingsController::class, 'twoFactorDisable'])->name('settings.2fa.disable');

    Route::delete('/settings', [SettingsController::class, 'destroy'])->name('settings.destroy');
});
