<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaderboardController extends Controller
{
    public function index(Request $request, \App\Services\QuranApiService $quranApiService)
    {
        $period = $request->query('period', 'lifetime');
        $currentUser = Auth::user();

        $quranStreak = 0;
        if ($currentUser && $currentUser->quran_access_token) {
            $quranStreak = $quranApiService->getUserStreak($currentUser) ?? 0;
        }

        if ($period === 'weekly') {
            $weekStart = now()->startOfWeek();
            $weeklyScore = '(COALESCE((SELECT SUM(score) FROM quizzes WHERE quizzes.user_id = users.id AND quizzes.created_at >= ?), 0)'
                . ' + COALESCE((SELECT SUM(points) FROM user_point_awards WHERE user_point_awards.user_id = users.id AND user_point_awards.created_at >= ?), 0))';

            $topUsers = User::query()
                ->select('users.*')
                ->selectRaw($weeklyScore . ' as weekly_score', [$weekStart, $weekStart])
                ->orderByDesc('weekly_score')
                ->orderByDesc('total_questions')
                ->limit(100)
                ->get()
                ->map(function ($user) {
                    $user->display_score = (int) $user->weekly_score;
                    return $user;
                });

            $currentUserWeeklyScore = 0;
            if ($currentUser) {
                $currentUserWeeklyScore = (int) $currentUser->quizzes()
                    ->where('created_at', '>=', $weekStart)
                    ->sum('score')
                    + (int) $currentUser->pointAwards()
                        ->where('created_at', '>=', $weekStart)
                        ->sum('points');
                $currentUser->display_score = $currentUserWeeklyScore;
            }

            $currentUserRank = User::whereRaw($weeklyScore . ' > ?', [$weekStart, $weekStart, $currentUserWeeklyScore])->count() + 1;
        } else {
            $topUsers = User::orderBy('total_score', 'desc')
                ->orderBy('total_questions', 'desc')
                ->limit(100)
                ->get()
                ->map(function ($user) {
                    $user->display_score = $user->total_score;
                    return $user;
                });

            $currentUserRank = 0;
            if ($currentUser) {
                $currentUserRank = User::where(function ($query) use ($currentUser) {
                    $query->where('total_score', '>', $currentUser->total_score)
                        ->orWhere(function ($q) use ($currentUser) {
                            $q->where('total_score', $currentUser->total_score)
                                ->where('total_questions', '>', $currentUser->total_questions);
                        });
                })->count() + 1;
            }
            if ($currentUser) {
                $currentUser->display_score = $currentUser->total_score;
            }
        }

        return view('leaderboard', compact('topUsers', 'currentUserRank', 'currentUser', 'period', 'quranStreak'));
    }

}