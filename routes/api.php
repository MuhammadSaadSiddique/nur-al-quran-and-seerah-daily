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

// Protected endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/research', [ResearchApiController::class, 'store']);
});
