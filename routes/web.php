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
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\QuranicLensController;
use App\Http\Controllers\Admin\QuranicLensApprovalController;
use App\Http\Controllers\AccountDeletionController;
use App\Http\Controllers\DuaController;
use App\Http\Controllers\ReadingController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [QuranicLensController::class, 'landing'])->name('lens.landing');

// Authentic Duas & Meanings
Route::get('/duas', [DuaController::class, 'index'])->name('duas.index');
Route::get('/duas/create', [DuaController::class, 'create'])->name('duas.create');
Route::post('/duas', [DuaController::class, 'store'])->name('duas.store');
Route::get('/duas/{dua:slug}', [DuaController::class, 'show'])->name('duas.show');
Route::get('/daily-dua', [DuaController::class, 'daily'])->name('daily.dua');

Route::get('/quiz-learning', function () {
    $testimonials = \App\Models\Testimonial::where('is_active', true)->latest()->get();
    $quranThemes = \App\Models\Theme::where('is_active', true)->where('type', 'PARA')->has('questions', '>=', 5)->orderBy('name')->get();
    $seerahThemes = \App\Models\Theme::where('is_active', true)->where('type', 'SEERAH')->has('questions', '>=', 5)->orderBy('name')->get();
    return view('welcome', compact('testimonials', 'quranThemes', 'seerahThemes'));
})->name('quiz.learning');

Route::get('/quran-research', [QuranicLensController::class, 'index'])->name('welcome');

// Community Quran Research Contributions (Videos, verification, pending items)
Route::get('/quran-research/contributions', [\App\Http\Controllers\QuranResearchContributionController::class, 'index'])->name('research.contributions.index');
Route::get('/quran-research/contributions/create', [\App\Http\Controllers\QuranResearchContributionController::class, 'create'])->name('research.contributions.create');
Route::post('/quran-research/contributions', [\App\Http\Controllers\QuranResearchContributionController::class, 'store'])->name('research.contributions.store');
Route::post('/quran-research/contributions/{id}/review', [\App\Http\Controllers\QuranResearchContributionController::class, 'review'])->name('research.contributions.review')->middleware('auth');

// Add Dua or Quran Research Category (Users & Admins)
Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/otp/request', [AuthController::class, 'requestOtp'])->middleware('throttle:5,1');
Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
Route::post('/login/password', [AuthController::class, 'loginWithPassword'])->name('login.password')->middleware('throttle:10,1');

Route::get('/auth/quran/redirect', [AuthController::class, 'redirectToQuran'])->name('quran.redirect');
Route::get('/oauth/callback', [AuthController::class, 'handleQuranCallback'])->name('quran.callback');

Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');

// Account Deletion & Data Erasure (Google Play & App Store Compliance)
Route::get('/delete-account', [AccountDeletionController::class, 'show'])->name('account.delete');
Route::get('/account-delete', function () {
    return redirect()->route('account.delete');
});
Route::post('/delete-account/request-otp', [AccountDeletionController::class, 'requestOtp'])->name('account.delete.request-otp')->middleware('throttle:5,1');
Route::post('/delete-account/verify-otp', [AccountDeletionController::class, 'verifyAndDeleteOtp'])->name('account.delete.verify-otp')->middleware('throttle:10,1');
Route::post('/delete-account/confirm', [AccountDeletionController::class, 'confirmDelete'])->name('account.delete.confirm')->middleware('auth');

// Theme Showcase (Public for SEO)
Route::get('/themes', [\App\Http\Controllers\ThemeShowcaseController::class, 'index'])->name('themes.index');
Route::get('/themes/{theme:slug}', [\App\Http\Controllers\ThemeShowcaseController::class, 'show'])->name('themes.show');

Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Researchers Directory
Route::get('/researchers', [\App\Http\Controllers\ResearchersController::class, 'index'])->name('researchers.index');
Route::post('/researchers/join', [\App\Http\Controllers\ResearchersController::class, 'join'])->name('researchers.join')->middleware('auth');
Route::post('/researchers/{user}/update-expert', [\App\Http\Controllers\ResearchersController::class, 'updateExpert'])->name('researchers.update-expert')->middleware('auth');

// Quranic Lens public routes
Route::prefix('lens')->name('lens.')->group(function () {
    Route::get('/', function(\Illuminate\Http\Request $request) {
        return redirect()->route('welcome', $request->query());
    })->name('index');
    Route::get('/{chapter}', [QuranicLensController::class, 'surah'])->name('surah');
    Route::get('/{chapter}/{verse}', [QuranicLensController::class, 'verse'])->name('verse');
});

// Insights (Publicly accessible; tracks progress when authenticated)
Route::get('/seerah', [InsightController::class, 'seerah'])->name('seerah');
Route::get('/quran-history', [InsightController::class, 'quranHistory'])->name('quran.history');
//Route::get('/daily-dua', [InsightController::class, 'dailyDua'])->name('daily.dua');
Route::post('/insight/answer', [InsightController::class, 'submitInsightAnswer'])->name('insight.answer');

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

    // Reading & Progress Tracking (Mark as Read)
    Route::post('/reading/toggle-surah', [ReadingController::class, 'toggleSurah'])->name('reading.toggle-surah');
    Route::post('/reading/toggle-para', [ReadingController::class, 'togglePara'])->name('reading.toggle-para');
    Route::post('/reading/mark-insight', [ReadingController::class, 'markInsight'])->name('reading.mark-insight');

    // Profile & Stats
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/stats', [ProfileController::class, 'stats'])->name('stats');
    Route::get('/bookmarks', [ProfileController::class, 'bookmarks'])->name('bookmarks');
    Route::post('/bookmark/toggle', [ProfileController::class, 'toggleBookmark'])->name('bookmark.toggle');
    Route::post('/testimonials/submit', [ProfileController::class, 'submitTestimonial'])->name('testimonials.submit');
    Route::post('/streak/check-in', [\App\Http\Controllers\StreakController::class, 'checkIn'])->name('streak.check-in');
    Route::get('/streak/status', [\App\Http\Controllers\StreakController::class, 'status'])->name('streak.status');

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
        Route::patch('/users/{user}/toggle-researcher', [AdminUserController::class, 'toggleResearcher'])->name('users.toggle-researcher');

        Route::get('/feedback', [AdminQuestionController::class, 'feedbackIndex'])->name('feedback.index');
        Route::patch('/feedback/{feedback}/status', [AdminQuestionController::class, 'feedbackUpdateStatus'])->name('feedback.update-status');

        Route::get('/duplicates', [AdminQuestionController::class, 'duplicates'])->name('duplicates');
        Route::post('/duplicates/bulk-delete', [AdminQuestionController::class, 'bulkDelete'])->name('duplicates.bulk-delete');

        Route::post('/themes/merge', [AdminThemeController::class, 'merge'])->name('themes.merge');
        Route::resource('themes', AdminThemeController::class)->except(['create', 'edit', 'show']);
        Route::resource('testimonials', AdminTestimonialController::class)->except(['create', 'edit', 'show']);

        Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AdminAnnouncementController::class, 'send'])->name('announcements.send');

        Route::get('/duas', [\App\Http\Controllers\Admin\DuaController::class, 'index'])->name('duas.index');
        Route::get('/duas/create', [\App\Http\Controllers\Admin\DuaController::class, 'create'])->name('duas.create');
        Route::post('/duas', [\App\Http\Controllers\Admin\DuaController::class, 'store'])->name('duas.store');
        Route::get('/duas/{id}/edit', [\App\Http\Controllers\Admin\DuaController::class, 'edit'])->name('duas.edit');
        Route::put('/duas/{id}', [\App\Http\Controllers\Admin\DuaController::class, 'update'])->name('duas.update');
        Route::post('/duas/{id}/approve', [\App\Http\Controllers\Admin\DuaController::class, 'approve'])->name('duas.approve');
        Route::post('/duas/{id}/reject', [\App\Http\Controllers\Admin\DuaController::class, 'reject'])->name('duas.reject');
        Route::delete('/duas/{id}', [\App\Http\Controllers\Admin\DuaController::class, 'destroy'])->name('duas.destroy');

        // Manage Dua & Quran Research Categories
        Route::get('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
        Route::post('/categories/merge', [\App\Http\Controllers\Admin\CategoryController::class, 'merge'])->name('categories.merge');
        Route::put('/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('categories.update');
        Route::match(['post', 'patch'], '/categories/{category}/toggle-active', [\App\Http\Controllers\Admin\CategoryController::class, 'toggleActive'])->name('categories.toggle-active');
        Route::delete('/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Quranic Lens approvals (accessible by Researchers and Admins)
    Route::middleware('researcher')->prefix('admin/lens')->name('admin.lens.')->group(function () {
        Route::get('/approvals', [QuranicLensApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{type}/{id}/approve', [QuranicLensApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{type}/{id}/reject', [QuranicLensApprovalController::class, 'reject'])->name('approvals.reject');
        Route::post('/approvals/connection/{table}/{id}/approve', [QuranicLensApprovalController::class, 'approveConnection'])->name('approvals.connection.approve');
        Route::post('/approvals/connection/{table}/{id}/reject', [QuranicLensApprovalController::class, 'rejectConnection'])->name('approvals.connection.reject');
        Route::post('/approvals/create-link', [QuranicLensApprovalController::class, 'createConnectionLink'])->name('approvals.create-link');
        Route::delete('/approvals/analysis/{id}', [QuranicLensApprovalController::class, 'destroyAnalysis'])->name('approvals.analysis.destroy');
        Route::post('/approvals/science-category', [QuranicLensApprovalController::class, 'storeScienceCategory'])->name('approvals.science-category.store');
        Route::put('/approvals/science-category/{id}', [QuranicLensApprovalController::class, 'updateScienceCategory'])->name('approvals.science-category.update');
        Route::delete('/approvals/science-category/{id}', [QuranicLensApprovalController::class, 'destroyScienceCategory'])->name('approvals.science-category.destroy');
    });

    // Quiz Feedback
    Route::post('/quiz/feedback', [QuizController::class, 'submitFeedback'])->name('quiz.feedback');

    // Quranic Lens authenticated routes
    Route::prefix('lens')->name('lens.')->group(function () {
        Route::post('/analysis', [QuranicLensController::class, 'storeAnalysis'])->name('analysis.store');
        Route::post('/analysis/ai', [QuranicLensController::class, 'generateAiAnalysis'])->name('analysis.ai');
        Route::post('/tag/word', [QuranicLensController::class, 'storeWordTag'])->name('tag.word.store');
        Route::post('/tag/verse', [QuranicLensController::class, 'storeVerseTag'])->name('tag.verse.store');
    });

    // Logout
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');
});
