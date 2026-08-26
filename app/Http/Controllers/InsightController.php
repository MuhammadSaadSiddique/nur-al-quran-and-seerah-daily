<?php

namespace App\Http\Controllers;

use App\Models\GeneratedQuestion;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InsightController extends Controller
{
    public function seerah(Request $request, GeminiService $gemini)
    {
        $difficulty = $request->get('difficulty', 'Medium');

        $insights = GeneratedQuestion::where('type', 'SEERAH_INSIGHT')
            ->where('difficulty', $difficulty)
            ->latest()
            ->paginate(1);

        if ($request->has('generate') || $insights->isEmpty()) {
            $newInsight = $gemini->generateSeerahInsight($difficulty);

            if (!$newInsight) {
                return back()->with('error', 'Failed to load Seerah insight. Please try again.');
            }

            if (isset($newInsight['question'])) {
                GeneratedQuestion::create([
                    'question_id' => $newInsight['question']['id'] ?? uniqid('q-seerah-'),
                    'type' => 'SEERAH_INSIGHT',
                    'source_info' => 'Seerah Insight',
                    'difficulty' => $newInsight['question']['difficulty'] ?? $difficulty,
                    'theme' => $newInsight['question']['theme'] ?? null,
                    'text' => $newInsight['question']['text'],
                    'options' => $newInsight['question']['options'],
                    'correct_answer_index' => $newInsight['question']['correctAnswerIndex'],
                    'explanation' => $newInsight['question']['explanation'] ?? null,
                    'reference' => $newInsight['question']['reference'] ?? null,
                    'insight_title' => $newInsight['title'],
                    'insight_content' => $newInsight['content'],
                ]);
            }

            $user = Auth::user();
            $user->seerah_read_count += 1;
            $user->save();

            return redirect()->route('seerah', ['difficulty' => $difficulty]);
        }

        $insightItem = $insights->first();
        $insight = [
            'title' => $insightItem->insight_title ?? 'Seerah Reflection',
            'content' => $insightItem->insight_content ?? 'Reflection context is not available for this older question.',
            'question' => [
                'id' => $insightItem->question_id,
                'text' => $insightItem->text,
                'options' => $insightItem->options,
                'correctAnswerIndex' => $insightItem->correct_answer_index,
                'explanation' => $insightItem->explanation,
                'theme' => $insightItem->theme,
                'reference' => $insightItem->reference,
                'difficulty' => $insightItem->difficulty,
            ]
        ];

        $user = Auth::user();

        return view('seerah', [
            'insight' => $insight,
            'insights' => $insights,
            'difficulty' => $difficulty,
            'user' => $user,
        ]);
    }

    public function quranHistory(Request $request, GeminiService $gemini)
    {
        $difficulty = $request->get('difficulty', 'Medium');

        $insights = GeneratedQuestion::where('type', 'QURAN_HISTORY')
            ->where('difficulty', $difficulty)
            ->latest()
            ->paginate(1);

        if ($request->has('generate') || $insights->isEmpty()) {
            $newInsight = $gemini->generateQuranHistoryInsight($difficulty);

            if (!$newInsight) {
                return back()->with('error', 'Failed to load Quranic history. Please try again.');
            }

            if (isset($newInsight['question'])) {
                GeneratedQuestion::create([
                    'question_id' => $newInsight['question']['id'] ?? uniqid('q-qh-'),
                    'type' => 'QURAN_HISTORY',
                    'source_info' => 'Quranic History',
                    'difficulty' => $newInsight['question']['difficulty'] ?? $difficulty,
                    'theme' => $newInsight['question']['theme'] ?? 'Quranic History',
                    'text' => $newInsight['question']['text'],
                    'options' => $newInsight['question']['options'],
                    'correct_answer_index' => $newInsight['question']['correctAnswerIndex'],
                    'explanation' => $newInsight['question']['explanation'] ?? null,
                    'reference' => $newInsight['question']['reference'] ?? null,
                    'insight_title' => $newInsight['title'],
                    'insight_content' => $newInsight['content'],
                ]);
            }

            $user = Auth::user();
            $user->quran_history_read_count += 1;
            $user->save();

            return redirect()->route('quran.history', ['difficulty' => $difficulty]);
        }

        $insightItem = $insights->first();
        $insight = [
            'title' => $insightItem->insight_title ?? 'Quranic History Reflection',
            'content' => $insightItem->insight_content ?? 'Reflection context is not available for this older question.',
            'question' => [
                'id' => $insightItem->question_id,
                'text' => $insightItem->text,
                'options' => $insightItem->options,
                'correctAnswerIndex' => $insightItem->correct_answer_index,
                'explanation' => $insightItem->explanation,
                'theme' => $insightItem->theme,
                'reference' => $insightItem->reference,
                'difficulty' => $insightItem->difficulty,
            ]
        ];

        $user = Auth::user();

        return view('quran-history', [
            'insight' => $insight,
            'insights' => $insights,
            'difficulty' => $difficulty,
            'user' => $user,
        ]);
    }

    public function submitInsightAnswer(Request $request)
    {
        $request->validate([
            'correct' => 'required|boolean',
            'questionId' => 'nullable|string',
        ]);

        $user = Auth::user();
        $user->total_questions += 1;
        if ($request->correct) {
            $user->total_score += 1;
        }
        $user->save();

        // Update generated question stats
        if ($request->questionId) {
            GeneratedQuestion::where('question_id', $request->questionId)->increment('times_answered');
            if ($request->correct) {
                GeneratedQuestion::where('question_id', $request->questionId)->increment('times_correct');
            }
        }

        return response()->json(['success' => true]);
    }
}
