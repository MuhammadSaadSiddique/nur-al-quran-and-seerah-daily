<?php

namespace App\Http\Controllers;

use App\Models\GeneratedQuestion;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionShowcaseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->get('tab', 'attempted'); // 'attempted' or 'bookmarks'

        if ($tab === 'bookmarks') {
            return $this->bookmarksTab($request, $user);
        }

        return $this->attemptedTab($request, $user);
    }

    /**
     * Show only questions the user has attempted via quizzes.
     */
    protected function attemptedTab(Request $request, $user)
    {
        // Collect all question IDs the user has attempted from quiz history
        $quizzes = Quiz::where('user_id', $user->id)->get();
        $attemptedQuestionIds = [];

        foreach ($quizzes as $quiz) {
            $details = $quiz->details;
            if (!empty($details['questions'])) {
                foreach ($details['questions'] as $q) {
                    if (isset($q['id'])) {
                        $attemptedQuestionIds[] = $q['id'];
                    }
                }
            }
        }

        $attemptedQuestionIds = array_unique($attemptedQuestionIds);

        // If user has not attempted any questions
        if (empty($attemptedQuestionIds)) {
            return view('questions.index', [
                'questions' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'totalQuestions' => 0,
                'attemptedCount' => 0,
                'bookmarkCount' => count($user->bookmarked_questions ?? []),
                'types' => collect(),
                'themes' => collect(),
                'avgAccuracy' => 0,
                'tab' => 'attempted',
            ]);
        }

        $query = GeneratedQuestion::whereIn('question_id', $attemptedQuestionIds)->latest();

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }
        if ($request->filled('theme')) {
            $query->where('theme', $request->theme);
        }
        if ($request->filled('search')) {
            $query->where('text', 'LIKE', '%' . $request->search . '%');
        }

        $questions = $query->paginate(20);

        // Aggregates for the user's attempted questions only
        $attemptedBase = GeneratedQuestion::whereIn('question_id', $attemptedQuestionIds);
        $totalQuestions = (clone $attemptedBase)->count();
        $types = (clone $attemptedBase)->selectRaw('type, count(*) as count')->groupBy('type')->pluck('count', 'type');
        $themes = (clone $attemptedBase)->whereNotNull('theme')->distinct('theme')->pluck('theme');
        $avgAccuracy = (clone $attemptedBase)->where('times_answered', '>', 0)
            ->selectRaw('ROUND(AVG(times_correct * 100.0 / times_answered)) as avg')
            ->value('avg') ?? 0;

        return view('questions.index', compact(
            'questions', 'totalQuestions', 'types', 'themes', 'avgAccuracy'
        ) + [
            'attemptedCount' => count($attemptedQuestionIds),
            'bookmarkCount' => count($user->bookmarked_questions ?? []),
            'tab' => 'attempted',
        ]);
    }

    /**
     * Show the user's bookmarked questions.
     */
    protected function bookmarksTab(Request $request, $user)
    {
        $bookmarks = $user->bookmarked_questions ?? [];
        $bookmarkCount = count($bookmarks);

        // Extract question IDs from bookmarks
        $bookmarkQuestionIds = [];
        foreach ($bookmarks as $bm) {
            $qId = $bm['question']['id'] ?? ($bm['id'] ?? null);
            if ($qId) {
                $bookmarkQuestionIds[] = $qId;
            }
        }

        if (empty($bookmarkQuestionIds)) {
            // Count attempted for tab badge
            $quizzes = Quiz::where('user_id', $user->id)->get();
            $attemptedIds = [];
            foreach ($quizzes as $quiz) {
                $details = $quiz->details;
                if (!empty($details['questions'])) {
                    foreach ($details['questions'] as $q) {
                        if (isset($q['id'])) {
                            $attemptedIds[] = $q['id'];
                        }
                    }
                }
            }

            return view('questions.index', [
                'questions' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'totalQuestions' => 0,
                'attemptedCount' => count(array_unique($attemptedIds)),
                'bookmarkCount' => 0,
                'types' => collect(),
                'themes' => collect(),
                'avgAccuracy' => 0,
                'tab' => 'bookmarks',
            ]);
        }

        $query = GeneratedQuestion::whereIn('question_id', $bookmarkQuestionIds)->latest();

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }
        if ($request->filled('theme')) {
            $query->where('theme', $request->theme);
        }
        if ($request->filled('search')) {
            $query->where('text', 'LIKE', '%' . $request->search . '%');
        }

        $questions = $query->paginate(20);

        // Aggregates for bookmarked questions
        $bmBase = GeneratedQuestion::whereIn('question_id', $bookmarkQuestionIds);
        $totalQuestions = (clone $bmBase)->count();
        $types = (clone $bmBase)->selectRaw('type, count(*) as count')->groupBy('type')->pluck('count', 'type');
        $themes = (clone $bmBase)->whereNotNull('theme')->distinct('theme')->pluck('theme');
        $avgAccuracy = (clone $bmBase)->where('times_answered', '>', 0)
            ->selectRaw('ROUND(AVG(times_correct * 100.0 / times_answered)) as avg')
            ->value('avg') ?? 0;

        // Count attempted for tab badge
        $quizzes = Quiz::where('user_id', $user->id)->get();
        $attemptedIds = [];
        foreach ($quizzes as $quiz) {
            $details = $quiz->details;
            if (!empty($details['questions'])) {
                foreach ($details['questions'] as $q) {
                    if (isset($q['id'])) {
                        $attemptedIds[] = $q['id'];
                    }
                }
            }
        }

        return view('questions.index', compact(
            'questions', 'totalQuestions', 'types', 'themes', 'avgAccuracy'
        ) + [
            'attemptedCount' => count(array_unique($attemptedIds)),
            'bookmarkCount' => $bookmarkCount,
            'tab' => 'bookmarks',
        ]);
    }

    public function show(GeneratedQuestion $question)
    {
        return view('questions.show', compact('question'));
    }
}
