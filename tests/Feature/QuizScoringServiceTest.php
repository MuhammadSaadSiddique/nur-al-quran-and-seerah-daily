<?php

namespace Tests\Feature;

use App\Models\GeneratedQuestion;
use App\Services\QuizScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recomputes_score_from_answers_and_ignores_client_claim(): void
    {
        GeneratedQuestion::create([
            'question_id' => 'q-score-1',
            'type' => 'PARA',
            'source_info' => 'test',
            'text' => 'Sample?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer_index' => 1,
            'difficulty' => 'Easy',
            'explanation' => 'Because',
        ]);

        $questions = [
            [
                'id' => 'q-score-1',
                'difficulty' => 'Hard', // client lie — DB Easy wins
                'correctAnswerIndex' => 0, // client lie
            ],
        ];

        $wrong = QuizScoringService::recompute($questions, [0], 'Hard');
        $this->assertSame(0, $wrong['score']);

        $right = QuizScoringService::recompute($questions, [1], 'Hard');
        $this->assertSame(5, $right['score']); // Easy = 5 points
        $this->assertSame(1, $right['total_questions']);
    }

    public function test_rejects_unknown_question_ids_from_score(): void
    {
        $questions = [
            [
                'id' => 'fabricated-q-999',
                'difficulty' => 'Hard',
                'correctAnswerIndex' => 0,
            ],
        ];

        $result = QuizScoringService::recompute($questions, [0], 'Hard');

        $this->assertSame(0, $result['score']);
        $this->assertSame(0, $result['total_questions']);
        $this->assertSame(0, $result['scored_questions']);
        $this->assertSame(1, $result['rejected_questions']);
    }

    public function test_resolves_numeric_primary_key_and_shuffled_option_text(): void
    {
        $q = GeneratedQuestion::create([
            'question_id' => 'q-score-pk',
            'type' => 'PARA',
            'source_info' => 'test',
            'text' => 'Sample?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer_index' => 1,
            'difficulty' => 'Medium',
            'explanation' => 'Because',
        ]);

        // Mobile legacy: numeric PK + shuffled options, answer index into shuffled list.
        $questions = [
            [
                'id' => $q->id,
                'options' => ['C', 'A', 'B', 'D'], // shuffled; correct text B is index 2
            ],
        ];

        $right = QuizScoringService::recompute($questions, [2], 'Medium');
        $this->assertSame(10, $right['score']);
        $this->assertSame(1, $right['total_questions']);

        $wrong = QuizScoringService::recompute($questions, [0], 'Medium');
        $this->assertSame(0, $wrong['score']);
    }
}
