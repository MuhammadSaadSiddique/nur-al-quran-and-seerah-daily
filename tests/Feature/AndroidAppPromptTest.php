<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AndroidAppPromptTest extends TestCase
{
    use RefreshDatabase;

    private const ANDROID_BROWSER = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';

    private const ANDROID_WEBVIEW = 'Mozilla/5.0 (Linux; Android 14; Pixel 8; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/120.0.0.0 Mobile Safari/537.36';

    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    public function test_home_page_lists_latest_features(): void
    {
        $response = $this->get(route('lens.landing'));

        $response->assertOk();
        $response->assertSee('Salah times');
        $response->assertSee('Scholars');
        $response->assertSee('Mosques and adhan');
        $response->assertSee('Root-word search');
        $response->assertSee(route('prayer'), false);
        $response->assertSee(route('scholars.index'), false);
    }

    public function test_android_browser_is_asked_to_download_the_app(): void
    {
        $response = $this->withHeaders(['User-Agent' => self::ANDROID_BROWSER])
            ->get(route('lens.landing'));

        $response->assertOk();
        $response->assertSee('Download the app for the full experience');
        $response->assertSee('https://play.google.com/store/apps/details?id=com.asloobulhayat.eternalecho', false);
    }

    public function test_desktop_and_app_webview_are_not_asked_to_download(): void
    {
        $desktop = $this->withHeaders(['User-Agent' => self::DESKTOP])->get(route('lens.landing'));
        $webview = $this->withHeaders(['User-Agent' => self::ANDROID_WEBVIEW])->get(route('lens.landing'));

        $desktop->assertOk();
        $webview->assertOk();
        $desktop->assertDontSee('Download the app for the full experience');
        $webview->assertDontSee('Download the app for the full experience');
    }

    public function test_dashboard_asks_android_browsers_to_download_the_app(): void
    {
        $user = User::factory()->create();

        $android = $this->actingAs($user)
            ->withHeaders(['User-Agent' => self::ANDROID_BROWSER])
            ->get(route('home'));
        $desktop = $this->actingAs($user)
            ->withHeaders(['User-Agent' => self::DESKTOP])
            ->get(route('home'));

        $android->assertOk();
        $desktop->assertOk();
        $android->assertSee('Download the app for the full experience');
        $android->assertSee("What's new", false);
        $desktop->assertDontSee('Download the app for the full experience');
        $desktop->assertSee('Salah times');
    }
}
