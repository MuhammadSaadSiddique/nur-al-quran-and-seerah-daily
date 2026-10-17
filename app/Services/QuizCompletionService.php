<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDailyEngagement;
use Illuminate\Support\Facades\Cache;

class QuizCompletionService
{
    /**
     * Soft anti-replay: reject the same user submitting the same question-id set within 24h.
     *
     * @param  array<int, mixed>  $questions
     */
    public static function claimAttempt(int $userId, array $questions): bool
    {
        $ids = [];
        foreach ($questions as $question) {
            if (!is_array($question)) {
                continue;
            }
            $id = $question['id'] ?? $question['question_id'] ?? null;
            if ($id === null || $id === '') {
                continue;
            }
            $ids[] = (string) $id;
        }

        $ids = array_values(array_unique($ids));
        sort($ids);

        if ($ids === []) {
            return false;
        }

        $hash = hash('sha256', $userId.'|'.implode(',', $ids));
        $key = 'quiz_attempt:'.$hash;

        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, 1, now()->addDay());

        return true;
    }

    /**
     * Shared post-score progress updates used by web finishQuiz and mobile submit_quiz.
     *
     * @param  array<int, mixed>  $questions
     * @param  array<int, mixed>  $answers
     */
    public static function applyProgress(
        User $user,
        array $questions,
        array $answers,
        string $type,
        ?int $paraNumber,
        string $fallbackDifficulty
    ): array {
        $typeUpper = strtoupper($type);
        if ($typeUpper === 'PARA' && $paraNumber) {
            $completed = $user->completed_paras ?? [];
            if (!in_array($paraNumber, $completed, false)) {
                $completed[] = $paraNumber;
                $user->completed_paras = $completed;
            }
        }

        if ($typeUpper === 'SEERAH') {
            $user->seerah_quiz_count = (int) ($user->seerah_quiz_count ?? 0) + 1;
        }

        $diffStats = $user->difficulty_stats ?? ['correct' => [], 'total' => []];
        foreach ($questions as $i => $q) {
            if (!is_array($q)) {
                continue;
            }
            $dbQ = QuizScoringService::resolveQuestion($q);
            if (!$dbQ) {
                continue;
            }
            $diff = $dbQ->difficulty ?: ($q['difficulty'] ?? $fallbackDifficulty);
            $diffStats['total'][$diff] = ($diffStats['total'][$diff] ?? 0) + 1;
            $isCorrect = QuizScoringService::isCorrect($dbQ, $q, $answers[$i] ?? null);
            if ($isCorrect) {
                $diffStats['correct'][$diff] = ($diffStats['correct'][$diff] ?? 0) + 1;
            }

            $dbQ->increment('times_answered');
            if ($isCorrect) {
                $dbQ->increment('times_correct');
            }
        }
        $user->difficulty_stats = $diffStats;
        $user->save();

        return app(DailyEngagementService::class)
            ->record($user, UserDailyEngagement::ACTIVITY_QUIZ);
    }

    /**
     * Client-safe quiz question payload (no answer key / explanation).
     *
     * @param  iterable<\App\Models\GeneratedQuestion>  $dbQuestions
     * @return array<int, array<string, mixed>>
     */
    public static function clientQuestions(iterable $dbQuestions): array
    {
        $questions = [];
        foreach ($dbQuestions as $q) {
            $questions[] = [
                'id' => $q->question_id,
                'dbId' => $q->id,
                'text' => $q->text,
                'options' => $q->options,
                'correctAnswerIndex' => -1,
                'explanation' => null,
                'theme' => $q->theme,
                'difficulty' => $q->difficulty,
                'reference' => $q->reference,
                'source_info' => $q->source_info,
            ];
        }

        return $questions;
    }
}
