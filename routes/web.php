<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('app'))->name('app');
Route::get('{any}', fn () => view('app'))->where('any', '^((?!api).)*');
