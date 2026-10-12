<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StreakController extends Controller
{
    /**
     * Check in and maintain daily streak for the authenticated user.
     */
    public function checkIn(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $result = $user->checkInStreak();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Get current streak status for the authenticated user.
     */
    public function status(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'current_streak' => 0,
                'longest_streak' => 0,
                'is_active_today' => false,
                'last_streak_date' => null,
                'streak_history' => []
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user->getStreakStatus()
        ]);
    }
}
