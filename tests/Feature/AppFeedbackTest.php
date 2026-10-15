<?php

namespace Tests\Feature;

use App\Models\AppFeedback;
use App\Models\User;
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
}
