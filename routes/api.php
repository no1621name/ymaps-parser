<?php

use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('user', fn (Request $request) => $request->user())->name('user');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::apiResources([
        'organizations' => OrganizationController::class,
    ], ['only' => ['index', 'show', 'store', 'destroy']]);

    Route::post('organizations/{organization}/refresh', [OrganizationController::class, 'refresh']);
    Route::get('organizations/{organization}/reviews', [ReviewController::class, 'index']);
});

Route::post('login', [LoginController::class, 'store'])->name('login');
