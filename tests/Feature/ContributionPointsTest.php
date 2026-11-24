<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ContributionPoints;
use App\Services\MosqueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributionPointsTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = 24.9204;
    private const LNG = 67.0932;

    public function test_adding_and_updating_a_mosque_awards_points_once(): void
    {
        $user = User::factory()->create(['total_score' => 5]);
        $service = app(MosqueService::class);

        $added = $service->create($user, [
            'name' => 'Masjid Points',
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'fajr_time' => '05:10',
        ]);

        $this->assertSame(ContributionPoints::MOSQUE_ADDED_POINTS, $added['points_awarded']);

        $duplicate = $service->create($user, [
            'name' => 'Masjid Points',
            'latitude' => self::LAT,
            'longitude' => self::LNG,
        ]);
        $this->assertTrue($duplicate['duplicate']);
        $this->assertSame(0, $duplicate['points_awarded']);

        $mosque = \App\Models\Mosque::findOrFail($added['mosque']['id']);
        $updated = $service->updateTimes($user, $mosque, ['fajr_time' => '05:20']);
        $this->assertSame(ContributionPoints::MOSQUE_UPDATED_POINTS, $updated['points_awarded']);

        $confirmed = $service->updateTimes($user->fresh(), $mosque->fresh(), ['fajr_time' => '05:20']);
        $this->assertFalse($confirmed['changed']);
        $this->assertSame(0, $confirmed['points_awarded']);

        $neighbour = User::factory()->create();
        $vouched = $service->confirmTimes($neighbour, $mosque->fresh());
        $this->assertSame(ContributionPoints::MOSQUE_CONFIRMED_POINTS, $vouched['points_awarded']);
        $this->assertSame(0, $service->confirmTimes($neighbour->fresh(), $mosque->fresh())['points_awarded']);

        $details = $service->updateDetails($user->fresh(), $mosque->fresh(), ['has_women_area' => 'yes']);
        $this->assertSame(ContributionPoints::MOSQUE_UPDATED_POINTS, $details['points_awarded']);

        $user->refresh();
        $expected = 5
            + ContributionPoints::MOSQUE_ADDED_POINTS
            + ContributionPoints::MOSQUE_UPDATED_POINTS
            + ContributionPoints::MOSQUE_UPDATED_POINTS
            + ContributionPoints::DAILY_TASK_POINTS
            + ContributionPoints::STREAK_BASE_POINTS;
        $this->assertSame($expected, (int) $user->total_score);
        $this->assertSame($expected - 5, (int) $user->contribution_points);
        $this->assertSame(5, $user->quiz_score);
    }

    public function test_quran_video_and_connection_award_points_and_keep_quiz_accuracy(): void
    {
        $user = User::factory()->create([
            'total_score' => 5,
            'total_questions' => 2,
            'difficulty_stats' => [
                'total' => ['Easy' => 2, 'Medium' => 0, 'Hard' => 0],
                'correct' => ['Easy' => 1, 'Medium' => 0, 'Hard' => 0],
            ],
        ]);

        $this->actingAs($user)->post(route('research.contributions.store'), [
            'title' => 'Tafsir video',
            'surah_number' => 1,
            'verse_number' => 1,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertSessionHas('success');

        $this->actingAs($user)->post(route('research.contributions.store'), [
            'title' => 'Science link',
            'surah_number' => 21,
            'verse_number' => 30,
            'category' => 'science',
            'description' => 'Water and life.',
        ]);

        $user->refresh();
        $this->assertSame(5 + ContributionPoints::QURAN_VIDEO_POINTS + ContributionPoints::QURAN_CONNECTION_POINTS + ContributionPoints::DAILY_TASK_POINTS + ContributionPoints::STREAK_BASE_POINTS, (int) $user->total_score);
        $this->assertSame(ContributionPoints::QURAN_VIDEO_POINTS + ContributionPoints::QURAN_CONNECTION_POINTS + ContributionPoints::DAILY_TASK_POINTS + ContributionPoints::STREAK_BASE_POINTS, (int) $user->contribution_points);
        $this->assertSame(50, $user->accuracy);

        $again = app(ContributionPoints::class)->forResearch($user, 'video', 1);
        $this->assertSame(0, $again);
    }

    public function test_quran_point_awards_stop_at_the_daily_cap(): void
    {
        $user = User::factory()->create();
        $points = app(ContributionPoints::class);

        for ($i = 1; $i <= ContributionPoints::DAILY_QURAN_LIMIT; $i++) {
            $this->assertSame(
                ContributionPoints::QURAN_CONNECTION_POINTS,
                $points->award($user, ContributionPoints::QURAN_CONNECTION, 'quran_contribution:cap-' . $i)
            );
        }

        $this->assertSame(0, $points->award($user, ContributionPoints::QURAN_VIDEO, 'quran_contribution:cap-over'));
        $user->refresh();
        $this->assertSame(ContributionPoints::DAILY_QURAN_LIMIT * ContributionPoints::QURAN_CONNECTION_POINTS, (int) $user->contribution_points);
    }

    public function test_weekly_leaderboard_includes_contribution_points(): void
    {
        $contributor = User::factory()->create(['name' => 'Contributor Zayd', 'display_name' => 'Contributor Zayd']);
        app(MosqueService::class)->create($contributor, [
            'name' => 'Masjid Leaderboard',
            'latitude' => self::LAT,
            'longitude' => self::LNG,
        ]);

        $this->actingAs($contributor)
            ->get(route('leaderboard', ['period' => 'weekly']))
            ->assertOk()
            ->assertSee('Contributor Zayd')
            ->assertSee((string) ContributionPoints::MOSQUE_ADDED_POINTS);
    }
}
