<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReadingController extends Controller
{
    /**
     * Toggle read/completed status of a Surah (1 to 114).
     */
    public function toggleSurah(Request $request)
    {
        $request->validate([
            'surah' => 'required|integer|min:1|max:114',
        ]);

        $user = Auth::user();
        $result = $user->toggleCompletedSurah((int) $request->surah);
        $streak = $user->getStreakStatus();

        return response()->json([
            'success' => true,
            'is_read' => $result['is_read'],
            'surah' => $result['surah_number'],
            'total_completed' => $result['total_completed'],
            'streak' => $streak,
            'message' => $result['is_read']
                ? "Masha'Allah! Surah marked as read."
                : "Surah marked as unread."
        ]);
    }

    /**
     * Toggle read/completed status of a Para (1 to 30).
     */
    public function togglePara(Request $request)
    {
        $request->validate([
            'para' => 'required|integer|min:1|max:30',
        ]);

        $user = Auth::user();
        $result = $user->toggleCompletedPara((int) $request->para);
        $streak = $user->getStreakStatus();

        return response()->json([
            'success' => true,
            'is_completed' => $result['is_completed'],
            'para' => $result['para_number'],
            'total_completed' => $result['total_completed'],
            'streak' => $streak,
            'message' => $result['is_completed']
                ? "Masha'Allah! Para {$request->para} marked as completed."
                : "Para {$request->para} marked as incomplete."
        ]);
    }

    /**
     * Mark a Seerah or Quran History insight as read.
     */
    public function markInsight(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:SEERAH,QURAN_HISTORY,seerah,quran_history',
            'insight_id' => 'nullable|string',
        ]);

        $user = Auth::user();
        $type = strtoupper($request->type);
        $result = $user->markInsightRead($type, $request->insight_id);
        $streak = $user->getStreakStatus();

        $typeName = ($type === 'SEERAH') ? 'Seerah reflection' : 'Quran history reflection';

        return response()->json([
            'success' => true,
            'already_read' => $result['already_read'],
            'type' => $type,
            'seerah_read_count' => $result['seerah_read_count'],
            'quran_history_read_count' => $result['quran_history_read_count'],
            'streak' => $streak,
            'message' => $result['already_read']
                ? "You have already completed this {$typeName}."
                : "Masha'Allah! {$typeName} completed."
        ]);
    }
}
