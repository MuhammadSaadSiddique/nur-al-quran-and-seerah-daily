<?php

namespace App\Http\Controllers;

use App\Models\AppFeedback;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppFeedbackController extends Controller
{
    /**
     * Ensure the app_feedbacks table exists in production databases.
     */
    protected function ensureTableExists(): void
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('app_feedbacks')) {
                \Illuminate\Support\Facades\Schema::create('app_feedbacks', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                    $table->unsignedTinyInteger('rating');
                    $table->string('category', 50)->default('app_experience')->index();
                    $table->text('message')->nullable();
                    $table->string('platform', 50)->default('web')->index();
                    $table->string('user_name', 100)->nullable();
                    $table->string('user_email', 150)->nullable();
                    $table->string('device_info')->nullable();
                    $table->enum('status', ['pending', 'reviewed', 'resolved', 'dismissed'])->default('pending')->index();
                    $table->text('admin_notes')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AppFeedback table check warning: ' . $e->getMessage());
        }
    }

    /**
     * Store submitted user feedback.
     */
    public function store(Request $request)
    {
        $this->ensureTableExists();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'category' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:2500',
            'platform' => 'nullable|string|max:50',
            'user_name' => 'nullable|string|max:100',
            'user_email' => 'nullable|email|max:150',
            'device_info' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer',
        ]);

        $userId = Auth::id();
        $userName = $validated['user_name'] ?? null;
        $userEmail = $validated['user_email'] ?? null;

        if (!$userId && !empty($validated['user_id'])) {
            $existingUser = User::find($validated['user_id']);
            if ($existingUser) {
                $userId = $existingUser->id;
                $userName = $userName ?: ($existingUser->display_name ?: $existingUser->name);
                $userEmail = $userEmail ?: $existingUser->email;
            }
        } elseif ($userId) {
            $user = Auth::user();
            $userName = $userName ?: ($user->display_name ?: $user->name);
            $userEmail = $userEmail ?: $user->email;
        }

        $feedback = AppFeedback::create([
            'user_id' => $userId,
            'rating' => $validated['rating'],
            'category' => $validated['category'] ?? 'app_experience',
            'message' => $validated['message'] ?? null,
            'platform' => $validated['platform'] ?? 'web',
            'user_name' => $userName,
            'user_email' => $userEmail,
            'device_info' => $validated['device_info'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'JazakAllahu Khairan! Thank you for sharing your valuable feedback.',
            'data' => [
                'id' => $feedback->id,
                'rating' => $feedback->rating,
                'category' => $feedback->category,
            ]
        ]);
    }

    /**
     * Admin view for app feedbacks.
     */
    public function adminIndex(Request $request)
    {
        $this->ensureTableExists();

        $query = AppFeedback::with('user')->latest();

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appFeedbacks = $query->paginate(20)->withQueryString();

        return view('admin.feedback.app_feedback', compact('appFeedbacks'));
    }

    /**
     * Admin update status of app feedback.
     */
    public function updateStatus(Request $request, AppFeedback $appFeedback)
    {
        $request->validate(['status' => 'required|in:pending,reviewed,resolved']);
        $appFeedback->update(['status' => $request->status]);

        return back()->with('success', 'Feedback status updated.');
    }
}
