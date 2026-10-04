<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\OtpCode;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_can_view_account_deletion_page()
    {
        $response = $this->get('/delete-account');

        $response->assertStatus(200);
        $response->assertSee('Account &amp; Data Deletion', false);
        $response->assertSee('What Data Is Permanently Deleted');
        $response->assertSee('Request Deletion via Registered Email');
    }

    /** @test */
    public function account_delete_url_redirects_to_delete_account()
    {
        $response = $this->get('/account-delete');

        $response->assertRedirect('/delete-account');
    }

    /** @test */
    public function guest_cannot_request_otp_for_non_existent_email()
    {
        $response = $this->postJson('/delete-account/request-otp', [
            'email' => 'unknown@example.com',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'No active account found with this email address.',
        ]);
    }

    /** @test */
    public function guest_can_request_otp_and_verify_to_delete_account()
    {
        $user = User::factory()->create([
            'email' => 'user_to_delete@example.com',
            'name' => 'Delete Me',
        ]);

        Quiz::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'type' => 'THEME',
            'title' => 'Test Quiz',
            'score' => 80,
            'total_questions' => 10,
            'difficulty' => 'Easy',
            'details' => [],
        ]);

        $response = $this->postJson('/delete-account/request-otp', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $otpRecord = OtpCode::where('email', $user->email)->latest()->first();
        $this->assertNotNull($otpRecord);

        // Verify with invalid OTP fails
        $failResponse = $this->postJson('/delete-account/verify-otp', [
            'email' => $user->email,
            'otp' => '999999',
        ]);
        $failResponse->assertStatus(400);

        // Verify with correct OTP succeeds and deletes account
        $successResponse = $this->postJson('/delete-account/verify-otp', [
            'email' => $user->email,
            'otp' => $otpRecord->otp,
        ]);

        $successResponse->assertStatus(200);
        $successResponse->assertJson(['success' => true]);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('quizzes', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('otp_codes', ['email' => $user->email]);
    }

    /** @test */
    public function authenticated_user_with_password_can_delete_account()
    {
        $user = User::factory()->create([
            'email' => 'auth_delete@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->actingAs($user)->post('/delete-account/confirm', [
            'password' => 'secret123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/delete-account');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function authenticated_user_with_wrong_password_cannot_delete_account()
    {
        $user = User::factory()->create([
            'email' => 'auth_wrong_pw@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->actingAs($user)->post('/delete-account/confirm', [
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function authenticated_user_without_password_can_delete_with_confirmation_flag()
    {
        $user = User::factory()->create([
            'email' => 'otp_user@example.com',
            'password' => '',
        ]);

        $response = $this->actingAs($user)->post('/delete-account/confirm', [
            'confirm_deletion' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/delete-account');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function footer_and_privacy_policy_contain_account_deletion_links()
    {
        $privacyResponse = $this->get('/privacy-policy');
        $privacyResponse->assertStatus(200);
        $privacyResponse->assertSee('/delete-account');
        $privacyResponse->assertSee('Account Deletion');
    }

    /** @test */
    public function perform_account_deletion_cleans_up_all_user_records()
    {
        $user = User::factory()->create([
            'email' => 'full_cleanup@example.com',
        ]);

        Quiz::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'type' => 'THEME',
            'title' => 'Cleanup Test Quiz',
            'score' => 90,
            'total_questions' => 10,
            'difficulty' => 'Medium',
            'details' => [],
        ]);

        \App\Http\Controllers\AccountDeletionController::performAccountDeletion($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('quizzes', ['user_id' => $user->id]);
    }
}
