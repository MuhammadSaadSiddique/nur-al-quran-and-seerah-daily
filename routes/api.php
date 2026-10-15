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
Route::post('/duas', [\App\Http\Controllers\DuaController::class, 'apiStore']);
Route::get('/duas/{id}', [\App\Http\Controllers\DuaController::class, 'apiShow']);

// Mobile Notifications & Push API endpoints
Route::get('/notifications', [\App\Http\Controllers\Api\NotificationApiController::class, 'index']);
Route::post('/device-token', [\App\Http\Controllers\Api\NotificationApiController::class, 'registerToken']);

// Research Contributions API endpoints (Public)
Route::get('/research-contributions', [\App\Http\Controllers\QuranResearchContributionController::class, 'apiIndex']);
Route::post('/research-contributions', [\App\Http\Controllers\QuranResearchContributionController::class, 'apiStore']);

// Categories API endpoints (Dua & Quran Research Categories)
Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'apiIndex']);
Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'apiStore']);

// User & App Feedback API endpoint (Mobile / Web)
Route::post('/app-feedback', [\App\Http\Controllers\AppFeedbackController::class, 'store']);
Route::post('/v1/app-feedback', [\App\Http\Controllers\AppFeedbackController::class, 'store']);

// Donation Notification API endpoint (Mobile / Web)
Route::post('/donate/notify', [\App\Http\Controllers\DonationController::class, 'storeNotification']);
Route::post('/v1/donate/notify', [\App\Http\Controllers\DonationController::class, 'storeNotification']);

// Protected endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/research', [ResearchApiController::class, 'store']);
    Route::post('/research-contributions/{id}/review', [\App\Http\Controllers\QuranResearchContributionController::class, 'apiReview']);
});


