<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActivityApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API routes with auth middleware
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Activity API routes
    Route::apiResource('activities', ActivityApiController::class);
    Route::get('activities/stats', [ActivityApiController::class, 'stats']);
    Route::patch('activities/{activity}/status', [ActivityApiController::class, 'updateStatus']);
    
});
