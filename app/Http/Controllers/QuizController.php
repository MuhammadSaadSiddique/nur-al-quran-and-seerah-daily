<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\GeneratedQuestion;
use App\Models\Quiz;
use App\Services\GeminiService;
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
            GeneratedQuestion::updateOrCreate(
                ['question_id' => $q['id'] ?? uniqid('q-')],
                [
                    'type' => $type,
                    'source_info' => $sourceInfo,
                    'difficulty' => $q['difficulty'] ?? $difficulty,
                    'theme' => $q['theme'] ?? null,
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
            'theme' => 'nullable|string',
        ]);

        $theme = $request->theme === 'Any Theme' ? null : $request->theme;
        
        $dbQuery = GeneratedQuestion::where('type', 'PARA')
                    ->where('difficulty', $request->difficulty)
                    ->where('source_info', "Para {$request->para}");
        if ($theme) {
            $dbQuery->where('theme', $theme);
        }
        
        $dbQuestions = $dbQuery->inRandomOrder()->limit(10)->get();
        $questions = [];

        if ($dbQuestions->isNotEmpty()) {
            foreach ($dbQuestions as $q) {
                $questions[] = [
                    'id' => $q->question_id,
                    'dbId' => $q->id,
                    'text' => $q->text,
                    'options' => $q->options,
                    'correctAnswerIndex' => $q->correct_answer_index,
                    'explanation' => $q->explanation,
                    'theme' => $q->theme,
                    'difficulty' => $q->difficulty,
                    'reference' => $q->reference,
                    'source_info' => $q->source_info,
                ];
            }
        } else {
            $questions = $gemini->generateParaQuestions($request->para, $request->difficulty, $theme);
            if (!empty($questions)) {
                $this->persistQuestions($questions, 'PARA', "Para {$request->para}", $request->difficulty);
            }
        }

        if (empty($questions)) {
            return back()->with('error', 'Failed to generate quiz. Please try again.');
        }

        return view('quiz', [
            'questions' => $questions,
            'type' => 'PARA',
            'title' => "Para {$request->para}",
            'paraNumber' => $request->para,
            'difficulty' => $request->difficulty,
            'theme' => $theme ?? 'General Study',
        ]);
    }

    public function startSeerahQuiz(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'difficulty' => 'required|in:Easy,Medium,Hard',
            'theme' => 'nullable|string',
        ]);

        $theme = $request->theme === 'Any Theme' ? null : $request->theme;
        
        $dbQuery = GeneratedQuestion::where('type', 'SEERAH')
                    ->where('difficulty', $request->difficulty);
        if ($theme) {
            $dbQuery->where('theme', $theme);
        }
        
        $dbQuestions = $dbQuery->inRandomOrder()->limit(10)->get();
        $questions = [];

        if ($dbQuestions->isNotEmpty()) {
            foreach ($dbQuestions as $q) {
                $questions[] = [
                    'id' => $q->question_id,
                    'dbId' => $q->id,
                    'text' => $q->text,
                    'options' => $q->options,
                    'correctAnswerIndex' => $q->correct_answer_index,
                    'explanation' => $q->explanation,
                    'theme' => $q->theme,
                    'difficulty' => $q->difficulty,
                    'reference' => $q->reference,
                    'source_info' => $q->source_info,
                ];
            }
        } else {
            $questions = $gemini->generateSeerahQuizQuestions($request->difficulty, $theme);
            if (!empty($questions)) {
                $this->persistQuestions($questions, 'SEERAH', 'Seerah', $request->difficulty);
            }
        }

        if (empty($questions)) {
            return back()->with('error', 'Failed to generate Seerah quiz. Please try again.');
        }

        return view('quiz', [
            'questions' => $questions,
            'type' => 'SEERAH',
            'title' => 'Seerah Knowledge Journey',
            'paraNumber' => null,
            'difficulty' => $request->difficulty,
            'theme' => $theme ?? 'General Study',
        ]);
    }

    public function finishQuiz(Request $request)
    {
        $request->validate([
            'type' => 'required|in:PARA,SEERAH',
            'title' => 'required|string',
            'difficulty' => 'required|string',
            'score' => 'required|integer',
            'totalQuestions' => 'required|integer',
            'questions' => 'required',
            'userAnswers' => 'required',
            'paraNumber' => 'nullable|integer',
        ]);

        $user = Auth::user();
        $quizId = 'quiz_' . time() . '_' . substr(md5(uniqid()), 0, 9);

        // Save quiz
        Quiz::create([
            'id' => $quizId,
            'user_id' => $user->id,
            'type' => $request->type,
            'title' => $request->title,
            'score' => $request->score,
            'total_questions' => $request->totalQuestions,
            'difficulty' => $request->difficulty,
            'details' => [
                'questions' => json_decode($request->questions, true),
                'userAnswers' => json_decode($request->userAnswers, true),
            ],
        ]);

        // Update user stats
        $user->total_score += $request->score;
        $user->total_questions += $request->totalQuestions;

        if ($request->type === 'PARA' && $request->paraNumber) {
            $completed = $user->completed_paras ?? [];
            if (!in_array($request->paraNumber, $completed)) {
                $completed[] = $request->paraNumber;
                $user->completed_paras = $completed;
            }
        }

        if ($request->type === 'SEERAH') {
            $user->seerah_quiz_count += 1;
        }

        // Update difficulty stats + per-question answer tracking
        $questions = json_decode($request->questions, true);
        $answers = json_decode($request->userAnswers, true);
        $diffStats = $user->difficulty_stats ?? ['correct' => [], 'total' => []];
        foreach ($questions as $i => $q) {
            $diff = $q['difficulty'] ?? $request->difficulty;
            $diffStats['total'][$diff] = ($diffStats['total'][$diff] ?? 0) + 1;
            $isCorrect = isset($answers[$i]) && $answers[$i] == $q['correctAnswerIndex'];
            if ($isCorrect) {
                $diffStats['correct'][$diff] = ($diffStats['correct'][$diff] ?? 0) + 1;
            }

            // Update generated_questions answer stats
            if (isset($q['id'])) {
                GeneratedQuestion::where('question_id', $q['id'])->increment('times_answered');
                if ($isCorrect) {
                    GeneratedQuestion::where('question_id', $q['id'])->increment('times_correct');
                }
            }
        }
        $user->difficulty_stats = $diffStats;
        $user->save();

        return response()->json([
            'success' => true,
            'redirect' => route('stats'),
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
        ]);

        $type = $request->quiz_type === 'QURAN' ? 'PARA' : 'SEERAH';
        
        $dbQuestions = GeneratedQuestion::where('type', $type)
            ->where('difficulty', $request->difficulty)
            ->inRandomOrder()
            ->limit(20)
            ->get();

        $questions = [];
        foreach ($dbQuestions as $q) {
            $questions[] = [
                'id' => $q->question_id,
                'dbId' => $q->id,
                'text' => $q->text,
                'options' => $q->options,
                'correctAnswerIndex' => $q->correct_answer_index,
                'explanation' => $q->explanation,
                'theme' => $q->theme,
                'difficulty' => $q->difficulty,
                'reference' => $q->reference,
                'source_info' => $q->source_info,
            ];
        }

        if (empty($questions)) {
            return back()->with('error', 'Not enough questions for Grand Quiz. Complete more regular quizzes first!');
        }

        $title = $request->quiz_type === 'QURAN' ? 'Grand Quran Quiz' : 'Grand Seerah Quiz';

        return view('quiz', [
            'questions' => $questions,
            'type' => $type,
            'title' => $title,
            'paraNumber' => null,
            'difficulty' => $request->difficulty,
            'theme' => 'Grand Quiz',
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
