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
        $this->assertEquals(20, $second['progress_percent']);

        $this->assertDatabaseCount('user_daily_engagements', 1);
    }

    public function test_first_activity_of_the_day_checks_in_streak(): void
    {
        $user = User::factory()->create([
            'streak_count' => 0,
            'last_streak_date' => null,
        ]);

        $status = app(DailyEngagementService::class)->record($user, UserDailyEngagement::ACTIVITY_CONTRIBUTE);

        $this->assertTrue($status['streak']['is_active_today']);
        $this->assertEquals(1, $status['streak']['current_streak']);
        $this->assertTrue($status['activities']['contribute']['done']);
    }

    public function test_progress_math_across_multiple_activities(): void
    {
        $user = User::factory()->create();
        $service = app(DailyEngagementService::class);

        $service->record($user, 'quiz');
        $service->record($user->fresh(), 'dua');
        $status = $service->todayStatus($user->fresh());

        $this->assertEquals(2, $status['completed_count']);
        $this->assertEquals(5, $status['total']);
        $this->assertEquals(40, $status['progress_percent']);
        $this->assertTrue($status['activities']['quiz']['done']);
        $this->assertTrue($status['activities']['dua']['done']);
        $this->assertFalse($status['activities']['contribute']['done']);
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
            ->assertJsonPath('data.total', 5)
            ->assertJsonPath('data.progress_percent', 20)
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

    public function test_submitting_research_contribution_records_contribute_engagement(): void
    {
        $user = User::factory()->create([
            'streak_count' => 0,
            'last_streak_date' => null,
        ]);

        $this->actingAs($user)->post(route('research.contributions.store'), [
            'title' => 'Water as the origin of life',
            'surah_number' => 21,
            'verse_number' => 30,
            'category' => 'science',
            'description' => 'Modern biology on aqueous origins.',
        ]);

        $status = app(DailyEngagementService::class)->todayStatus($user->fresh());

        $this->assertTrue($status['activities']['contribute']['done']);
        $this->assertTrue($status['streak']['is_active_today']);
    }

    public function test_marking_surah_as_read_no_longer_counts_toward_devotion(): void
    {
        $user = User::factory()->create([
            'para_mastery' => [],
        ]);

        $response = $this->actingAs($user)->postJson(route('reading.toggle-surah'), [
            'surah' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('is_read', true)
            ->assertJsonPath('engagement.completed_count', 0);
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
        $response->assertSee('Open the prayer page');
        $response->assertSee(route('quiz.learning'), false);
        $response->assertSee(route('research.contributions.create'), false);
    }

    public function test_activity_links_leave_the_dashboard(): void
    {
        $user = User::factory()->create();
        $status = app(DailyEngagementService::class)->todayStatus($user);

        $this->assertSame(route('quiz.learning'), $status['activities']['quiz']['href']);
        $this->assertSame(route('research.contributions.create'), $status['activities']['contribute']['href']);
        $this->assertSame(route('seerah'), $status['activities']['insight']['href']);
        $this->assertSame(route('daily.dua'), $status['activities']['dua']['href']);
        $this->assertSame(route('prayer'), $status['activities']['prayer']['href']);
        $this->assertStringNotContainsString('/dashboard', $status['activities']['contribute']['href']);
        $this->assertStringNotContainsString('/dashboard', $status['activities']['quiz']['href']);
    }

    public function test_first_task_awards_task_points_and_day_one_streak_points(): void
    {
        $user = User::factory()->create([
            'streak_count' => 0,
            'last_streak_date' => null,
            'total_score' => 0,
            'contribution_points' => 0,
        ]);

        $status = app(DailyEngagementService::class)->record($user, UserDailyEngagement::ACTIVITY_DUA);

        $this->assertSame(10, $status['points_awarded']);
        $this->assertSame(10, $user->fresh()->total_score);
        $this->assertSame(10, $user->fresh()->contribution_points);
    }

    public function test_later_task_the_same_day_awards_only_the_task(): void
    {
        $user = User::factory()->create([
            'streak_count' => 0,
            'last_streak_date' => null,
            'total_score' => 0,
            'contribution_points' => 0,
        ]);
        $service = app(DailyEngagementService::class);

        $service->record($user, UserDailyEngagement::ACTIVITY_DUA);
        $second = $service->record($user->fresh(), UserDailyEngagement::ACTIVITY_PRAYER);
        $repeat = $service->record($user->fresh(), UserDailyEngagement::ACTIVITY_PRAYER);

        $this->assertSame(5, $second['points_awarded']);
        $this->assertSame(0, $repeat['points_awarded']);
        $this->assertSame(15, $user->fresh()->total_score);
    }

    public function test_longer_streak_awards_more_points_up_to_the_cap(): void
    {
        $week = User::factory()->create([
            'streak_count' => 6,
            'last_streak_date' => now()->subDay()->toDateString(),
            'total_score' => 0,
            'contribution_points' => 0,
        ]);

        $checkedIn = $week->checkInStreak();

        $this->assertSame(7, $checkedIn['streak_count']);
        $this->assertSame(11, $checkedIn['points_awarded']);
        $this->assertSame(0, $week->fresh()->checkInStreak()['points_awarded']);

        $long = User::factory()->create([
            'streak_count' => 40,
            'last_streak_date' => now()->subDay()->toDateString(),
            'total_score' => 0,
            'contribution_points' => 0,
        ]);

        $this->assertSame(30, $long->checkInStreak()['points_awarded']);
        $this->assertSame(30, $long->fresh()->total_score);
    }

    public function test_prayer_page_shows_ordered_times_and_marks_the_daily_task(): void
    {
        $guest = $this->get('/prayer?latitude=24.86&longitude=67.01&tz=5');

        $guest->assertStatus(200);
        $guest->assertSee('Fajr');
        $guest->assertSee('Isha');
        $this->assertPrayerOrder($guest->getContent());

        $user = User::factory()->create([
            'streak_count' => 0,
            'last_streak_date' => null,
            'total_score' => 0,
            'contribution_points' => 0,
        ]);

        $response = $this->actingAs($user)->get('/prayer?latitude=24.86&longitude=67.01&tz=5');

        $response->assertStatus(200);
        $response->assertSee('You earned 10 points.');
        $this->assertTrue(
            app(DailyEngagementService::class)->todayStatus($user->fresh())['activities']['prayer']['done']
        );
        $this->assertSame(10, $user->fresh()->total_score);

        $this->actingAs($user->fresh())->get('/prayer?latitude=24.86&longitude=67.01&tz=5')
            ->assertDontSee('You earned');
    }

    private function assertPrayerOrder(string $html): void
    {
        preg_match_all('/\b([01]\d|2[0-3]):[0-5]\d\b/', $html, $matches);
        $minutes = array_map(function (string $clock) {
            [$hour, $minute] = array_map('intval', explode(':', $clock));

            return $hour * 60 + $minute;
        }, $matches[0]);

        $this->assertCount(6, $minutes);
        $ordered = $minutes;
        sort($ordered);
        $this->assertSame($ordered, $minutes);
        $this->assertNotSame($minutes[0], $minutes[5]);
    }
}
