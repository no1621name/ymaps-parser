<?php

use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ParseEventController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('user', fn (Request $request) => $request->user())->name('user');
Route::pattern('organization', '[0-9]+');
Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::apiResources([
        'organizations' => OrganizationController::class,
    ], ['only' => ['index', 'show', 'store', 'destroy']]);

    Route::post('organizations/{organization}/refresh', [OrganizationController::class, 'refresh']);
    Route::get('organizations/{organization}/reviews', [ReviewController::class, 'index']);
    Route::get('organizations/{organization}/events', [ParseEventController::class, 'index']);
});
