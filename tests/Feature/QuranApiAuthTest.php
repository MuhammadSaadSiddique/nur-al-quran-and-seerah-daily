<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\QuranApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuranApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_refresh_uses_http_basic_auth(): void
    {
        config([
            'services.quran.client_id' => 'client-id',
            'services.quran.client_secret' => 'client-secret',
            'services.quran.auth_url' => 'https://prelive-oauth2.quran.foundation',
        ]);

        Http::fake([
            'prelive-oauth2.quran.foundation/*' => Http::response([
                'access_token' => 'new-access',
                'refresh_token' => 'new-refresh',
            ]),
        ]);

        $user = User::factory()->create();
        $user->forceFill([
            'quran_access_token' => 'old-access',
            'quran_refresh_token' => 'old-refresh',
        ])->save();

        $this->assertTrue(app(QuranApiService::class)->refreshToken($user));

        $user->refresh();
        $this->assertSame('new-access', $user->quran_access_token);
        $this->assertSame('new-refresh', $user->quran_refresh_token);

        Http::assertSent(function ($request) {
            $authorization = $request->header('Authorization')[0] ?? '';
            $body = $request->body();

            return $request->url() === 'https://prelive-oauth2.quran.foundation/oauth2/token'
                && $authorization === 'Basic ' . base64_encode('client-id:client-secret')
                && str_contains($body, 'grant_type=refresh_token')
                && str_contains($body, 'refresh_token=old-refresh')
                && !str_contains($body, 'client_secret');
        });
    }
}
