<?php

use App\Http\Controllers\ChaineController;
use App\Http\Controllers\EntiteController;
use App\Http\Controllers\KeyTokenController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoImportController;
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

    // Equipe / Entité
    Route::get('/entites', [EntiteController::class, 'index'])->name('entites.index');
    Route::post('/entites', [EntiteController::class, 'store'])->name('entites.store');
    Route::post('/entites/{entite}/invite', [EntiteController::class, 'invite'])->name('entites.members.invite');
    Route::delete('/entites/invitations/{membreEntite}', [EntiteController::class, 'cancelInvitation'])->name('entites.members.invitations.cancel');

    // Clés d'API
    Route::get('/keys', [KeyTokenController::class, 'index'])->name('keys.index');
    Route::post('/keys', [KeyTokenController::class, 'store'])->name('keys.store');
    Route::delete('/keys/{keyToken}', [KeyTokenController::class, 'destroy'])->name('keys.destroy');
    Route::post('/keys/{keyToken}/retest', [KeyTokenController::class, 'retest'])->name('keys.retest');
    Route::put('/keys/{keyToken}/entite', [KeyTokenController::class, 'updateEntite'])->name('keys.update-entite');

    // Importation de vidéos
    Route::get('/videos/import', [VideoImportController::class, 'index'])->name('videos.import');
    Route::post('/videos/import/analyze', [VideoImportController::class, 'analyzeUrl'])->name('videos.import.analyze');
    Route::post('/videos/import/url', [VideoImportController::class, 'storeFromUrl'])->name('videos.import.url');
    Route::post('/videos/import/channel', [VideoImportController::class, 'importFromChannel'])->name('videos.import.channel');

    // Liste des vidéos
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');

    // Chaînes (CRUD)
    Route::get('/chaines', [ChaineController::class, 'index'])->name('chaines.index');
    Route::post('/chaines', [ChaineController::class, 'store'])->name('chaines.store');
    Route::get('/chaines/{chaine}/edit', [ChaineController::class, 'edit'])->name('chaines.edit');
    Route::put('/chaines/{chaine}', [ChaineController::class, 'update'])->name('chaines.update');
    Route::delete('/chaines/{chaine}', [ChaineController::class, 'destroy'])->name('chaines.destroy');
});

// Signed routes for accept/reject that can be opened from email without auth
Route::get('/entites/invitations/{membreEntite}/accept', [EntiteController::class, 'acceptInvitation'])
    ->middleware('signed')
    ->name('entites.members.invitations.accept');
Route::get('/entites/invitations/{membreEntite}/reject', [EntiteController::class, 'rejectInvitation'])
    ->middleware('signed')
    ->name('entites.members.invitations.reject');
