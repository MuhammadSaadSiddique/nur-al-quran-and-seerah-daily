<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\Feedback;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'No active account found with this email address.'
            ], 404);
        }

        // Clean up previous OTPs for this email
        OtpCode::where('email', $email)->delete();

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            Log::error('Failed to send Account Deletion OTP email', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Unable to send verification email. Please try again later.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "A 6-digit confirmation code has been sent to {$email}. Please enter it below to confirm permanent deletion of your account."
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
        $record = OtpCode::where('email', $email)->latest()->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'error' => 'No pending verification request found. Please request a new code.'
            ], 400);
        }

        if ($record->isExpired()) {
            $record->delete();
            return response()->json([
                'success' => false,
                'error' => 'The verification code has expired. Please request a new one.'
            ], 400);
        }

        if ($record->otp !== trim($request->otp)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid verification code. Please check your email and try again.'
            ], 400);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $record->delete();
            return response()->json([
                'success' => false,
                'error' => 'Account not found or already deleted.'
            ], 404);
        }

        $record->delete();
        $this->performAccountDeletion($user);

        return response()->json([
            'success' => true,
            'message' => 'Your account and all associated data have been permanently deleted.'
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

        // If user has a password set, require password verification
        if (!empty($user->password)) {
            $request->validate([
                'password' => 'required|string',
            ]);

            if (!Hash::check($request->password, $user->password)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'The password you entered is incorrect.'
                    ], 422);
                }
                return back()->withErrors(['password' => 'The password you entered is incorrect.']);
            }
        } else {
            // Require explicit confirmation checkbox or parameter
            $request->validate([
                'confirm_deletion' => 'required|in:1,true,on',
            ]);
        }

        $userToDelete = User::find($user->id);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($userToDelete) {
            $this->performAccountDeletion($userToDelete);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your account and all associated data have been permanently deleted.'
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
            // 1. Quizzes
            $user->quizzes()->delete();

            // 2. Feedback
            Feedback::where('user_id', $user->id)->delete();

            // 3. Mobile Device Tokens
            if (Schema::hasTable('device_tokens')) {
                DB::table('device_tokens')->where('user_id', $user->id)->delete();
            }

            // 4. App Notifications sent by user
            if (Schema::hasTable('app_notifications')) {
                DB::table('app_notifications')->where('sent_by', $user->id)->delete();
            }

            // 5. Quranic Lens contributions (if any)
            if (Schema::hasTable('quranic_analyses')) {
                DB::table('quranic_analyses')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('quranic_word_tags')) {
                DB::table('quranic_word_tags')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('quranic_verse_tags')) {
                DB::table('quranic_verse_tags')->where('user_id', $user->id)->delete();
            }

            // 6. Delete OTP codes
            OtpCode::where('email', $user->email)->delete();

            // 7. Delete user record
            $user->delete();
        });
    }
}
