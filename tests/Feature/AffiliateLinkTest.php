<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\User;
use App\Services\ContributionPoints;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AffiliateLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_a_personal_invite_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Your invite link')
            ->assertSee('/a/');

        $this->assertNotEmpty($user->fresh()->affiliate_code);
    }

    public function test_new_signup_through_an_invite_pays_the_referrer_once(): void
    {
        Mail::fake();
        $referrer = User::factory()->create();
        $url = $referrer->affiliateUrl();

        $this->get($url)->assertRedirect(route('login'));
        $this->get(route('login'))->assertSee('You opened an invite link');

        $this->postJson('/otp/request', ['email' => 'invited@example.com'])->assertOk();
        $otp = Mail::sent(OtpMail::class)->last()->otp;

        $this->postJson('/otp/verify', [
            'email' => 'invited@example.com',
            'otp' => $otp,
        ])->assertOk();

        $joined = User::where('email', 'invited@example.com')->firstOrFail();
        $this->assertSame($referrer->id, $joined->referred_by);

        $referrer->refresh();
        $this->assertSame(ContributionPoints::AFFILIATE_SIGNUP_POINTS, (int) $referrer->contribution_points);
        $this->assertSame(ContributionPoints::AFFILIATE_SIGNUP_POINTS, (int) $referrer->total_score);

        $this->postJson('/otp/request', ['email' => 'invited@example.com'])->assertOk();
        $otp = Mail::sent(OtpMail::class)->last()->otp;
        $this->withSession(['affiliate_code' => $referrer->affiliate_code])
            ->postJson('/otp/verify', [
                'email' => 'invited@example.com',
                'otp' => $otp,
            ])->assertOk();

        $this->assertSame(ContributionPoints::AFFILIATE_SIGNUP_POINTS, (int) $referrer->fresh()->contribution_points);
    }

    public function test_unknown_invite_does_not_attach_a_referrer(): void
    {
        Mail::fake();

        $this->get('/a/notacode')->assertRedirect(route('login'));
        $this->postJson('/otp/request', ['email' => 'plain@example.com'])->assertOk();
        $otp = Mail::sent(OtpMail::class)->last()->otp;

        $this->postJson('/otp/verify', [
            'email' => 'plain@example.com',
            'otp' => $otp,
        ])->assertOk();

        $this->assertNull(User::where('email', 'plain@example.com')->firstOrFail()->referred_by);
    }
}
