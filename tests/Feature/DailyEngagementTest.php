<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDailyEngagement;
use App\Services\DailyEngagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyEngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_activity_is_idempotent_for_same_day(): void
    {
        $user = User::factory()->create([
            'streak_count' => 0,
            'last_streak_date' => null,
        ]);

        $service = app(DailyEngagementService::class);

        $first = $service->record($user, UserDailyEngagement::ACTIVITY_QUIZ);
        $second = $service->record($user->fresh(), UserDailyEngagement::ACTIVITY_QUIZ);

        $this->assertEquals(1, $first['completed_count']);
        $this->assertEquals(1, $second['completed_count']);
        $this->assertTrue($second['activities']['quiz']['done']);
        $this->assertEquals(25, $second['progress_percent']);

        $this->assertDatabaseCount('user_daily_engagements', 1);
    }

    public function test_first_activity_of_the_day_checks_in_streak(): void
    {
        $user = User::factory()->create([
            'streak_count' => 0,
            'last_streak_date' => null,
        ]);

        $status = app(DailyEngagementService::class)->record($user, UserDailyEngagement::ACTIVITY_READ);

        $this->assertTrue($status['streak']['is_active_today']);
        $this->assertEquals(1, $status['streak']['current_streak']);
        $this->assertTrue($status['activities']['read']['done']);
    }

    public function test_progress_math_across_multiple_activities(): void
    {
        $user = User::factory()->create();
        $service = app(DailyEngagementService::class);

        $service->record($user, 'quiz');
        $service->record($user->fresh(), 'dua');
        $status = $service->todayStatus($user->fresh());

        $this->assertEquals(2, $status['completed_count']);
        $this->assertEquals(4, $status['total']);
        $this->assertEquals(50, $status['progress_percent']);
        $this->assertTrue($status['activities']['quiz']['done']);
        $this->assertTrue($status['activities']['dua']['done']);
        $this->assertFalse($status['activities']['read']['done']);
        $this->assertFalse($status['activities']['insight']['done']);
    }

    public function test_engagement_today_endpoint_returns_payload(): void
    {
        $user = User::factory()->create();
        app(DailyEngagementService::class)->record($user, UserDailyEngagement::ACTIVITY_INSIGHT);

        $response = $this->actingAs($user)->getJson(route('engagement.today'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.completed_count', 1)
            ->assertJsonPath('data.total', 4)
            ->assertJsonPath('data.progress_percent', 25)
            ->assertJsonPath('data.activities.insight.done', true);
    }

    public function test_mark_dua_endpoint_records_engagement(): void
    {
        $user = User::factory()->create([
            'streak_count' => 0,
            'last_streak_date' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('engagement.dua'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.activities.dua.done', true)
            ->assertJsonPath('data.streak.is_active_today', true);
    }

    public function test_marking_surah_as_read_records_read_engagement(): void
    {
        $user = User::factory()->create([
            'streak_count' => 0,
            'last_streak_date' => null,
            'para_mastery' => [],
        ]);

        $response = $this->actingAs($user)->postJson(route('reading.toggle-surah'), [
            'surah' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_read', true)
            ->assertJsonPath('engagement.activities.read.done', true);

        $this->assertTrue(
            \App\Models\UserDailyEngagement::where('user_id', $user->id)
                ->whereDate('engagement_date', now()->toDateString())
                ->exists()
        );
    }

    public function test_marking_insight_records_insight_engagement(): void
    {
        $user = User::factory()->create([
            'para_mastery' => [],
            'seerah_read_count' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('reading.mark-insight'), [
            'type' => 'SEERAH',
            'insight_id' => 'test-insight-1',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('engagement.activities.insight.done', true);
    }

    public function test_dashboard_includes_engagement_checklist(): void
    {
        $user = User::factory()->create();
        app(DailyEngagementService::class)->record($user, UserDailyEngagement::ACTIVITY_QUIZ);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Today', false);
        $response->assertSee('Devotion', false);
        $response->assertSee('Complete a quiz');
        $response->assertSee(route('quiz.learning'), false);
        $response->assertSee(route('lens.index'), false);
    }

    public function test_activity_links_leave_the_dashboard(): void
    {
        $user = User::factory()->create();
        $status = app(DailyEngagementService::class)->todayStatus($user);

        $this->assertSame(route('quiz.learning'), $status['activities']['quiz']['href']);
        $this->assertSame(route('lens.index'), $status['activities']['read']['href']);
        $this->assertSame(route('seerah'), $status['activities']['insight']['href']);
        $this->assertSame(route('daily.dua'), $status['activities']['dua']['href']);
        $this->assertStringNotContainsString('/dashboard', $status['activities']['read']['href']);
        $this->assertStringNotContainsString('/dashboard', $status['activities']['quiz']['href']);
    }
}
