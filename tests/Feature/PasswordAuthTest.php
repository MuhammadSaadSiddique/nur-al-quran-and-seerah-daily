<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
            'error' => 'Invalid email or password.',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'newuser@example.com',
        ]);

        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function user_can_verify_otp_and_then_set_password()
    {
        Mail::fake();

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
        $this->assertTrue(
            str_starts_with((string) $otpRecord->otp, '$2y$')
            || str_starts_with((string) $otpRecord->otp, '$2a$')
        );

        $plainOtp = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$plainOtp) {
            $plainOtp = $mail->otp;
            return true;
        });
        $this->assertNotEmpty($plainOtp);

        $response = $this->postJson('/otp/verify', [
            'email' => 'otpuser@example.com',
            'otp' => $plainOtp,
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

        $response = $this->actingAs($user)->postJson(route('profile.update'), [
            'password' => 'securepassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('securepassword123', $user->password));
    }

    /** @test */
    public function existing_user_with_password_can_login()
    {
        User::factory()->create([
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
        User::factory()->create([
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
        User::factory()->create([
            'email' => 'otpuser@example.com',
            'password' => '',
        ]);

        $response = $this->postJson(route('login.password'), [
            'email' => 'otpuser@example.com',
            'password' => 'somepassword',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'Invalid email or password.',
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
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /** @test */
    public function profile_password_change_requires_current_password_when_already_set()
    {
        $user = User::factory()->create([
            'email' => 'haspass@example.com',
            'password' => bcrypt('old-secret'),
        ]);

        $denied = $this->actingAs($user)->postJson(route('profile.update'), [
            'password' => 'brand-new-password',
        ]);
        $denied->assertStatus(422);

        $wrong = $this->actingAs($user)->postJson(route('profile.update'), [
            'password' => 'brand-new-password',
            'current_password' => 'not-the-old-one',
        ]);
        $wrong->assertStatus(422);
        $wrong->assertJson([
            'success' => false,
            'error' => 'Current password is incorrect.',
        ]);

        $ok = $this->actingAs($user)->postJson(route('profile.update'), [
            'password' => 'brand-new-password',
            'current_password' => 'old-secret',
        ]);
        $ok->assertStatus(200);
        $ok->assertJson(['success' => true]);

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-password', $user->password));
    }

    /** @test */
    public function role_flags_are_not_mass_assignable_from_fill()
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_researcher' => false,
        ]);

        $user->fill([
            'is_admin' => true,
            'is_researcher' => true,
            'display_name' => 'Escalated',
        ])->save();

        $user->refresh();
        $this->assertFalse($user->is_admin);
        $this->assertFalse($user->is_researcher);
        $this->assertEquals('Escalated', $user->display_name);
    }
}
