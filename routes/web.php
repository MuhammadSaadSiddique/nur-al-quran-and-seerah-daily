<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionShowcaseController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ThemeController as AdminThemeController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    $testimonials = \App\Models\Testimonial::where('is_active', true)->latest()->get();
    $quranThemes = \App\Models\Theme::where('is_active', true)->where('type', 'PARA')->has('questions', '>=', 5)->orderBy('name')->get();
    $seerahThemes = \App\Models\Theme::where('is_active', true)->where('type', 'SEERAH')->has('questions', '>=', 5)->orderBy('name')->get();
    return view('welcome', compact('testimonials', 'quranThemes', 'seerahThemes'));
})->name('welcome');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/otp/request', [AuthController::class, 'requestOtp']);
Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);

Route::get('/auth/quran/redirect', [AuthController::class, 'redirectToQuran'])->name('quran.redirect');
Route::get('/oauth/callback', [AuthController::class, 'handleQuranCallback'])->name('quran.callback');

Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');

// Theme Showcase (Public for SEO)
Route::get('/themes', [\App\Http\Controllers\ThemeShowcaseController::class, 'index'])->name('themes.index');
Route::get('/themes/{theme:slug}', [\App\Http\Controllers\ThemeShowcaseController::class, 'show'])->name('themes.show');

Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('home');

    // Quizzes
    Route::post('/quiz/para', [QuizController::class, 'startParaQuiz'])->name('quiz.para');
    Route::post('/quiz/seerah', [QuizController::class, 'startSeerahQuiz'])->name('quiz.seerah');
    Route::post('/quiz/theme', [QuizController::class, 'startThemeQuiz'])->name('quiz.theme');
    Route::post('/quiz/grand', [QuizController::class, 'startGrandQuiz'])->name('quiz.grand');
    Route::post('/quiz/finish', [QuizController::class, 'finishQuiz'])->name('quiz.finish');
    Route::get('/quiz/history', [QuizController::class, 'history'])->name('quiz.history');

    // Insights
    Route::get('/seerah', [InsightController::class, 'seerah'])->name('seerah');
    Route::get('/quran-history', [InsightController::class, 'quranHistory'])->name('quran.history');
    //Route::get('/daily-dua', [InsightController::class, 'dailyDua'])->name('daily.dua');
    Route::post('/insight/answer', [InsightController::class, 'submitInsightAnswer'])->name('insight.answer');

    // Profile & Stats
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/stats', [ProfileController::class, 'stats'])->name('stats');
    Route::get('/bookmarks', [ProfileController::class, 'bookmarks'])->name('bookmarks');
    Route::post('/bookmark/toggle', [ProfileController::class, 'toggleBookmark'])->name('bookmark.toggle');
    Route::post('/testimonials/submit', [ProfileController::class, 'submitTestimonial'])->name('testimonials.submit');

    // Question Bank
    Route::get('/questions', [QuestionShowcaseController::class, 'index'])->name('questions.index');
    Route::get('/questions/{question}', [QuestionShowcaseController::class, 'show'])->name('questions.show');

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.questions.index');
        })->name('dashboard');

        Route::get('/questions', [AdminQuestionController::class, 'index'])->name('questions.index');
        Route::get('/questions/create', [AdminQuestionController::class, 'create'])->name('questions.create');
        Route::post('/questions', [AdminQuestionController::class, 'store'])->name('questions.store');
        Route::get('/questions/{question}/edit', [AdminQuestionController::class, 'edit'])->name('questions.edit');
        Route::put('/questions/{question}', [AdminQuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{question}', [AdminQuestionController::class, 'destroy'])->name('questions.destroy');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/toggle-admin', [AdminUserController::class, 'toggleAdmin'])->name('users.toggle-admin');

        Route::get('/feedback', [AdminQuestionController::class, 'feedbackIndex'])->name('feedback.index');
        Route::patch('/feedback/{feedback}/status', [AdminQuestionController::class, 'feedbackUpdateStatus'])->name('feedback.update-status');

        Route::get('/duplicates', [AdminQuestionController::class, 'duplicates'])->name('duplicates');
        Route::post('/duplicates/bulk-delete', [AdminQuestionController::class, 'bulkDelete'])->name('duplicates.bulk-delete');

        Route::post('/themes/merge', [AdminThemeController::class, 'merge'])->name('themes.merge');
        Route::resource('themes', AdminThemeController::class)->except(['create', 'edit', 'show']);
        Route::resource('testimonials', AdminTestimonialController::class)->except(['create', 'edit', 'show']);
    });

    // Quiz Feedback
    Route::post('/quiz/feedback', [QuizController::class, 'submitFeedback'])->name('quiz.feedback');

    // Logout
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');
});
