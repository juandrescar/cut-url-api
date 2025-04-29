<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UrlController;
use Illuminate\Support\Facades\Route;

//Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/', [UrlController::class, 'index']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/shorten', [UrlController::class, 'store']);
    Route::get('/urls/{urlId}', [UrlController::class, 'show']);
});
