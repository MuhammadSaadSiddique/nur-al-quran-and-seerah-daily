<?php

namespace App\Http\Controllers;

use App\Models\UserDailyEngagement;
use App\Services\DailyEngagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EngagementController extends Controller
{
    public function __construct(
        private DailyEngagementService $engagementService
    ) {
    }

    /**
     * Today's engagement checklist + streak for the authenticated user.
     */
    public function today(Request $request)
    {
        $user = Auth::user();
        $status = $this->engagementService->todayStatus($user);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Explicitly mark today's daily dua as completed.
     */
    public function markDua(Request $request)
    {
        $user = Auth::user();
        $status = $this->engagementService->record($user, UserDailyEngagement::ACTIVITY_DUA);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Masha'Allah! Today's dua recorded.",
                'data' => $status,
            ]);
        }

        return back()->with('success', "Masha'Allah! Today's dua recorded.");
    }

    /**
     * Self-mark only for dua. Quiz / read / insight must come from real actions.
     */
    public function mark(Request $request)
    {
        $request->validate([
            'activity' => 'required|string|in:' . UserDailyEngagement::ACTIVITY_DUA,
        ]);

        $user = Auth::user();
        $status = $this->engagementService->record($user, UserDailyEngagement::ACTIVITY_DUA);

        return response()->json([
            'success' => true,
            'message' => 'Engagement recorded.',
            'data' => $status,
        ]);
    }
}
