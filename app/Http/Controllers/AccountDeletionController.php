<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpRateLimiter;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AccountDeletionController extends Controller
{
    /**
     * Display the public Account Deletion page.
     */
    public function show(Request $request)
    {
        $user = Auth::user();
        return view('account.delete', compact('user'));
    }

    /**
     * Request an OTP for account deletion (for guests/external visitors).
     */
    public function requestOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($request->email));

        if (OtpRateLimiter::tooManyRequests($email, 'request')) {
            return response()->json([
                'success' => false,
                'error' => 'Too many deletion requests. Please try again later.',
            ], 429);
        }

        $user = User::where('email', $email)->first();

        // Avoid account enumeration: always return the same success-shaped response.
        if ($user) {
            try {
                OtpService::issueAndSend($email, 10);
                OtpRateLimiter::hit($email, 'request');
            } catch (\RuntimeException $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        } else {
            OtpRateLimiter::hit($email, 'request');
        }

        return response()->json([
            'success' => true,
            'message' => "If an account exists for {$email}, a 6-digit confirmation code has been sent. Enter it below to confirm permanent deletion.",
        ]);
    }

    /**
     * Verify OTP and permanently delete the account (for guests/external visitors).
     */
    public function verifyAndDeleteOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        $email = strtolower(trim($request->email));

        if (OtpRateLimiter::tooManyRequests($email, 'verify')) {
            return response()->json([
                'success' => false,
                'error' => 'Too many verification attempts. Please try again later.',
            ], 429);
        }

        if (!OtpService::verify($email, $request->otp)) {
            OtpRateLimiter::hit($email, 'verify');
            return response()->json([
                'success' => false,
                'error' => 'Invalid or expired verification code. Please request a new one.',
            ], 400);
        }

        OtpRateLimiter::clearVerifyAttempts($email);

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Account not found or already deleted.',
            ], 404);
        }

        self::performAccountDeletion($user);

        return response()->json([
            'success' => true,
            'message' => 'Your account and all associated data have been permanently deleted.',
        ]);
    }

    /**
     * Confirm and permanently delete the currently authenticated user's account.
     */
    public function confirmDelete(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('account.delete')->with('error', 'You must be logged in to perform this action.');
        }

        if (!empty($user->password)) {
            $request->validate([
                'password' => 'required|string',
            ]);

            if (!Hash::check($request->password, $user->password)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'The password you entered is incorrect.',
                    ], 422);
                }
                return back()->withErrors(['password' => 'The password you entered is incorrect.']);
            }
        } else {
            $request->validate([
                'confirm_deletion' => 'required|in:1,true,on',
            ]);
        }

        $userToDelete = User::find($user->id);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($userToDelete) {
            self::performAccountDeletion($userToDelete);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your account and all associated data have been permanently deleted.',
            ]);
        }

        return redirect()->route('account.delete')->with('success', 'Your account and all associated data have been successfully and permanently deleted.');
    }

    /**
     * Permanently purge all user data across all tables.
     */
    public static function performAccountDeletion(User $user): void
    {
        DB::transaction(function () use ($user) {
            try {
                $user->tokens()->delete();
            } catch (\Throwable $e) {
                // Sanctum tokens table may be absent in older installs.
            }

            $user->quizzes()->delete();

            Feedback::where('user_id', $user->id)->delete();

            if (Schema::hasTable('device_tokens')) {
                DB::table('device_tokens')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('app_notifications')) {
                DB::table('app_notifications')->where('sent_by', $user->id)->delete();
            }

            if (Schema::hasTable('quranic_analyses')) {
                DB::table('quranic_analyses')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('quranic_word_tags')) {
                DB::table('quranic_word_tags')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('quranic_verse_tags')) {
                DB::table('quranic_verse_tags')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('user_daily_engagements')) {
                DB::table('user_daily_engagements')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('quran_research_reviews')) {
                DB::table('quran_research_reviews')->where('user_id', $user->id)->delete();
            }

            OtpCode::where('email', $user->email)->delete();

            $user->delete();
        });
    }
}
