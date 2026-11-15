<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Website visitor analytics
    |--------------------------------------------------------------------------
    |
    | Page views on the public website are grouped into visits and shown in
    | Admin → Visitors. Admins, bots, AJAX/JSON calls and the paths below are
    | never counted.
    |
    */

    'enabled' => env('VISITOR_TRACKING_ENABLED', true),

    // A visit ends after this many idle minutes.
    'session_minutes' => (int) env('VISITOR_SESSION_MINUTES', 30),

    // Visits older than this are deleted by the daily `model:prune` run.
    'retention_days' => (int) env('VISITOR_RETENTION_DAYS', 365),

    'excluded_paths' => [
        'admin', 'admin/*',
        'api/*',
        'up',
        'oauth/*', 'auth/*',
        'logout',
        'sitemap.xml',
        '.well-known/*',
        'otp/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Location lookup
    |--------------------------------------------------------------------------
    |
    | Country/city come from CDN headers when the site sits behind Cloudflare
    | or CloudFront. Otherwise the visitor's IP is looked up once per visit
    | (cached for a week) with the driver below: "ipinfo" (ipinfo.io; add a
    | free token for higher limits), "ipapi" (ipapi.co) or "none".
    |
    */

    'geo' => [
        'driver' => env('VISITOR_GEO_DRIVER', 'ipinfo'),
        'token' => env('VISITOR_GEO_TOKEN'),
        'timeout' => (float) env('VISITOR_GEO_TIMEOUT', 2),
        'cache_days' => 7,
    ],

];
