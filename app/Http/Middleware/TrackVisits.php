<?php

namespace App\Http\Middleware;

use App\Services\VisitTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Counts public page views for Admin → Visitors. Cookies are set while the
 * response is built; the database write (and any location lookup) happens in
 * terminate(), after the page has been sent.
 */
class TrackVisits
{
    private const ATTRIBUTE = '_visit_tracking';

    public function __construct(private readonly VisitTracker $tracker)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($this->tracker->shouldTrack($request, $response)) {
                $request->attributes->set(self::ATTRIBUTE, $this->tracker->begin($request, $response));
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $pending = $request->attributes->get(self::ATTRIBUTE);
        if (is_array($pending)) {
            $this->tracker->record($request, $pending);
        }
    }
}
