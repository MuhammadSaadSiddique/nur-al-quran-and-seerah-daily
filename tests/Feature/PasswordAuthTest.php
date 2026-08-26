<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function new_user_cannot_register_via_password_directly()
    {
        $response = $this->postJson(route('login.password'), [
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'Account not found. Please register/sign up using OTP first.',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'newuser@example.com',
        ]);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function user_can_verify_otp_and_then_set_password()
    {
        // 1. Request OTP
        $response = $this->postJson('/otp/request', [
            'email' => 'otpuser@example.com',
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('otp_codes', [
            'email' => 'otpuser@example.com',
        ]);

        $otpRecord = \App\Models\OtpCode::where('email', 'otpuser@example.com')->first();
        $this->assertNotNull($otpRecord);

        // 2. Verify OTP
        $response = $this->postJson('/otp/verify', [
            'email' => 'otpuser@example.com',
            'otp' => $otpRecord->otp,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_password' => false,
        ]);

        $this->assertTrue(auth()->check());
        $user = auth()->user();
        $this->assertEquals('otpuser@example.com', $user->email);
        $this->assertEmpty($user->password);

        // 3. Set password via profile update route
        $response = $this->actingAs($user)->postJson(route('profile.update'), [
            'password' => 'securepassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('securepassword123', $user->password));
    }

    /** @test */
    public function existing_user_with_password_can_login()
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson(route('login.password'), [
            'email' => 'existing@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect' => route('home'),
        ]);

        $this->assertTrue(auth()->check());
    }

    /** @test */
    public function user_cannot_login_with_incorrect_password()
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson(route('login.password'), [
            'email' => 'existing@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'Invalid email or password.',
        ]);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function user_without_password_cannot_login_via_password()
    {
        $user = User::factory()->create([
            'email' => 'otpuser@example.com',
            'password' => '', // Registered via OTP, no password set
        ]);

        $response = $this->postJson(route('login.password'), [
            'email' => 'otpuser@example.com',
            'password' => 'somepassword',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'You registered via OTP and do not have a password set yet. Please log in via OTP first, then set a password in your Profile.',
        ]);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function logged_in_user_can_set_password_in_profile()
    {
        $user = User::factory()->create([
            'email' => 'profileuser@example.com',
            'password' => '',
        ]);

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'display_name' => 'Profile User',
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(302);
        
        $user->refresh();
        $this->assertEquals('Profile User', $user->display_name);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->password));
    }
}
