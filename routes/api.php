<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BulkQuestionController;
use App\Http\Controllers\Api\ResearchApiController;
use App\Http\Middleware\ApiSecureToken;

Route::middleware([ApiSecureToken::class, 'throttle:60,1'])->group(function () {
    Route::post('/upload-questions', [BulkQuestionController::class, 'store']);
});

// Public token issuance
Route::post('/token', [ResearchApiController::class, 'token'])->middleware('throttle:10,1');
Route::get('/research', [ResearchApiController::class, 'index']);

// Public read endpoints
Route::get('/duas', [\App\Http\Controllers\DuaController::class, 'apiIndex']);
Route::get('/duas/{id}', [\App\Http\Controllers\DuaController::class, 'apiShow']);
Route::get('/notifications', [\App\Http\Controllers\Api\NotificationApiController::class, 'index']);
Route::get('/research-contributions', [\App\Http\Controllers\QuranResearchContributionController::class, 'apiIndex']);
Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'apiIndex']);

// Public write endpoints with throttle (no account mutation)
Route::post('/device-token', [\App\Http\Controllers\Api\NotificationApiController::class, 'registerToken'])
    ->middleware('throttle:30,1');
Route::post('/app-feedback', [\App\Http\Controllers\AppFeedbackController::class, 'store'])
    ->middleware('throttle:10,1');
Route::post('/v1/app-feedback', [\App\Http\Controllers\AppFeedbackController::class, 'store'])
    ->middleware('throttle:10,1');
Route::post('/donate/notify', [\App\Http\Controllers\DonationController::class, 'storeNotification'])
    ->middleware('throttle:10,1');
Route::post('/v1/donate/notify', [\App\Http\Controllers\DonationController::class, 'storeNotification'])
    ->middleware('throttle:10,1');

// Authenticated write endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/duas', [\App\Http\Controllers\DuaController::class, 'apiStore']);
    Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'apiStore']);
    Route::post('/research-contributions', [\App\Http\Controllers\QuranResearchContributionController::class, 'apiStore']);
    Route::post('/research', [ResearchApiController::class, 'store']);
    Route::post('/research-contributions/{id}/review', [\App\Http\Controllers\QuranResearchContributionController::class, 'apiReview']);
});
