<?php

namespace App\Services;

use App\Models\GeneratedQuestion;

class QuizScoringService
{
    public static function pointsForDifficulty(?string $difficulty): int
    {
        return match (strtolower((string) $difficulty)) {
            'easy' => 5,
            'hard' => 15,
            default => 10,
        };
    }

    /**
     * Resolve a submitted question payload to a DB row.
     * Accepts string question_id or numeric primary key (legacy mobile clients).
     *
     * @param  array<string, mixed>  $question
     */
    public static function resolveQuestion(array $question): ?GeneratedQuestion
    {
        $questionId = $question['id'] ?? $question['question_id'] ?? null;
        if ($questionId === null || $questionId === '') {
            return null;
        }

        $byKey = GeneratedQuestion::where('question_id', (string) $questionId)->first();
        if ($byKey) {
            return $byKey;
        }

        if (is_numeric($questionId)) {
            return GeneratedQuestion::find((int) $questionId);
        }

        return null;
    }

    /**
     * Whether the selected answer matches the DB key.
     * Supports shuffled client options by matching selected option text to DB options.
     *
     * @param  array<string, mixed>  $question
     */
    public static function isCorrect(GeneratedQuestion $dbQuestion, array $question, mixed $selected): bool
    {
        if ($selected === null || $selected === '' || (int) $selected < 0) {
            return false;
        }

        $correctIndex = (int) $dbQuestion->correct_answer_index;
        $dbOptions = is_array($dbQuestion->options) ? $dbQuestion->options : [];

        // Prefer text match when client sent (possibly shuffled) options.
        $clientOptions = $question['options'] ?? null;
        if (is_array($clientOptions) && array_key_exists((int) $selected, $clientOptions)) {
            $selectedText = (string) $clientOptions[(int) $selected];
            $dbMatch = array_search($selectedText, $dbOptions, true);
            if ($dbMatch !== false) {
                return (int) $dbMatch === $correctIndex;
            }
        }

        return (int) $selected === $correctIndex;
    }

    /**
     * Recompute quiz score from submitted answers using DB answer keys only.
     * Questions without a matching generated_questions row are ignored (score 0).
     *
     * @param  array<int, mixed>  $questions
     * @param  array<int, mixed>  $answers
     * @return array{score:int,total_questions:int,scored_questions:int,rejected_questions:int}
     */
    public static function recompute(array $questions, array $answers, ?string $fallbackDifficulty = 'Medium'): array
    {
        $score = 0;
        $scored = 0;
        $rejected = 0;

        foreach ($questions as $i => $question) {
            if (!is_array($question)) {
                continue;
            }

            $dbQuestion = self::resolveQuestion($question);
            if (!$dbQuestion) {
                $rejected++;
                continue;
            }

            $scored++;
            $difficulty = $dbQuestion->difficulty ?: ($fallbackDifficulty ?? 'Medium');
            $points = self::pointsForDifficulty($difficulty);
            $selected = $answers[$i] ?? null;

            if (self::isCorrect($dbQuestion, $question, $selected)) {
                $score += $points;
            }
        }

        return [
            'score' => $score,
            'total_questions' => $scored,
            'scored_questions' => $scored,
            'rejected_questions' => $rejected,
        ];
    }
}
