<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visit;
use App\Services\VisitTracker;
use App\Support\TrafficSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class VisitorAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_ANDROID = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36';
    private const PUBLIC_IP = '39.40.1.2';

    private int $geoStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        config(['visitors.enabled' => true, 'visitors.geo.driver' => 'ipinfo']);
        Http::fake(fn () => $this->geoStatus === 200
            ? Http::response(['ip' => self::PUBLIC_IP, 'city' => 'Karachi', 'region' => 'Sindh', 'country' => 'PK'])
            : Http::response(['error' => 'rate limited'], $this->geoStatus));
    }

    private function visit(string $uri = '/privacy-policy', array $headers = [])
    {
        return $this->withServerVariables(['REMOTE_ADDR' => self::PUBLIC_IP])
            ->withHeaders(array_merge(['User-Agent' => self::CHROME_ANDROID], $headers))
            ->get($uri);
    }

    public function test_first_page_view_records_location_referrer_and_landing_page(): void
    {
        $response = $this->visit('/privacy-policy', ['Referer' => 'https://www.google.com.pk/search?q=private+query']);

        $response->assertOk()->assertCookie(VisitTracker::VISITOR_COOKIE)->assertCookie(VisitTracker::VISIT_COOKIE);

        $visit = Visit::sole();
        $this->assertSame('/privacy-policy', $visit->landing_path);
        $this->assertSame('search', $visit->channel);
        $this->assertSame('google.com', $visit->referrer_host);
        $this->assertSame('https://www.google.com.pk/search', $visit->referrer_url, 'query string is dropped');
        $this->assertSame('PK', $visit->country_code);
        $this->assertSame('Karachi', $visit->city);
        $this->assertSame('Sindh', $visit->region);
        $this->assertSame('mobile', $visit->device_type);
        $this->assertSame('Chrome', $visit->browser);
        $this->assertSame('Android', $visit->os);
        $this->assertTrue($visit->is_new_visitor);
        $this->assertSame(1, $visit->pageviews);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'ipinfo.io/' . self::PUBLIC_IP));
    }

    public function test_internal_navigation_continues_the_same_visit(): void
    {
        $first = $this->visit('/privacy-policy', ['Referer' => 'https://facebook.com/']);
        $visitorId = $first->getCookie(VisitTracker::VISITOR_COOKIE)->getValue();
        $visitCookie = $first->getCookie(VisitTracker::VISIT_COOKIE)->getValue();

        $this->withCookie(VisitTracker::VISITOR_COOKIE, $visitorId)
            ->withCookie(VisitTracker::VISIT_COOKIE, $visitCookie)
            ->visit('/terms', ['Referer' => url('/privacy-policy')])
            ->assertOk();

        $visit = Visit::sole();
        $this->assertSame(2, $visit->pageviews);
        $this->assertSame('/privacy-policy', $visit->landing_path);
        $this->assertSame('/terms', $visit->last_path);
        $this->assertSame('social', $visit->channel);
        Http::assertSentCount(1);
    }

    public function test_arriving_again_from_a_new_source_starts_a_new_visit_for_a_returning_visitor(): void
    {
        $first = $this->visit('/privacy-policy');
        $visitorId = $first->getCookie(VisitTracker::VISITOR_COOKIE)->getValue();
        $visitCookie = $first->getCookie(VisitTracker::VISIT_COOKIE)->getValue();

        $this->withCookie(VisitTracker::VISITOR_COOKIE, $visitorId)
            ->withCookie(VisitTracker::VISIT_COOKIE, $visitCookie)
            ->visit('/terms?utm_source=newsletter&utm_medium=email&utm_campaign=ramadan')
            ->assertOk();

        $this->assertSame(2, Visit::count());
        $second = Visit::orderByDesc('id')->first();
        $this->assertSame($visitorId, $second->visitor_id);
        $this->assertFalse($second->is_new_visitor);
        $this->assertSame('email', $second->channel);
        $this->assertSame('ramadan', $second->utm_campaign);
        $this->assertSame('/terms', $second->landing_path);
    }

    public function test_expired_visit_cookie_starts_a_new_visit(): void
    {
        $visitorId = (string) Str::uuid();

        $this->withCookie(VisitTracker::VISITOR_COOKIE, $visitorId)->visit('/terms')->assertOk();

        $visit = Visit::sole();
        $this->assertSame($visitorId, $visit->visitor_id);
        $this->assertFalse($visit->is_new_visitor);
        $this->assertSame('direct', $visit->channel);
    }

    public function test_cloudflare_location_headers_are_used_without_a_lookup(): void
    {
        $this->visit('/privacy-policy', ['CF-IPCountry' => 'AE', 'CF-IPCity' => 'Dubai', 'CF-Region' => 'Dubai'])->assertOk();

        $visit = Visit::sole();
        $this->assertSame('AE', $visit->country_code);
        $this->assertSame('Dubai', $visit->city);
        Http::assertNothingSent();
    }

    public function test_failed_lookup_still_records_the_visit(): void
    {
        $this->geoStatus = 429;

        $this->visit('/privacy-policy')->assertOk();

        $this->assertNull(Visit::sole()->country_code);
    }

    public function test_bots_admins_json_and_excluded_paths_are_not_counted(): void
    {
        $this->visit('/privacy-policy', ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']);
        $this->visit('/privacy-policy', ['User-Agent' => '']);
        $this->visit('/privacy-policy', ['Sec-Purpose' => 'prefetch']);
        $this->visit('/privacy-policy', ['X-Requested-With' => 'XMLHttpRequest']);
        $this->visit('/sitemap.xml');

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->visit('/privacy-policy')->assertOk();

        $this->assertSame(0, Visit::count());
    }

    public function test_tracking_can_be_switched_off(): void
    {
        config(['visitors.enabled' => false]);

        $this->visit('/privacy-policy')->assertOk()->assertCookieMissing(VisitTracker::VISITOR_COOKIE);

        $this->assertSame(0, Visit::count());
    }

    public function test_signed_in_visitor_is_linked_to_the_visit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->visit('/privacy-policy')->assertOk();

        $this->assertSame($user->id, Visit::sole()->user_id);
    }

    public function test_traffic_source_classification(): void
    {
        $cases = [
            [null, [], 'direct', null],
            ['https://quran-site.test/page', [], 'direct', null], // own host
            ['https://l.facebook.com/l.php?u=x', [], 'social', 'facebook.com'],
            ['https://t.co/abc', [], 'social', 'x.com'],
            ['https://chatgpt.com/', [], 'ai', 'chatgpt.com'],
            ['https://duckduckgo.com/', [], 'search', 'duckduckgo.com'],
            ['https://mail.google.com/', [], 'email', 'mail.google.com'],
            ['https://www.islamicfinder.org/some/page', [], 'referral', 'islamicfinder.org'],
            ['android-app://com.google.android.gm/', [], 'email', 'mail.google.com'],
            ['android-app://com.whatsapp', [], 'social', 'whatsapp.com'],
            [null, ['fbclid' => 'abc'], 'social', 'facebook.com'],
            [null, ['utm_source' => 'flyer', 'utm_campaign' => 'jummah'], 'campaign', null],
            ['https://www.facebook.com/', ['utm_source' => 'fb', 'utm_medium' => 'cpc'], 'campaign', 'facebook.com'],
        ];

        foreach ($cases as [$referrer, $query, $channel, $host]) {
            $result = TrafficSource::classify($referrer, 'www.quran-site.test', $query);
            $this->assertSame($channel, $result['channel'], "channel for {$referrer}");
            $this->assertSame($host, $result['referrer_host'], "host for {$referrer}");
        }
    }

    public function test_admin_dashboard_shows_locations_referrers_and_landing_pages(): void
    {
        $this->seedVisit(['country_code' => 'PK', 'city' => 'Lahore', 'referrer_host' => 'google.com', 'channel' => 'search', 'landing_path' => '/seerah', 'pageviews' => 3]);
        $this->seedVisit(['country_code' => 'GB', 'city' => 'London', 'referrer_host' => 'facebook.com', 'channel' => 'social', 'landing_path' => '/duas']);
        $this->seedVisit(['country_code' => 'PK', 'landing_path' => '/seerah', 'started_at' => now()->subDays(60), 'last_seen_at' => now()->subDays(60)]);

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.visitors.index'))
            ->assertOk()
            ->assertSee('Pakistan')
            ->assertSee('Lahore')
            ->assertSee('United Kingdom')
            ->assertSee('google.com')
            ->assertSee('facebook.com')
            ->assertSee('/seerah')
            ->assertSee('/duas');

        // Drill-down: only Pakistan.
        $this->actingAs($admin)->get(route('admin.visitors.index', ['country' => 'PK']))
            ->assertOk()
            ->assertSee('Remove filter')
            ->assertSee('Lahore')
            ->assertDontSee('London');

        // Each trend bucketing path renders.
        foreach (array_keys(\App\Http\Controllers\Admin\VisitorController::RANGES) as $range) {
            $this->actingAs($admin)->get(route('admin.visitors.index', ['range' => $range]))->assertOk();
        }
    }

    public function test_visitor_dashboard_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.visitors.index'))->assertForbidden();
    }

    public function test_old_visits_are_pruned(): void
    {
        $old = $this->seedVisit(['started_at' => now()->subDays(400), 'last_seen_at' => now()->subDays(400)]);
        $recent = $this->seedVisit();

        $this->artisan('model:prune', ['--model' => [Visit::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    public function test_account_deletion_erases_the_users_visits(): void
    {
        $user = User::factory()->create();
        $theirs = $this->seedVisit(['user_id' => $user->id]);
        $someoneElses = $this->seedVisit();

        \App\Http\Controllers\AccountDeletionController::performAccountDeletion($user);

        $this->assertModelMissing($theirs);
        $this->assertModelExists($someoneElses);
    }

    public function test_privacy_policy_explains_visit_analytics(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee(VisitTracker::VISITOR_COOKIE)
            ->assertSee(VisitTracker::VISIT_COOKIE)
            ->assertSee('ipinfo.io')
            ->assertSee('365 days');
    }

    private function seedVisit(array $overrides = []): Visit
    {
        return Visit::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'visitor_id' => (string) Str::uuid(),
            'landing_path' => '/',
            'channel' => 'direct',
            'device_type' => 'mobile',
            'browser' => 'Chrome',
            'os' => 'Android',
            'started_at' => now()->subHour(),
            'last_seen_at' => now()->subHour(),
        ], $overrides));
    }
}
