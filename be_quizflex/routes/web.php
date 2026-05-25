<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleSsoController;

Route::get('/', function () {
    return view('welcome');
});

// Tuyến đường cho Google SSO
Route::get('/auth/google/redirect', [GoogleSsoController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleSsoController::class, 'handleGoogleCallback']);
