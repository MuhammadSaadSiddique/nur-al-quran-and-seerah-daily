<?php

namespace App\Services;

use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Approximate visitor location (country, region, city). CDN headers are used
 * when present; otherwise the IP is looked up once and cached, keyed by a
 * hash so raw IPs never reach the cache or the database.
 */
class VisitorGeoLocator
{
    private const EMPTY = ['country_code' => null, 'region' => null, 'city' => null];

    /** @return array{country_code: ?string, region: ?string, city: ?string} */
    public function locate(Request $request): array
    {
        $fromHeaders = $this->fromHeaders($request);
        if ($fromHeaders['country_code'] !== null && $fromHeaders['city'] !== null) {
            return $fromHeaders;
        }

        $ip = $this->clientIp($request);
        if ($ip === null) {
            return $fromHeaders;
        }

        $looked = $this->lookup($ip);
        if ($fromHeaders['country_code'] !== null
            && $looked['country_code'] !== null
            && $looked['country_code'] !== $fromHeaders['country_code']) {
            return $fromHeaders; // CDN and lookup disagree: trust the CDN, skip the city.
        }

        return [
            'country_code' => $fromHeaders['country_code'] ?? $looked['country_code'],
            'region' => $fromHeaders['region'] ?? $looked['region'],
            'city' => $fromHeaders['city'] ?? $looked['city'],
        ];
    }

    /** Public client IP, or null for private/reserved addresses (local dev, LAN). */
    public function clientIp(Request $request): ?string
    {
        $candidates = [$request->header('CF-Connecting-IP'), $request->header('True-Client-IP'), $request->ip()];

        foreach ($candidates as $ip) {
            $ip = trim((string) $ip);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }

        return null;
    }

    /** @return array{country_code: ?string, region: ?string, city: ?string} */
    private function fromHeaders(Request $request): array
    {
        $country = Countries::normalize(
            $request->header('CF-IPCountry')
            ?? $request->header('CloudFront-Viewer-Country')
            ?? $request->header('X-Vercel-IP-Country')
            ?? $request->header('X-Country-Code')
        );

        if ($country === null) {
            return self::EMPTY;
        }

        return [
            'country_code' => $country,
            'region' => $this->text($request->header('CF-Region') ?? $request->header('CloudFront-Viewer-Country-Region-Name') ?? $request->header('X-Vercel-IP-Country-Region')),
            'city' => $this->text(rawurldecode((string) ($request->header('CF-IPCity') ?? $request->header('CloudFront-Viewer-City') ?? $request->header('X-Vercel-IP-City')))),
        ];
    }

    /** @return array{country_code: ?string, region: ?string, city: ?string} */
    private function lookup(string $ip): array
    {
        $driver = (string) config('visitors.geo.driver', 'ipinfo');
        if (!in_array($driver, ['ipinfo', 'ipapi'], true)) {
            return self::EMPTY;
        }

        $key = 'visitor-geo:' . hash_hmac('sha256', $ip, (string) config('app.key'));
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $result = self::EMPTY;
        try {
            $result = $driver === 'ipapi' ? $this->ipapi($ip) : $this->ipinfo($ip);
        } catch (Throwable $e) {
            Log::warning('Visitor geo lookup failed', ['driver' => $driver, 'error' => $e->getMessage()]);
        }

        // Cache misses briefly so a failing or rate-limited service isn't hammered.
        $ttl = $result['country_code'] !== null
            ? now()->addDays(max(1, (int) config('visitors.geo.cache_days', 7)))
            : now()->addHour();
        Cache::put($key, $result, $ttl);

        return $result;
    }

    private function ipinfo(string $ip): array
    {
        $response = $this->http()->get('https://ipinfo.io/' . urlencode($ip) . '/json', array_filter([
            'token' => config('visitors.geo.token'),
        ]));
        $data = $response->successful() ? (array) $response->json() : [];

        return [
            'country_code' => Countries::normalize($data['country'] ?? null),
            'region' => $this->text($data['region'] ?? null),
            'city' => $this->text($data['city'] ?? null),
        ];
    }

    private function ipapi(string $ip): array
    {
        $response = $this->http()->get('https://ipapi.co/' . urlencode($ip) . '/json/', array_filter([
            'key' => config('visitors.geo.token'),
        ]));
        $data = $response->successful() ? (array) $response->json() : [];
        if (!empty($data['error'])) {
            return self::EMPTY;
        }

        return [
            'country_code' => Countries::normalize($data['country_code'] ?? null),
            'region' => $this->text($data['region'] ?? null),
            'city' => $this->text($data['city'] ?? null),
        ];
    }

    private function http()
    {
        return Http::timeout(max(0.5, (float) config('visitors.geo.timeout', 2)))
            ->connectTimeout(max(0.5, (float) config('visitors.geo.timeout', 2)))
            ->acceptJson()
            ->withUserAgent('VisitorAnalytics/1.0');
    }

    private function text(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : mb_substr($value, 0, 120);
    }
}
