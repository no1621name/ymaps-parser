<?php

use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResources([
    'organizations' => OrganizationController::class,
], ['only' => ['index', 'show', 'store', 'destroy']]);

Route::post('organizations/{organization}/refresh', [OrganizationController::class, 'refresh']);
Route::get('organizations/{organization}/reviews', [ReviewController::class, 'index']);
