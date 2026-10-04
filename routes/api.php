<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BulkQuestionController;
use App\Http\Controllers\Api\ResearchApiController;
use App\Http\Middleware\ApiSecureToken;

Route::middleware([ApiSecureToken::class, 'throttle:60,1'])->group(function () {
    Route::post('/upload-questions', [BulkQuestionController::class, 'store']);
});

// Public endpoints
Route::post('/token', [ResearchApiController::class, 'token']);
Route::get('/research', [ResearchApiController::class, 'index']);

// Duas Public API endpoints
Route::get('/duas', [\App\Http\Controllers\DuaController::class, 'apiIndex']);
Route::get('/duas/{id}', [\App\Http\Controllers\DuaController::class, 'apiShow']);

// Mobile Notifications & Push API endpoints
Route::get('/notifications', [\App\Http\Controllers\Api\NotificationApiController::class, 'index']);
Route::post('/device-token', [\App\Http\Controllers\Api\NotificationApiController::class, 'registerToken']);

// Protected endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/research', [ResearchApiController::class, 'store']);
});


