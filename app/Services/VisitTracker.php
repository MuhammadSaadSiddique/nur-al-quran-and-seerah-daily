<?php

namespace App\Services;

use App\Models\Visit;
use App\Support\TrafficSource;
use App\Support\UserAgent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Groups public page views into visits. A visit starts on the first page
 * view, when the previous one has been idle for `visitors.session_minutes`,
 * or when the visitor arrives again from a different source (search, social
 * post, campaign link). Two first-party cookies hold the anonymous visitor id
 * and the current visit.
 */
class VisitTracker
{
    public const VISITOR_COOKIE = 'ee_vid';
    public const VISIT_COOKIE = 'ee_visit';
    private const VISITOR_COOKIE_MINUTES = 60 * 24 * 730;

    public function __construct(private readonly VisitorGeoLocator $geo)
    {
    }

    public function shouldTrack(Request $request, Response $response): bool
    {
        if (!config('visitors.enabled', true) || !$request->isMethod('GET')) {
            return false;
        }

        if ($response->getStatusCode() !== 200
            || !str_starts_with(strtolower((string) $response->headers->get('Content-Type')), 'text/html')) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson() || $this->isPrefetch($request)) {
            return false;
        }

        if ($request->is(...config('visitors.excluded_paths', []))) {
            return false;
        }

        if (UserAgent::isBot($request->userAgent())) {
            return false;
        }

        // Admins browsing their own site would skew the numbers.
        return !($request->user()?->is_admin);
    }

    /**
     * Decide whether this page view starts or continues a visit and set the
     * cookies on the response. Returns what record() needs after the response.
     *
     * @return array<string, mixed>
     */
    public function begin(Request $request, Response $response): array
    {
        $existingVisitor = $this->validUuid($request->cookie(self::VISITOR_COOKIE));
        $visitorId = $existingVisitor ?? (string) Str::uuid();

        [$activeVisit, $activeKey] = $this->parseVisitCookie($request->cookie(self::VISIT_COOKIE));
        $ownHosts = [$request->getHost(), parse_url((string) config('app.url'), PHP_URL_HOST)];
        $source = TrafficSource::classify($request->headers->get('referer'), $ownHosts, $request->query());

        // Internal navigation (or a reload) continues the visit; a fresh external source starts a new one.
        $continues = $activeVisit !== null && ($source['channel'] === 'direct' || $source['key'] === $activeKey);
        $visitUuid = $continues ? $activeVisit : (string) Str::uuid();
        $visitKey = $continues ? $activeKey : $source['key'];

        $response->headers->setCookie(cookie(self::VISITOR_COOKIE, $visitorId, self::VISITOR_COOKIE_MINUTES));
        $response->headers->setCookie(cookie(self::VISIT_COOKIE, $visitUuid . '|' . $visitKey, max(1, (int) config('visitors.session_minutes', 30))));

        return [
            'continues' => $continues,
            'visit_uuid' => $visitUuid,
            'visitor_id' => $visitorId,
            'is_new_visitor' => $existingVisitor === null,
            'path' => $this->path($request),
            'source' => $source,
            'user_id' => $request->user()?->id,
            'at' => now(),
        ];
    }

    /** Persist the page view. Runs after the response is sent; never throws. */
    public function record(Request $request, array $pending): void
    {
        try {
            if ($pending['continues'] && $this->touch($pending)) {
                return;
            }

            $this->create($request, $pending);
        } catch (UniqueConstraintViolationException) {
            // Two tabs opened the same new visit at once: count this as a page view of it.
            $this->touch($pending);
        } catch (Throwable $e) {
            Log::warning('Visit tracking failed', ['error' => $e->getMessage()]);
        }
    }

    private function touch(array $pending): bool
    {
        $changes = [
            'pageviews' => DB::raw('pageviews + 1'),
            'last_path' => $pending['path'],
            'last_seen_at' => $pending['at'],
        ];
        if ($pending['user_id']) {
            $changes['user_id'] = $pending['user_id'];
        }

        return Visit::where('uuid', $pending['visit_uuid'])->update($changes) > 0;
    }

    private function create(Request $request, array $pending): void
    {
        $source = $pending['source'];

        Visit::create([
            'uuid' => $pending['visit_uuid'],
            'visitor_id' => $pending['visitor_id'],
            'user_id' => $pending['user_id'],
            'is_new_visitor' => $pending['is_new_visitor'],
            'landing_path' => $pending['path'],
            'last_path' => $pending['path'],
            'pageviews' => 1,
            'referrer_url' => $source['referrer_url'],
            'referrer_host' => $source['referrer_host'],
            'channel' => $source['channel'],
            'utm_source' => $source['utm_source'],
            'utm_medium' => $source['utm_medium'],
            'utm_campaign' => $source['utm_campaign'],
            ...$this->geo->locate($request),
            ...UserAgent::parse($request->userAgent()),
            'started_at' => $pending['at'],
            'last_seen_at' => $pending['at'],
        ]);
    }

    private function path(Request $request): string
    {
        return mb_substr('/' . ltrim($request->path(), '/'), 0, 500);
    }

    /** @return array{0: ?string, 1: ?string} */
    private function parseVisitCookie(mixed $value): array
    {
        if (!is_string($value) || !str_contains($value, '|')) {
            return [null, null];
        }
        [$uuid, $key] = explode('|', $value, 2);
        $uuid = $this->validUuid($uuid);

        return $uuid ? [$uuid, $key] : [null, null];
    }

    private function validUuid(mixed $value): ?string
    {
        return is_string($value) && Str::isUuid($value) ? strtolower($value) : null;
    }

    private function isPrefetch(Request $request): bool
    {
        $purpose = strtolower((string) ($request->header('Sec-Purpose') ?? $request->header('Purpose') ?? $request->header('X-Moz')));

        return str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender');
    }
}
