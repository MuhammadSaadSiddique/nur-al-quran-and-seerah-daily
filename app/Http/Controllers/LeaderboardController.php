<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', 'lifetime');
        $currentUser = Auth::user();

        if ($period === 'weekly') {
            $topUsers = User::withSum(['quizzes' => function ($query) {
                $query->where('created_at', '>=', now()->startOfWeek());
            }], 'score')
            ->orderBy('quizzes_sum_score', 'desc')
            ->orderBy('total_questions', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($user) {
                // For the view consistency, we set total_score to the weekly sum
                $user->display_score = $user->quizzes_sum_score ?: 0;
                return $user;
            });

            // Find current user's rank for weekly
            $currentUserWeeklyScore = $currentUser->quizzes()
                ->where('created_at', '>=', now()->startOfWeek())
                ->sum('score');
            
            $currentUserRank = User::withSum(['quizzes' => function ($query) {
                $query->where('created_at', '>=', now()->startOfWeek());
            }], 'score')
            ->having('quizzes_sum_score', '>', $currentUserWeeklyScore)
            ->count() + 1;
            
            $currentUser->display_score = $currentUserWeeklyScore;
        } else {
            $topUsers = User::orderBy('total_score', 'desc')
                ->orderBy('total_questions', 'desc')
                ->limit(100)
                ->get()
                ->map(function ($user) {
                    $user->display_score = $user->total_score;
                    return $user;
                });

            $currentUserRank = User::where(function ($query) use ($currentUser) {
                $query->where('total_score', '>', $currentUser->total_score)
                    ->orWhere(function ($q) use ($currentUser) {
                        $q->where('total_score', $currentUser->total_score)
                            ->where('total_questions', '>', $currentUser->total_questions);
                    });
            })->count() + 1;
            
            $currentUser->display_score = $currentUser->total_score;
        }

        return view('leaderboard', compact('topUsers', 'currentUserRank', 'currentUser', 'period'));
    }
}
