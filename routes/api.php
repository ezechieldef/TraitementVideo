<?php

use Illuminate\Support\Facades\Route;

// API: Transcription

Route::get('/videos/{video}/transcription/youtube', [\App\Http\Controllers\Api\TranscriptionController::class, 'fetchFromYouTube'])
    ->name('api.videos.transcription.youtube');
Route::get('/videos/{video}/transcription', [\App\Http\Controllers\Api\TranscriptionController::class, 'latest'])
    ->name('api.videos.transcription.latest');
Route::post('/videos/{video}/transcription', [\App\Http\Controllers\Api\TranscriptionController::class, 'save'])
    ->name('api.videos.transcription.save');
