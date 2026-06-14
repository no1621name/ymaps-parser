<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('app'))->name('app');

Route::prefix('sanctum')->group(function () {
    Route::get('/csrf-cookie', fn () => response()->noContent())->name('sanctum.csrf-cookie');
});

Route::prefix('api')->group(function () {
    Route::post('login', [LoginController::class, 'store'])->name('login');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::get('{any}', fn () => view('app'))->where('any', '^((?!api).)*');
