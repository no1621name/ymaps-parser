<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('app'))->name('app');

Route::prefix('sanctum')->group(function () {
    Route::get('/csrf-cookie', fn () => response()->noContent())->name('sanctum.csrf-cookie');
});

Route::get('{any}', fn () => view('app'))->where('any', '^((?!api).)*');
