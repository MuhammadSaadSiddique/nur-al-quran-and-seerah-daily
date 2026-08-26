<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GeneratedQuestion;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightPaginationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_insight_if_none_exists()
    {
        $user = User::factory()->create();

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('generateSeerahInsight')
                ->once()
                ->with('Medium')
                ->andReturn([
                    'title' => 'Test Seerah Title',
                    'content' => 'Test Seerah Content',
                    'question' => [
                        'id' => 'q-seerah-test-1',
                        'text' => 'Who was the first prophet?',
                        'options' => ['Adam', 'Nuh', 'Ibrahim', 'Musa'],
                        'correctAnswerIndex' => 0,
                        'explanation' => 'Adam is the first prophet.',
                        'theme' => 'Early Life',
                        'reference' => 'History books',
                        'difficulty' => 'Medium',
                    ]
                ]);
        });

        $response = $this->actingAs($user)->get(route('seerah'));

        // Since it generates, it will redirect back to the page without ?generate
        $response->assertStatus(302);
        $response->assertRedirect(route('seerah', ['difficulty' => 'Medium']));

        $this->assertDatabaseHas('generated_questions', [
            'type' => 'SEERAH_INSIGHT',
            'insight_title' => 'Test Seerah Title',
            'insight_content' => 'Test Seerah Content',
            'question_id' => 'q-seerah-test-1',
        ]);

        $user->refresh();
        $this->assertEquals(1, $user->seerah_read_count);
    }

    /** @test */
    public function it_paginates_existing_insights_without_generating_new_one()
    {
        $user = User::factory()->create();

        GeneratedQuestion::create([
            'question_id' => 'q-seerah-saved-1',
            'type' => 'SEERAH_INSIGHT',
            'source_info' => 'Seerah Insight',
            'difficulty' => 'Medium',
            'insight_title' => 'Saved Seerah Title',
            'insight_content' => 'Saved Seerah Content',
            'text' => 'Saved question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer_index' => 1,
            'explanation' => 'Explanation',
        ]);

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldNotReceive('generateSeerahInsight');
        });

        $response = $this->actingAs($user)->get(route('seerah'));

        $response->assertStatus(200);
        $response->assertViewHas('insight');
        $response->assertViewHas('insights');

        $viewData = $response->viewData('insight');
        $this->assertEquals('Saved Seerah Title', $viewData['title']);
        $this->assertEquals('Saved Seerah Content', $viewData['content']);
    }
}
