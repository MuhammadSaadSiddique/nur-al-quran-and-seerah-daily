<?php

namespace Tests\Feature;

use App\Models\DonationRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_donation_landing_page_renders_successfully()
    {
        $response = $this->get(route('donate'));

        $response->assertStatus(200);
        $response->assertSee('Sadaqah Jariyah');
        $response->assertSee('The Eternal Echo');
        $response->assertSee('Bank Transfer');
        $response->assertSee('Online / Card / PayPal');
    }

    public function test_guest_can_submit_donation_notification()
    {
        $response = $this->postJson(route('donate.notify'), [
            'donor_name' => 'Abdullah',
            'donor_email' => 'abdullah@example.com',
            'amount' => 50,
            'currency' => 'USD',
            'payment_method' => 'card',
            'transaction_reference' => 'TXN-987654321',
            'message' => 'May Allah bless this effort in spreading authentic Quranic knowledge.'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'amount' => 50,
                    'currency' => 'USD',
                    'transaction_reference' => 'TXN-987654321',
                    'status' => 'pending'
                ]
            ]);

        $this->assertDatabaseHas('donation_records', [
            'donor_name' => 'Abdullah',
            'donor_email' => 'abdullah@example.com',
            'amount' => 50.00,
            'currency' => 'USD',
            'payment_method' => 'card',
            'transaction_reference' => 'TXN-987654321',
            'status' => 'pending',
            'user_id' => null
        ]);
    }

    public function test_authenticated_user_notification_attaches_user_id()
    {
        $user = User::factory()->create([
            'name' => 'Fatima',
            'email' => 'fatima@example.com'
        ]);

        $response = $this->actingAs($user)->postJson(route('donate.notify'), [
            'amount' => 100,
            'currency' => 'USD',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'MEEZAN-REF-4455',
            'message' => 'Contribution for server infrastructure.'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'amount' => 100,
                    'currency' => 'USD',
                    'status' => 'pending'
                ]
            ]);

        $this->assertDatabaseHas('donation_records', [
            'user_id' => $user->id,
            'donor_name' => 'Fatima',
            'donor_email' => 'fatima@example.com',
            'amount' => 100.00,
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'MEEZAN-REF-4455'
        ]);
    }

    public function test_validation_fails_for_invalid_notification_payload()
    {
        $response = $this->postJson(route('donate.notify'), [
            'amount' => -10,
            'currency' => '',
            'payment_method' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'currency', 'payment_method']);
    }

    public function test_guest_and_non_admin_cannot_access_admin_donations()
    {
        // Guest
        $guestResponse = $this->get(route('admin.donations.index'));
        $guestResponse->assertRedirect(route('login'));

        // Non-admin user
        $user = User::factory()->create(['is_admin' => false]);
        $userResponse = $this->actingAs($user)->get(route('admin.donations.index'));
        $userResponse->assertStatus(403);
    }

    public function test_admin_can_view_donations_and_update_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $donation = DonationRecord::create([
            'donor_name' => 'Zayd',
            'donor_email' => 'zayd@example.com',
            'amount' => 75.00,
            'currency' => 'USD',
            'payment_method' => 'crypto',
            'transaction_reference' => '0x992384918239abc',
            'message' => 'BarakAllahu Feekum',
            'status' => 'pending'
        ]);

        // Admin can view the list
        $viewResponse = $this->actingAs($admin)->get(route('admin.donations.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Zayd');
        $viewResponse->assertSee('0x992384918239abc');

        // Admin can update status
        $updateResponse = $this->actingAs($admin)->patch(route('admin.donations.update-status', $donation), [
            'status' => 'verified'
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('donation_records', [
            'id' => $donation->id,
            'status' => 'verified'
        ]);
    }

    public function test_user_can_submit_donation_with_name_and_choose_to_hide_it()
    {
        $response = $this->postJson(route('donate.notify'), [
            'donor_name' => 'Tariq Al-Mansoor',
            'donor_email' => 'tariq@example.com',
            'is_anonymous' => true,
            'amount' => 150,
            'currency' => 'USD',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'TXN-ANON-999',
            'message' => 'Please keep this anonymous for Allah’s sake.'
        ]);

        $response->assertStatus(200);

        $record = DonationRecord::where('transaction_reference', 'TXN-ANON-999')->first();
        $this->assertNotNull($record);
        $this->assertEquals('Tariq Al-Mansoor', $record->donor_name);
        $this->assertTrue($record->is_anonymous);
        $this->assertEquals('Anonymous Supporter (فاعل خير)', $record->public_donor_name);

        // Verify admin can see both the real name and the Anonymous badge
        $admin = User::factory()->create(['is_admin' => true]);
        $adminResponse = $this->actingAs($admin)->get(route('admin.donations.index'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Tariq Al-Mansoor');
        $adminResponse->assertSee('Anonymous');
    }

    public function test_verified_supporters_respect_anonymity_on_public_page()
    {
        // 1. Visible donor
        DonationRecord::create([
            'donor_name' => 'Bilal Habashi',
            'is_anonymous' => false,
            'amount' => 50,
            'currency' => 'USD',
            'payment_method' => 'card',
            'status' => 'verified'
        ]);

        // 2. Hidden / Anonymous donor
        DonationRecord::create([
            'donor_name' => 'Secret Supporter Name',
            'is_anonymous' => true,
            'amount' => 200,
            'currency' => 'USD',
            'payment_method' => 'bank_transfer',
            'status' => 'verified'
        ]);

        $response = $this->get(route('donate'));
        $response->assertStatus(200);

        // Visible donor should be shown publicly
        $response->assertSee('Bilal Habashi');

        // Hidden donor real name must NOT be displayed publicly
        $response->assertDontSee('Secret Supporter Name');
        $response->assertSee('Anonymous Supporter (فاعل خير)');
    }

    public function test_api_endpoint_can_submit_donation_notification()
    {
        $response = $this->postJson('/api/donate/notify', [
            'donor_name' => 'Mobile Supporter',
            'donor_email' => 'mobile@example.com',
            'is_anonymous' => false,
            'amount' => 50,
            'currency' => 'USD',
            'payment_method' => 'easypaisa',
            'transaction_reference' => 'EP-998877',
            'message' => 'From mobile app'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success'
        ]);

        $this->assertDatabaseHas('donation_records', [
            'transaction_reference' => 'EP-998877',
            'payment_method' => 'easypaisa'
        ]);
    }
}
