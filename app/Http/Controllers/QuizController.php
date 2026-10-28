<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\GeneratedQuestion;
use App\Models\Quiz;
use App\Models\Theme;
use App\Services\GeminiService;
use App\Services\QuizCompletionService;
use App\Services\QuizScoringService;
use App\Services\QuranApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    /**
     * Save an array of questions to the generated_questions table.
     */
    protected function persistQuestions(array $questions, string $type, string $sourceInfo, string $difficulty): void
    {
        foreach ($questions as $q) {
            $theme = Theme::where('name', $q['theme'] ?? '')->where('type', $type)->first();

            GeneratedQuestion::updateOrCreate(
                ['question_id' => $q['id'] ?? uniqid('q-')],
                [
                    'type' => $type,
                    'source_info' => $sourceInfo,
                    'difficulty' => $q['difficulty'] ?? $difficulty,
                    'theme' => $q['theme'] ?? null,
                    'theme_id' => $theme?->id,
                    'text' => $q['text'],
                    'options' => $q['options'],
                    'correct_answer_index' => $q['correctAnswerIndex'],
                    'explanation' => $q['explanation'] ?? null,
                    'reference' => $q['reference'] ?? null,
                ]
            );
        }
    }

    public function startParaQuiz(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'para' => 'required|integer|min:1|max:30',
            'difficulty' => 'required|in:Easy,Medium,Hard',
            'quantity' => 'required|integer|in:20,50,100',
        ]);

        $quantity = $request->quantity;

        $dbQuery = GeneratedQuestion::where('type', 'PARA')
            ->where('difficulty', $request->difficulty)
            ->where('source_info', 'like', "%Para {$request->para}:%")
            ->where('reference', 'like', "%Para {$request->para}:%");

        $dbQuestions = $dbQuery->inRandomOrder()->limit($quantity)->get();
        $questions = [];

        if ($dbQuestions->count() < $quantity) {
            $needed = $quantity - $dbQuestions->count();
            $generated = $gemini->generateParaQuestions($request->para, $request->difficulty, $needed + 5);

            if (!empty($generated)) {
                $this->persistQuestions($generated, 'PARA', "Para {$request->para}", $request->difficulty);
            }

            // Refresh from DB to get the mix
            $dbQuestions = GeneratedQuestion::where('type', 'PARA')
                ->where('difficulty', $request->difficulty)
                ->where(function ($q) use ($request) {
                    $q->where('source_info', 'like', "%Para {$request->para}:%")
                        ->orWhere('source_info', "Para {$request->para}")
                        ->orWhere('reference', 'like', "%Para {$request->para}:%");
                })
                ->inRandomOrder()
                ->limit($quantity)
                ->get();

            // If Gemini was unavailable and this difficulty has too few questions, supplement from other difficulties of the same Para
            if ($dbQuestions->count() < $quantity) {
                $existingIds = $dbQuestions->pluck('id')->all();
                $supplement = GeneratedQuestion::where('type', 'PARA')
                    ->whereNotIn('id', $existingIds)
                    ->where(function ($q) use ($request) {
                        $q->where('source_info', 'like', "%Para {$request->para}:%")
                            ->orWhere('source_info', "Para {$request->para}")
                            ->orWhere('reference', 'like', "%Para {$request->para}:%");
                    })
                    ->inRandomOrder()
                    ->limit($quantity - $dbQuestions->count())
                    ->get();
                $dbQuestions = $dbQuestions->concat($supplement);
            }

            // Final fallback: supplement remaining slots from any PARA questions if still fewer than requested quantity
            if ($dbQuestions->count() < $quantity) {
                $existingIds = $dbQuestions->pluck('id')->all();
                $generalSupplement = GeneratedQuestion::where('type', 'PARA')
                    ->whereNotIn('id', $existingIds)
                    ->where('difficulty', $request->difficulty)
                    ->inRandomOrder()
                    ->limit($quantity - $dbQuestions->count())
                    ->get();
                $dbQuestions = $dbQuestions->concat($generalSupplement);
            }
        }

        $questions = QuizCompletionService::clientQuestions($dbQuestions);

        if (empty($questions)) {
            return back()->with('error', 'Failed to generate quiz. Please try again.');
        }

        return $this->quizView($questions, 'PARA', "Para {$request->para}", $request->difficulty, 'General Study', $request->para);
    }

    public function startSeerahQuiz(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'difficulty' => 'required|in:Easy,Medium,Hard',
            'quantity' => 'required|integer|in:20,50,100',
        ]);

        $quantity = $request->quantity;

        $dbQuery = GeneratedQuestion::where('type', 'SEERAH')
            ->where('difficulty', $request->difficulty);

        $dbQuestions = $dbQuery->inRandomOrder()->limit($quantity)->get();
        $questions = [];

        if ($dbQuestions->count() < $quantity) {
            $needed = $quantity - $dbQuestions->count();
            $generated = $gemini->generateSeerahQuizQuestions($request->difficulty, $needed + 5);

            if (!empty($generated)) {
                $this->persistQuestions($generated, 'SEERAH', 'Seerah', $request->difficulty);
            }

            $dbQuestions = GeneratedQuestion::where('type', 'SEERAH')
                ->where('difficulty', $request->difficulty)
                ->inRandomOrder()->limit($quantity)->get();
        }

        $questions = QuizCompletionService::clientQuestions($dbQuestions);

        if (empty($questions)) {
            return back()->with('error', 'Failed to generate Seerah quiz. Please try again.');
        }

        return $this->quizView($questions, 'SEERAH', 'Seerah Knowledge Journey', $request->difficulty, 'General Study');
    }

    public function startThemeQuiz(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'theme_id' => 'required|exists:themes,id',
            'difficulty' => 'required|in:Easy,Medium,Hard',
            'quantity' => 'required|integer|in:20,50,100',
        ]);

        $theme = Theme::findOrFail($request->theme_id);
        if ($theme->questions()->count() < 5) {
            return back()->with('error', 'This theme is not active yet.');
        }
        $quantity = $request->quantity;

        $dbQuery = GeneratedQuestion::where('theme_id', $theme->id)
            ->where('difficulty', $request->difficulty);

        $dbQuestions = $dbQuery->inRandomOrder()->limit($quantity)->get();
        $questions = [];

        if ($dbQuestions->count() < $quantity) {
            $needed = $quantity - $dbQuestions->count();
            // Full scope generation
            $generated = $gemini->generateThemeQuestions($theme->type, $theme->name, $request->difficulty, $needed + 5);

            if (!empty($generated)) {
                $this->persistQuestions($generated, $theme->type, ($theme->type === 'PARA' ? 'Full Quran theme' : 'Seerah theme'), $request->difficulty);
            }

            $dbQuestions = GeneratedQuestion::where('theme_id', $theme->id)
                ->where('difficulty', $request->difficulty)
                ->inRandomOrder()->limit($quantity)->get();
        }

        $questions = QuizCompletionService::clientQuestions($dbQuestions);

        if (empty($questions)) {
            return back()->with('error', 'Failed to load theme quiz. Please try again.');
        }

        return $this->quizView($questions, $theme->type, $theme->name, $request->difficulty, $theme->name);
    }

    public function finishQuiz(Request $request)
    {
        $request->validate([
            'type' => 'required|in:PARA,SEERAH',
            'title' => 'required|string',
            'difficulty' => 'required|string',
            'score' => 'nullable|integer',
            'totalQuestions' => 'nullable|integer',
            'questions' => 'required',
            'userAnswers' => 'required',
            'paraNumber' => 'nullable|integer',
            'attemptToken' => 'required|string|min:20|max:80',
        ]);

        $user = Auth::user();
        $quizId = 'quiz_' . time() . '_' . substr(md5(uniqid()), 0, 9);

        $questions = json_decode($request->questions, true) ?: [];
        $answers = json_decode($request->userAnswers, true) ?: [];
        $computed = QuizScoringService::recompute($questions, $answers, $request->difficulty);
        $score = $computed['score'];
        $totalQuestions = $computed['total_questions'];

        if ($totalQuestions <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'No valid quiz questions found to score.',
            ], 422);
        }

        if (!QuizCompletionService::consumeAttempt((int) $user->id, $request->attemptToken, $questions)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or expired quiz attempt. Please start a new quiz.',
            ], 422);
        }

        if (!QuizCompletionService::claimAttempt((int) $user->id, $questions)) {
            return response()->json([
                'success' => false,
                'error' => 'This quiz set was already submitted recently. Try a new quiz.',
            ], 429);
        }

        // Save quiz
        Quiz::create([
            'id' => $quizId,
            'user_id' => $user->id,
            'type' => $request->type,
            'title' => $request->title,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'difficulty' => $request->difficulty,
            'details' => [
                'questions' => $questions,
                'userAnswers' => $answers,
            ],
        ]);

        // Update user stats from server-computed score only
        $user->total_score += $score;
        $user->total_questions += $totalQuestions;
        $user->save();

        $engagement = QuizCompletionService::applyProgress(
            $user->fresh(),
            $questions,
            $answers,
            $request->type,
            $request->paraNumber ? (int) $request->paraNumber : null,
            $request->difficulty
        );

        // Push reading session to Quran API if linked
        if (!empty($user->quran_access_token)) {
            $quranApi = app(QuranApiService::class);
            $quranApi->postUserData('/reading_sessions', [
                'type' => $request->type,
                'title' => $request->title,
                'score' => $score,
                'total_questions' => $totalQuestions,
            ], $user);
        }

        return response()->json([
            'success' => true,
            'redirect' => route('stats'),
            'engagement' => $engagement,
        ]);
    }

    public function checkAnswer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|string',
            'selected_index' => 'required|integer|min:0',
            'options' => 'nullable|array',
        ]);

        $payload = [
            'id' => $request->question_id,
            'options' => $request->options,
        ];
        $dbQuestion = QuizScoringService::resolveQuestion($payload);
        if (!$dbQuestion) {
            return response()->json([
                'success' => false,
                'error' => 'Question not found.',
            ], 404);
        }

        $selected = (int) $request->selected_index;
        $isCorrect = QuizScoringService::isCorrect($dbQuestion, $payload, $selected);

        return response()->json([
            'success' => true,
            'correct' => $isCorrect,
            'correctAnswerIndex' => (int) $dbQuestion->correct_answer_index,
            'explanation' => (string) ($dbQuestion->explanation ?? ''),
            'question_id' => (string) $dbQuestion->question_id,
        ]);
    }

    public function history()
    {
        $quizzes = Auth::user()->quizzes()->get();
        return view('history', compact('quizzes'));
    }

    public function startGrandQuiz(Request $request)
    {
        $request->validate([
            'quiz_type' => 'required|in:QURAN,SEERAH',
            'difficulty' => 'required|in:Easy,Medium,Hard',
            'quantity' => 'required|integer|in:20,50,100',
        ]);

        $type = $request->quiz_type === 'QURAN' ? 'PARA' : 'SEERAH';
        $quantity = $request->quantity;

        $dbQuestions = GeneratedQuestion::where('type', $type)
            ->where('difficulty', $request->difficulty)
            ->inRandomOrder()
            ->limit($quantity)
            ->get();

        $questions = QuizCompletionService::clientQuestions($dbQuestions);

        if (empty($questions)) {
            return back()->with('error', 'Not enough questions for Grand Quiz. Complete more regular quizzes first!');
        }

        $title = $request->quiz_type === 'QURAN' ? 'Grand Quran Quiz' : 'Grand Seerah Quiz';

        return $this->quizView($questions, $type, $title, $request->difficulty, 'Grand Quiz');
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    protected function quizView(
        array $questions,
        string $type,
        string $title,
        string $difficulty,
        string $theme,
        $paraNumber = null
    ) {
        $attemptToken = QuizCompletionService::issueAttempt((int) Auth::id(), $questions);

        return view('quiz', [
            'questions' => $questions,
            'type' => $type,
            'title' => $title,
            'paraNumber' => $paraNumber,
            'difficulty' => $difficulty,
            'theme' => $theme,
            'attemptToken' => $attemptToken,
        ]);
    }

    public function submitFeedback(Request $request)
    {
        $request->validate([
            'question_db_id' => 'required|integer|exists:generated_questions,id',
            'question_text' => 'required|string',
            'type' => 'required|in:error,suggestion,praise',
            'message' => 'required|string|max:1000',
        ]);

        Feedback::create([
            'user_id' => Auth::id(),
            'question_id' => $request->question_db_id,
            'question_text' => $request->question_text,
            'type' => $request->type,
            'message' => $request->message,
        ]);

        return response()->json(['success' => true, 'message' => 'Feedback submitted. Thank you!']);
    }
}
