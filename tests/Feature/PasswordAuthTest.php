<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function new_user_can_register_via_password()
    {
        $response = $this->postJson(route('login.password'), [
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect' => route('home'),
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);

        $this->assertTrue(auth()->check());
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
