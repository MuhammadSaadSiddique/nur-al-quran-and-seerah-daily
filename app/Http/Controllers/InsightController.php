<?php

namespace App\Http\Controllers;

use App\Models\GeneratedQuestion;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function dailyDua()
    {
        // Popular Quranic Duas (Verse Keys)
        $duas = [
            '2:201',  // Rabbana atina fid-dunya hasanatan...
            '3:8',    // Rabbana la tuzigh quloobana...
            '3:193',  // Rabbana innana sami'na munadiyan...
            '14:40',  // Rabbij'alni muqimas-salati...
            '14:41',  // Rabbanaghfir li waliwalidayya...
            '21:89',  // Rabbi la tatharni fardan...
            '25:74',  // Rabbana hab lana min azwajina...
            '28:24',  // Rabbi inni lima anzalta ilayya min khayrin faqir
            '40:60',  // Ud'ooni astajib lakum
            '59:10',  // Rabbanaghfir lana wali-ikhwaninalladhina sabaqoona bil-iman
        ];

        // Pick a random Dua
        $randomVerseKey = $duas[array_rand($duas)];

        $duaData = [
            'verse_key' => $randomVerseKey,
            'arabic' => 'Error loading dua.',
            'translation' => 'Please check your connection and try again.',
        ];

        try {
            // Fetch from Quran.com API (Sahih International ID: 20)
            $response = Http::timeout(10)->withoutVerifying()->get("https://api.quran.com/content/api/v4/verses/by_key/{$randomVerseKey}", [
                'language' => 'en',
                'translations' => '20',
                'fields' => 'text_uthmani',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['verse'])) {
                    $duaData['arabic'] = $data['verse']['text_uthmani'] ?? $duaData['arabic'];
                    if (isset($data['verse']['translations'][0]['text'])) {
                        // Strip HTML tags if any (Quran.com sometimes includes basic tags in translations)
                        $duaData['translation'] = strip_tags($data['verse']['translations'][0]['text']);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch daily dua from Quran.com: " . $e->getMessage());
        }

        // Optional: track stats for daily dua
        $user = Auth::user();
        if ($user) {
            // We can add logic to update a specific counter if added in the future.
            // For now, we just pass the user to the view.
        }

        return view('daily-dua', [
            'dua' => $duaData,
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
