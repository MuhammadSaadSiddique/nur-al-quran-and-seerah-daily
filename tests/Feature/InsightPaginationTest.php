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
    public function it_seeds_curated_insights_if_none_exist()
    {
        $user = User::factory()->create([
            'seerah_read_count' => 0,
        ]);

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('getCuratedSeerahInsights')
                ->once()
                ->andReturn([
                    [
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
                        ],
                    ],
                ]);
            $mock->shouldNotReceive('generateSeerahInsight');
        });

        $response = $this->actingAs($user)->get(route('seerah'));

        $response->assertStatus(200);
        $response->assertViewHas('insight');

        $this->assertDatabaseHas('generated_questions', [
            'type' => 'SEERAH_INSIGHT',
            'insight_title' => 'Test Seerah Title',
            'insight_content' => 'Test Seerah Content',
            'question_id' => 'q-seerah-test-1',
        ]);

        $user->refresh();
        // Viewing the page must not farm read counts.
        $this->assertEquals(0, $user->seerah_read_count);
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
            $mock->shouldNotReceive('getCuratedSeerahInsights');
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
