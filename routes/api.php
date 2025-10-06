<?php

use Illuminate\Support\Facades\Route;

// API: Transcription (protected by Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/videos/{video}/transcription/youtube', [\App\Http\Controllers\Api\TranscriptionController::class, 'fetchFromYouTube'])
        ->name('api.videos.transcription.youtube');
    Route::get('/videos/{video}/transcription', [\App\Http\Controllers\Api\TranscriptionController::class, 'latest'])
        ->name('api.videos.transcription.latest');
    Route::post('/videos/{video}/transcription', [\App\Http\Controllers\Api\TranscriptionController::class, 'save'])
        ->name('api.videos.transcription.save');
    Route::get('/videos/{video}/getAvailableLanguages', [\App\Http\Controllers\Api\TranscriptionController::class, 'getAvailableTracks'])->name('api.videos.transcription.fetchAll');
    Route::get('/videos/{video}/transcription/languages', [\App\Http\Controllers\Api\TranscriptionController::class, 'listLanguages'])->name('api.videos.transcription.languages');
    Route::get('/videos/{video}/transcription/{langue}', [\App\Http\Controllers\Api\TranscriptionController::class, 'showByLanguage'])->name('api.videos.transcription.showByLanguage');

    // Sections
    Route::get('/videos/{video}/sections', [\App\Http\Controllers\Api\SectionController::class, 'index'])->name('api.videos.sections.index');
    Route::post('/videos/{video}/sections', [\App\Http\Controllers\Api\SectionController::class, 'store'])->name('api.videos.sections.store');
    Route::put('/videos/{video}/sections/{section}', [\App\Http\Controllers\Api\SectionController::class, 'update'])->name('api.videos.sections.update');
    Route::delete('/videos/{video}/sections/{section}', [\App\Http\Controllers\Api\SectionController::class, 'destroy'])->name('api.videos.sections.destroy');
    Route::post('/videos/{video}/sections/auto', [\App\Http\Controllers\Api\SectionController::class, 'auto'])->name('api.videos.sections.auto');
});
