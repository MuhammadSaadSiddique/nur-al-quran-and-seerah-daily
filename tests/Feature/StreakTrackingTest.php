<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StreakTrackingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_login_with_password_automatically_checks_in_streak()
    {
        $user = User::factory()->create([
            'email' => 'streakuser@example.com',
            'password' => Hash::make('secret123'),
            'streak_count' => 0,
            'last_streak_date' => null,
        ]);

        $response = $this->postJson(route('login.password'), [
            'email' => 'streakuser@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $user->refresh();
        $streak = $user->getStreakStatus();

        $this->assertEquals(1, $streak['current_streak']);
        $this->assertTrue($streak['is_active_today']);
        $this->assertEquals(now()->toDateString(), $streak['last_streak_date']);
    }

    /** @test */
    public function user_login_with_otp_automatically_checks_in_streak()
    {
        $user = User::factory()->create([
            'email' => 'otpstreak@example.com',
            'streak_count' => 0,
            'last_streak_date' => null,
        ]);

        OtpCode::create([
            'email' => 'otpstreak@example.com',
            'otp' => '654321',
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/otp/verify', [
            'email' => 'otpstreak@example.com',
            'otp' => '654321',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $user->refresh();
        $streak = $user->getStreakStatus();

        $this->assertEquals(1, $streak['current_streak']);
        $this->assertTrue($streak['is_active_today']);
    }

    /** @test */
    public function consecutive_day_login_increments_streak_count()
    {
        $user = User::factory()->create([
            'email' => 'consecutive@example.com',
            'password' => Hash::make('secret123'),
            'streak_count' => 5,
            'longest_streak' => 5,
            'last_streak_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->postJson(route('login.password'), [
            'email' => 'consecutive@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $streak = $user->getStreakStatus();

        $this->assertEquals(6, $streak['current_streak']);
        $this->assertEquals(6, $streak['longest_streak']);
        $this->assertTrue($streak['is_active_today']);
    }

    /** @test */
    public function authenticated_user_can_claim_streak_via_post_endpoint()
    {
        $user = User::factory()->create([
            'streak_count' => 0,
            'last_streak_date' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('streak.check-in'));
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertEquals(1, $user->streak_count);
        $this->assertEquals(now()->toDateString(), $user->last_streak_date->toDateString());
    }

    /** @test */
    public function authenticated_user_can_retrieve_streak_status()
    {
        $user = User::factory()->create([
            'streak_count' => 3,
            'longest_streak' => 7,
            'last_streak_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson(route('streak.status'));
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'current_streak' => 3,
                'longest_streak' => 7,
                'is_active_today' => true,
            ]
        ]);
    }
}
