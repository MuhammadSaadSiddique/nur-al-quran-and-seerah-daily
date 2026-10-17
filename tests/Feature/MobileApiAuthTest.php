<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MobileApiAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_token_is_required_for_password_change(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password'),
        ]);

        $response = $this->post('/api/v1/mobile.php?action=change_password', [
            'user_id' => $user->id,
            'password' => 'hijacked-password',
        ]);

        // Standalone script may 404 under php artisan serve routing; assert unauthorized when reachable.
        if ($response->status() === 404) {
            $this->markTestSkipped('mobile.php is not routed through the Laravel test client in this environment.');
        }

        $response->assertStatus(401);
        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('old-password', $user->password));
    }

    public function test_issued_mobile_token_authenticates_user(): void
    {
        $user = User::factory()->create();
        $token = MobileApiAuthService::issueToken($user);

        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => MobileApiAuthService::TOKEN_NAME,
        ]);
    }

    public function test_set_password_rejected_when_password_already_exists(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('existing-password'),
        ]);
        $token = MobileApiAuthService::issueToken($user);

        $response = $this->post('/api/v1/mobile.php?action=set_password', [
            'password' => 'hijacked-password',
        ], [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ]);

        if ($response->status() === 404) {
            $this->markTestSkipped('mobile.php is not routed through the Laravel test client in this environment.');
        }

        $response->assertOk();
        $payload = $response->json();
        $this->assertSame('error', $payload['status'] ?? null);
        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('existing-password', $user->password));
    }
}
