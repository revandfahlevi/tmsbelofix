<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/', function () {
    return 'API Running';
});

// ── Google OAuth ──────────────────────────────────────────────
// Harus di web.php (bukan api.php) karena butuh redirect browser
Route::get('/auth/google',          [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);