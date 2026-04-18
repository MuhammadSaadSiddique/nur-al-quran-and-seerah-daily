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
        $insight = $gemini->generateSeerahInsight($difficulty);

        if (!$insight) {
            return back()->with('error', 'Failed to load Seerah insight. Please try again.');
        }

        // Save the insight question to DB
        if (isset($insight['question'])) {
            GeneratedQuestion::updateOrCreate(
                ['question_id' => $insight['question']['id'] ?? uniqid('q-seerah-')],
                [
                    'type' => 'SEERAH_INSIGHT',
                    'source_info' => 'Seerah Insight',
                    'difficulty' => $insight['question']['difficulty'] ?? $difficulty,
                    'theme' => $insight['question']['theme'] ?? null,
                    'text' => $insight['question']['text'],
                    'options' => $insight['question']['options'],
                    'correct_answer_index' => $insight['question']['correctAnswerIndex'],
                    'explanation' => $insight['question']['explanation'] ?? null,
                ]
            );
        }

        $user = Auth::user();
        $user->seerah_read_count += 1;
        $user->save();

        return view('seerah', [
            'insight' => $insight,
            'difficulty' => $difficulty,
            'user' => $user,
        ]);
    }

    public function quranHistory(Request $request, GeminiService $gemini)
    {
        $difficulty = $request->get('difficulty', 'Medium');
        $insight = $gemini->generateQuranHistoryInsight($difficulty);

        if (!$insight) {
            return back()->with('error', 'Failed to load Quranic history. Please try again.');
        }

        // Save the insight question to DB
        if (isset($insight['question'])) {
            GeneratedQuestion::updateOrCreate(
                ['question_id' => $insight['question']['id'] ?? uniqid('q-qh-')],
                [
                    'type' => 'QURAN_HISTORY',
                    'source_info' => 'Quranic History',
                    'difficulty' => $insight['question']['difficulty'] ?? $difficulty,
                    'theme' => $insight['question']['theme'] ?? 'Quranic History',
                    'text' => $insight['question']['text'],
                    'options' => $insight['question']['options'],
                    'correct_answer_index' => $insight['question']['correctAnswerIndex'],
                    'explanation' => $insight['question']['explanation'] ?? null,
                ]
            );
        }

        $user = Auth::user();
        $user->quran_history_read_count += 1;
        $user->save();

        return view('quran-history', [
            'insight' => $insight,
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
