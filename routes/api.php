<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RecommendationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ai/training-data', [RecommendationController::class, 'getTrainingData']);
Route::get('/ai/active-routes', [RecommendationController::class, 'getActiveRoutes']);
