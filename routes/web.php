<?php

use App\Http\Controllers\UrlController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//Url
Route::get('/{shortCode}', [UrlController::class, 'redirect']);