<?php

namespace App\Http\Controllers;

use App\Models\GeneratedQuestion;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InsightController extends Controller
{
    protected function ensureCuratedSeerahInsights(GeminiService $gemini): void
    {
        if (GeneratedQuestion::where('type', 'SEERAH_INSIGHT')->count() > 0) {
            return;
        }

        foreach ($gemini->getCuratedSeerahInsights() as $item) {
            if (!isset($item['question'])) {
                continue;
            }
            GeneratedQuestion::updateOrCreate(
                ['question_id' => $item['question']['id'] ?? uniqid('q-seerah-')],
                [
                    'type' => 'SEERAH_INSIGHT',
                    'source_info' => 'Seerah Insight',
                    'difficulty' => $item['question']['difficulty'] ?? ($item['difficulty'] ?? 'Medium'),
                    'theme' => $item['question']['theme'] ?? 'Seerah',
                    'text' => $item['question']['text'],
                    'options' => $item['question']['options'],
                    'correct_answer_index' => $item['question']['correctAnswerIndex'],
                    'explanation' => $item['question']['explanation'] ?? null,
                    'reference' => $item['question']['reference'] ?? null,
                    'insight_title' => $item['title'],
                    'insight_content' => $item['content'],
                ]
            );
        }
    }

    protected function ensureCuratedQuranHistoryInsights(GeminiService $gemini): void
    {
        if (GeneratedQuestion::where('type', 'QURAN_HISTORY')->count() > 0) {
            return;
        }

        foreach ($gemini->getCuratedQuranHistoryInsights() as $item) {
            if (!isset($item['question'])) {
                continue;
            }
            GeneratedQuestion::updateOrCreate(
                ['question_id' => $item['question']['id'] ?? uniqid('q-qh-')],
                [
                    'type' => 'QURAN_HISTORY',
                    'source_info' => 'Quranic History',
                    'difficulty' => $item['question']['difficulty'] ?? ($item['difficulty'] ?? 'Medium'),
                    'theme' => $item['question']['theme'] ?? 'Quranic History',
                    'text' => $item['question']['text'],
                    'options' => $item['question']['options'],
                    'correct_answer_index' => $item['question']['correctAnswerIndex'],
                    'explanation' => $item['question']['explanation'] ?? null,
                    'reference' => $item['question']['reference'] ?? null,
                    'insight_title' => $item['title'],
                    'insight_content' => $item['content'],
                ]
            );
        }
    }

    public function seerah(Request $request, GeminiService $gemini)
    {
        $difficulty = $request->get('difficulty', 'Medium');
        if (!in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
            $difficulty = 'Medium';
        }

        $this->ensureCuratedSeerahInsights($gemini);

        $insights = GeneratedQuestion::where('type', 'SEERAH_INSIGHT')
            ->where('difficulty', $difficulty)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(1);

        if ($request->has('generate') || $insights->isEmpty()) {
            $newInsight = $gemini->generateSeerahInsight($difficulty);

            if (!$newInsight) {
                return back()->with('error', 'Failed to load Seerah insight. Please try again.');
            }

            if (isset($newInsight['question'])) {
                $record = GeneratedQuestion::updateOrCreate(
                    ['question_id' => $newInsight['question']['id'] ?? uniqid('q-seerah-')],
                    [
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
                    ]
                );
                $record->touch();
            }

            $user = Auth::user();
            if ($user) {
                $user->seerah_read_count += 1;
                $user->save();
            }

            if ($request->has('generate')) {
                return redirect()->route('seerah', ['difficulty' => $difficulty]);
            }

            $insights = GeneratedQuestion::where('type', 'SEERAH_INSIGHT')
                ->where('difficulty', $difficulty)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->paginate(1);
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
        $isRead = ($user && $insightItem) ? $user->isInsightRead('SEERAH', $insightItem->question_id ?? null) : false;

        return view('seerah', [
            'insight' => $insight,
            'insights' => $insights,
            'difficulty' => $difficulty,
            'user' => $user,
            'isRead' => $isRead,
        ]);
    }

    public function quranHistory(Request $request, GeminiService $gemini)
    {
        $difficulty = $request->get('difficulty', 'Medium');
        if (!in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
            $difficulty = 'Medium';
        }

        $this->ensureCuratedQuranHistoryInsights($gemini);

        $insights = GeneratedQuestion::where('type', 'QURAN_HISTORY')
            ->where('difficulty', $difficulty)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(1);

        if ($request->has('generate') || $insights->isEmpty()) {
            $newInsight = $gemini->generateQuranHistoryInsight($difficulty);

            if (!$newInsight) {
                return back()->with('error', 'Failed to load Quranic history. Please try again.');
            }

            if (isset($newInsight['question'])) {
                $record = GeneratedQuestion::updateOrCreate(
                    ['question_id' => $newInsight['question']['id'] ?? uniqid('q-qh-')],
                    [
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
                    ]
                );
                $record->touch();
            }

            $user = Auth::user();
            if ($user) {
                $user->quran_history_read_count += 1;
                $user->save();
            }

            if ($request->has('generate')) {
                return redirect()->route('quran.history', ['difficulty' => $difficulty]);
            }

            $insights = GeneratedQuestion::where('type', 'QURAN_HISTORY')
                ->where('difficulty', $difficulty)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->paginate(1);
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
        $isRead = ($user && $insightItem) ? $user->isInsightRead('QURAN_HISTORY', $insightItem->question_id ?? null) : false;

        return view('quran-history', [
            'insight' => $insight,
            'insights' => $insights,
            'difficulty' => $difficulty,
            'user' => $user,
            'isRead' => $isRead,
        ]);
    }

    public function submitInsightAnswer(Request $request)
    {
        $request->validate([
            'correct' => 'required|boolean',
            'questionId' => 'nullable|string',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->total_questions += 1;
            if ($request->correct) {
                $user->total_score += 1;
            }
            $user->save();
        }

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
