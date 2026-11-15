<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Support\Countries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Website visitor analytics: how many people visit, where they are, which
 * sites send them and which pages they land on. Every figure on the page is
 * scoped by the same date range and drill-down filters.
 */
class VisitorController extends Controller
{
    public const RANGES = [
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        '12m' => 'Last 12 months',
    ];

    public function index(Request $request)
    {
        $range = array_key_exists((string) $request->input('range'), self::RANGES) ? (string) $request->input('range') : '30d';
        [$from, $to, $prevFrom, $prevTo] = $this->window($range);
        $filters = $this->filters($request);

        $scoped = fn (Carbon $start, Carbon $end): Builder => $this->applyFilters(Visit::query(), $filters)
            ->where('started_at', '>=', $start)
            ->where('started_at', '<', $end);
        $current = fn (): Builder => $scoped($from, $to);

        $totals = $this->totals($current());
        $previous = $this->totals($scoped($prevFrom, $prevTo));

        $activeNow = Visit::where('last_seen_at', '>=', now()->subMinutes(5))->distinct()->count('visitor_id');

        $visits = $current()->with('user:id,name')->orderByDesc('started_at')->paginate(25)->withQueryString();

        return view('admin.visitors.index', [
            'range' => $range,
            'ranges' => self::RANGES,
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'totals' => $totals,
            'previous' => $previous,
            'activeNow' => $activeNow,
            'trend' => $this->trend($current(), $range, $from, $to),
            'countries' => $this->countries($current()),
            'cities' => $this->cities($current()),
            'channels' => $this->grouped($current(), 'channel'),
            'referrers' => $this->referrers($current()),
            'landingPages' => $this->landingPages($current()),
            'campaigns' => $this->campaigns($current()),
            'devices' => $this->grouped($current(), 'device_type'),
            'browsers' => $this->grouped($current(), 'browser', 6),
            'systems' => $this->grouped($current(), 'os', 6),
            'visits' => $visits,
        ]);
    }

    /**
     * Today compares with the same hours yesterday; other ranges with the
     * equally long period just before.
     *
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon} start, end (exclusive), previous start, previous end
     */
    private function window(string $range): array
    {
        $to = now()->addSecond();
        if ($range === 'today') {
            $from = now()->startOfDay();

            return [$from, $to, $from->copy()->subDay(), $to->copy()->subDay()];
        }

        $from = match ($range) {
            '7d' => now()->subDays(6)->startOfDay(),
            '90d' => now()->subDays(89)->startOfDay(),
            '12m' => now()->subDays(364)->startOfDay(),
            default => now()->subDays(29)->startOfDay(),
        };

        return [$from, $to, $from->copy()->subSeconds((int) abs($from->diffInSeconds($to))), $from];
    }

    /** @return array<string, string> */
    private function filters(Request $request): array
    {
        $landing = trim((string) $request->input('landing'));
        $source = strtolower(trim((string) $request->input('source')));
        $channel = (string) $request->input('channel');

        return array_filter([
            'country' => Countries::normalize($request->input('country')),
            'source' => $source !== '' ? mb_substr($source, 0, 191) : null,
            'channel' => array_key_exists($channel, Visit::CHANNELS) ? $channel : null,
            'landing' => str_starts_with($landing, '/') ? mb_substr($landing, 0, 500) : null,
        ]);
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['country'] ?? null, fn ($q, $v) => $q->where('country_code', $v))
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('referrer_host', $v))
            ->when($filters['channel'] ?? null, fn ($q, $v) => $q->where('channel', $v))
            ->when($filters['landing'] ?? null, fn ($q, $v) => $q->where('landing_path', $v));
    }

    /** @return array{visits: int, visitors: int, pageviews: int, bounces: int, new_visitors: int} */
    private function totals(Builder $query): array
    {
        $row = $query->toBase()->selectRaw(
            'COUNT(*) as visits, COUNT(DISTINCT visitor_id) as visitors, COALESCE(SUM(pageviews), 0) as pageviews,'
            . ' COALESCE(SUM(CASE WHEN pageviews = 1 THEN 1 ELSE 0 END), 0) as bounces,'
            . ' COUNT(DISTINCT CASE WHEN is_new_visitor = 1 THEN visitor_id END) as new_visitors'
        )->first();

        return array_map('intval', (array) $row);
    }

    /**
     * Visits per hour (today), per day (≤ 90 days) or per week (12 months).
     *
     * @return list<array{label: string, short: string, visits: int, pageviews: int}>
     */
    private function trend(Builder $query, string $range, Carbon $from, Carbon $to): array
    {
        if ($range === 'today') {
            $hourExpr = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%H', started_at) AS INTEGER)"
                : 'HOUR(started_at)';
            $rows = $query->toBase()
                ->selectRaw("$hourExpr as bucket, COUNT(*) as visits, COALESCE(SUM(pageviews), 0) as pageviews")
                ->groupBy('bucket')->get()->keyBy(fn ($r) => (int) $r->bucket);

            return collect(range(0, (int) now()->format('G')))->map(fn (int $h) => [
                'label' => sprintf('%02d:00–%02d:59', $h, $h),
                'short' => sprintf('%02d:00', $h),
                'visits' => (int) ($rows[$h]->visits ?? 0),
                'pageviews' => (int) ($rows[$h]->pageviews ?? 0),
            ])->all();
        }

        $rows = $query->toBase()
            ->selectRaw('DATE(started_at) as day, COUNT(*) as visits, COALESCE(SUM(pageviews), 0) as pageviews')
            ->groupBy('day')->get()->keyBy(fn ($r) => (string) $r->day);

        $buckets = [];
        for ($day = $from->copy()->startOfDay(); $day->lt($to); $day->addDay()) {
            $key = $range === '12m' ? $day->copy()->startOfWeek()->toDateString() : $day->toDateString();
            $buckets[$key] ??= [
                'label' => $range === '12m' ? 'Week of ' . $day->copy()->startOfWeek()->format('j M Y') : $day->format('D j M Y'),
                'short' => $range === '12m' ? $day->copy()->startOfWeek()->format('M Y') : $day->format('j M'),
                'visits' => 0,
                'pageviews' => 0,
            ];
            $row = $rows[$day->toDateString()] ?? null;
            $buckets[$key]['visits'] += (int) ($row->visits ?? 0);
            $buckets[$key]['pageviews'] += (int) ($row->pageviews ?? 0);
        }

        return array_values($buckets);
    }

    private function countries(Builder $query)
    {
        return $query->toBase()
            ->selectRaw('country_code, COUNT(*) as visits, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('country_code')->orderByDesc('visits')->limit(12)->get()
            ->map(fn ($r) => [
                'label' => Countries::name($r->country_code) ?? 'Unknown',
                'icon' => Countries::flag($r->country_code),
                'value' => (int) $r->visits,
                'meta' => number_format($r->visitors) . ' ' . str('visitor')->plural((int) $r->visitors),
                'filter' => $r->country_code ? ['country' => $r->country_code] : null,
            ]);
    }

    private function cities(Builder $query)
    {
        return $query->toBase()->whereNotNull('city')
            ->selectRaw('country_code, region, city, COUNT(*) as visits, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('country_code', 'region', 'city')->orderByDesc('visits')->limit(10)->get()
            ->map(fn ($r) => [
                'label' => $r->city,
                'icon' => Countries::flag($r->country_code),
                'value' => (int) $r->visits,
                'meta' => collect([$r->region !== $r->city ? $r->region : null, Countries::name($r->country_code)])->filter()->implode(', '),
                'filter' => $r->country_code ? ['country' => $r->country_code] : null,
            ]);
    }

    private function referrers(Builder $query)
    {
        return $query->toBase()->whereNotNull('referrer_host')
            ->selectRaw('referrer_host, MIN(channel) as channel, MAX(channel) as channel_max, COUNT(*) as visits, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('referrer_host')->orderByDesc('visits')->limit(12)->get()
            ->map(fn ($r) => [
                'label' => $r->referrer_host,
                'icon' => null,
                'value' => (int) $r->visits,
                // Name the channel only when all of this site's visits share one (e.g. not organic + paid Facebook).
                'meta' => collect([
                    $r->channel === $r->channel_max ? (Visit::CHANNELS[$r->channel] ?? ucfirst($r->channel)) : null,
                    number_format($r->visitors) . ' ' . str('visitor')->plural((int) $r->visitors),
                ])->filter()->implode(' · '),
                'filter' => ['source' => $r->referrer_host],
            ]);
    }

    private function landingPages(Builder $query)
    {
        return $query->toBase()
            ->selectRaw('landing_path, COUNT(*) as visits, COUNT(DISTINCT visitor_id) as visitors, COALESCE(SUM(pageviews), 0) as pageviews,'
                . ' COALESCE(SUM(CASE WHEN pageviews = 1 THEN 1 ELSE 0 END), 0) as bounces')
            ->groupBy('landing_path')->orderByDesc('visits')->limit(15)->get()
            ->map(fn ($r) => [
                'path' => $r->landing_path,
                'visits' => (int) $r->visits,
                'visitors' => (int) $r->visitors,
                'pages_per_visit' => $r->visits ? round($r->pageviews / $r->visits, 1) : 0,
                'bounce_rate' => $r->visits ? (int) round(100 * $r->bounces / $r->visits) : 0,
            ]);
    }

    private function campaigns(Builder $query)
    {
        return $query->toBase()
            ->where(fn ($q) => $q->whereNotNull('utm_source')->orWhereNotNull('utm_campaign'))
            ->selectRaw('utm_source, utm_medium, utm_campaign, COUNT(*) as visits, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')->orderByDesc('visits')->limit(10)->get();
    }

    private function grouped(Builder $query, string $column, int $limit = 10)
    {
        return $query->toBase()
            ->selectRaw("$column as name, COUNT(*) as visits")
            ->groupBy($column)->orderByDesc('visits')->limit($limit)->get()
            ->map(fn ($r) => [
                'label' => match ($column) {
                    'channel' => Visit::CHANNELS[$r->name] ?? ucfirst((string) $r->name),
                    'device_type' => ucfirst((string) ($r->name ?: 'unknown')),
                    default => $r->name ?: 'Unknown',
                },
                'icon' => null,
                'value' => (int) $r->visits,
                'meta' => null,
                'filter' => $column === 'channel' && $r->name ? ['channel' => $r->name] : null,
            ]);
    }
}
