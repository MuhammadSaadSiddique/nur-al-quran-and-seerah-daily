<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BulkQuestionController;
use App\Http\Middleware\ApiSecureToken;

Route::middleware([ApiSecureToken::class])->group(function () {
    Route::post('/upload-questions', [BulkQuestionController::class, 'store']);
});
