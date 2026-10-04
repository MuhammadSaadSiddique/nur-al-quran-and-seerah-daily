<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_notifications_via_api(): void
    {
        AppNotification::create([
            'title' => 'Test Welcome',
            'message' => 'Welcome to the updated version of The Eternal Echo!',
            'type' => 'announcement',
            'send_push' => true,
        ]);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'count',
                'data' => [
                    '*' => ['id', 'title', 'message', 'type', 'created_at']
                ]
            ]);
    }

    public function test_can_register_device_token_via_api(): void
    {
        $response = $this->postJson('/api/device-token', [
            'token' => 'sample-test-fcm-token-1234567890',
            'platform' => 'android',
            'device_name' => 'Pixel 8 Pro',
            'app_version' => '1.0',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('device_tokens', [
            'token' => 'sample-test-fcm-token-1234567890',
            'platform' => 'android',
        ]);
    }

    public function test_admin_can_send_announcement_with_push_notification(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        DeviceToken::create([
            'token' => 'sample-device-token-abc',
            'platform' => 'android',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.announcements.send'), [
            'subject' => 'Major App Update Live!',
            'content' => 'Explore the new Quranic Lens and Duas library directly in the mobile app.',
            'send_push' => '1',
            'send_email' => '0',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('app_notifications', [
            'title' => 'Major App Update Live!',
            'send_push' => 1,
        ]);
    }
}
