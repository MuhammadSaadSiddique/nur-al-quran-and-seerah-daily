<?php

namespace Tests\Feature;

use App\Models\AppFeedback;
use App\Models\User;
use App\Models\UserDailyEngagement;
use App\Services\ContributionPoints;
use App\Services\DailyEngagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_app_feedback_via_web_endpoint()
    {
        $response = $this->postJson(route('app-feedback.store'), [
            'rating' => 5,
            'category' => 'app_experience',
            'message' => 'MashaAllah, excellent application for learning the Quran!',
            'platform' => 'web',
            'user_name' => 'Tariq',
            'user_email' => 'tariq@example.com'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'rating' => 5,
                    'category' => 'app_experience'
                ]
            ]);

        $this->assertDatabaseHas('app_feedbacks', [
            'rating' => 5,
            'category' => 'app_experience',
            'user_name' => 'Tariq',
            'platform' => 'web',
            'status' => 'pending'
        ]);
    }

    public function test_authenticated_user_feedback_associates_user_id()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('app-feedback.store'), [
            'rating' => 4,
            'category' => 'content',
            'message' => 'Great insights, would love to see more Tafsir commentary.'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'rating' => 4,
                    'category' => 'content'
                ]
            ]);

        $this->assertDatabaseHas('app_feedbacks', [
            'user_id' => $user->id,
            'rating' => 4,
            'category' => 'content'
        ]);
    }

    public function test_rating_validation_rejects_out_of_bounds_values()
    {
        $response = $this->postJson(route('app-feedback.store'), [
            'rating' => 6, // invalid, max 5
            'message' => 'Invalid'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_admin_can_view_app_feedback_list_and_update_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $feedback = AppFeedback::create([
            'rating' => 5,
            'category' => 'feature_request',
            'message' => 'Please add audio recitation playback.',
            'platform' => 'web',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.app-feedback.index'));
        $response->assertStatus(200);
        $response->assertSee('Please add audio recitation playback.');

        $patchResponse = $this->actingAs($admin)->patch(route('admin.app-feedback.update-status', $feedback), [
            'status' => 'reviewed'
        ]);
        $patchResponse->assertRedirect();

        $this->assertDatabaseHas('app_feedbacks', [
            'id' => $feedback->id,
            'status' => 'reviewed'
        ]);
    }

    public function test_api_endpoint_can_submit_app_feedback()
    {
        $response = $this->postJson('/api/v1/app-feedback', [
            'rating' => 5,
            'category' => 'app_experience',
            'message' => 'Loving the mobile companion experience!',
            'platform' => 'android',
            'device_info' => 'Pixel 8 - Android 14'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);

        $this->assertDatabaseHas('app_feedbacks', [
            'rating' => 5,
            'platform' => 'android',
            'device_info' => 'Pixel 8 - Android 14'
        ]);
    }

    public function test_signed_in_feedback_saves_100_reward_points_once(): void
    {
        $user = User::factory()->create([
            'total_score' => 40,
            'contribution_points' => 0,
            'reward_points' => 0,
        ]);

        $first = $this->actingAs($user)->postJson(route('app-feedback.store'), [
            'rating' => 5,
            'message' => 'The prayer page is useful.',
        ]);

        $first->assertOk()
            ->assertJsonPath('data.points_awarded', 100)
            ->assertJsonPath('data.reward_points', 100)
            ->assertJsonPath('reward_note', 'You earned 100 points. They stay on your account for events and courses.');

        $user->refresh();
        $this->assertSame(100, $user->reward_points);
        $this->assertSame(40, $user->total_score);

        $second = $this->actingAs($user)->postJson(route('app-feedback.store'), [
            'rating' => 4,
            'message' => 'Another note.',
        ]);

        $second->assertOk()->assertJsonPath('data.points_awarded', 0);
        $this->assertSame(100, $user->fresh()->reward_points);
        $this->assertSame(40, $user->fresh()->total_score);
        $this->assertSame(ContributionPoints::APP_FEEDBACK_POINTS, 100);
    }

    public function test_guest_feedback_does_not_create_reward_points(): void
    {
        $response = $this->postJson(route('app-feedback.store'), [
            'rating' => 5,
            'message' => 'Beautiful recitation links.',
            'user_name' => 'Amina',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.points_awarded', 0)
            ->assertJsonPath('reward_note', '');
    }

    public function test_profile_shows_reward_balance_without_a_claim_form(): void
    {
        $user = User::factory()->create([
            'total_score' => 0,
            'contribution_points' => 0,
            'reward_points' => 0,
            'streak_count' => 0,
            'last_streak_date' => null,
        ]);

        app(DailyEngagementService::class)->record($user, UserDailyEngagement::ACTIVITY_DUA);
        $this->assertSame(10, $user->fresh()->reward_points);
        $this->assertSame(10, $user->fresh()->total_score);

        $this->actingAs($user)->get(route('profile'))
            ->assertOk()
            ->assertSee('Reward points')
            ->assertDontSee('Claim points');
    }
}
