@extends('layouts.app')

@section('title', 'Admin Panel - Website Visitors')
@section('robots', 'noindex, nofollow')

@section('content')
@use('App\Support\Countries')
@use('Illuminate\Support\Arr')
@php
    $fmt = function ($n) {
        $n = (float) $n;
        if ($n >= 1_000_000) return rtrim(rtrim(number_format($n / 1_000_000, 1), '0'), '.') . 'M';
        if ($n >= 10_000) return rtrim(rtrim(number_format($n / 1_000, 1), '0'), '.') . 'K';
        return number_format($n);
    };

    $filterUrl = fn (array $add) => route('admin.visitors.index', array_merge(['range' => $range], $filters, $add));
    $removeUrl = fn (string $key) => route('admin.visitors.index', array_merge(['range' => $range], Arr::except($filters, $key)));
    $rangeUrl = fn (string $key) => route('admin.visitors.index', array_merge($filters, ['range' => $key]));

    $periodLabel = $range === 'today' ? 'same time yesterday' :'previous ' . strtolower(str_replace('Last ', '', $ranges[$range]));

    // Signed change vs the previous period; $upIsGood flips the colour for bounce rate.
    $delta = function ($current, $previous, bool $upIsGood = true, bool $points = false) {
        if ($points) {
            $change = round($current - $previous);
            $text = ($change > 0 ? '+' : '') . $change . ' pts';
        } else {
            if ($previous <= 0) return null;
            $change = round(100 * ($current - $previous) / $previous);
            $text = ($change > 0 ? '+' : '') . $change . '%';
        }
        if ($change == 0) return ['text' => 'No change', 'arrow' => '•', 'class' => 'text-slate-500'];
        $good = ($change > 0) === $upIsGood;
        return ['text' => $text, 'arrow' => $change > 0 ? '▲' : '▼', 'class' => $good ? 'text-emerald-700' : 'text-rose-700'];
    };

    $rate = fn ($part, $whole) => $whole > 0 ? 100 * $part / $whole : 0;
    $pagesPerVisit = fn ($t) => $t['visits'] > 0 ? $t['pageviews'] / $t['visits'] : 0;

    $tiles = [
        ['label' => 'Visits', 'value' => $fmt($totals['visits']), 'delta' => $delta($totals['visits'], $previous['visits'])],
        ['label' => 'Page views', 'value' => $fmt($totals['pageviews']), 'delta' => $delta($totals['pageviews'], $previous['pageviews'])],
        ['label' => 'Pages per visit', 'value' => number_format($pagesPerVisit($totals), 1), 'delta' => $delta($pagesPerVisit($totals), $pagesPerVisit($previous))],
        ['label' => 'Bounce rate', 'value' => round($rate($totals['bounces'], $totals['visits'])) . '%', 'hint' => 'Visits that saw one page',
            'delta' => $previous['visits'] > 0 ? $delta($rate($totals['bounces'], $totals['visits']), $rate($previous['bounces'], $previous['visits']), false, true) : null],
        ['label' => 'New visitors', 'value' => round($rate($totals['new_visitors'], $totals['visitors'])) . '%',
            'hint' => number_format($totals['new_visitors']) . ' first-time · ' . number_format(max(0, $totals['visitors'] - $totals['new_visitors'])) . ' returning'],
    ];

    $filterLabels = [
        'country' => fn ($v) => Countries::flag($v) . ' ' . (Countries::name($v) ?? $v),
        'source' => fn ($v) => 'From ' . $v,
        'channel' => fn ($v) => (\App\Models\Visit::CHANNELS[$v] ?? $v) . ' traffic',
        'landing' => fn ($v) => 'Landed on ' . $v,
    ];

    // Column chart scale: clean ticks, full numbers under 10,000.
    $trendMax = max(1, (int) collect($trend)->max('visits'));
    $rawStep = $trendMax / 4;
    $magnitude = 10 ** floor(log10($rawStep));
    $residual = $rawStep / $magnitude;
    $step = max(1, (int) (($residual <= 1 ? 1 : ($residual <= 2 ? 2 : ($residual <= 5 ? 5 : 10))) * $magnitude));
    $yMax = (int) (ceil($trendMax / $step) * $step);
    $ticks = range(0, $yMax, $step);
    $bucketCount = count($trend);
    $labelEvery = max(1, (int) ceil($bucketCount / 7));
    $trendTitle = match ($range) { 'today' => 'Visits per hour today', '12m' => 'Visits per week', default => 'Visits per day' };

    $channelBadge = [
        'direct' => 'bg-slate-100 text-slate-700',
        'search' => 'bg-sky-50 text-sky-800',
        'social' => 'bg-violet-50 text-violet-800',
        'ai' => 'bg-amber-50 text-amber-800',
        'referral' => 'bg-emerald-50 text-emerald-800',
        'email' => 'bg-rose-50 text-rose-800',
        'app' => 'bg-teal-50 text-teal-800',
        'campaign' => 'bg-orange-50 text-orange-800',
    ];
@endphp
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Website Visitors</h1>
            <p class="text-slate-500 mt-1">Who visits the website, where they are, which sites send them and the pages they land on. Admin accounts and bots are not counted.</p>
        </div>
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-700" title="Visitors with a page view in the last 5 minutes">
            <span class="relative flex h-2 w-2" aria-hidden="true">
                @if($activeNow > 0)<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>@endif
                <span class="relative inline-flex rounded-full h-2 w-2 {{ $activeNow > 0 ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
            </span>
            {{ number_format($activeNow) }} active now
        </div>
    </div>

    @include('admin.partials.tabs')

    {{-- Filters: date range first, then any drill-down chips. They scope everything below. --}}
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <div class="inline-flex flex-wrap gap-1 p-1 bg-white border border-slate-200 rounded-xl" role="group" aria-label="Date range">
            @foreach($ranges as $key => $label)
                <a href="{{ $rangeUrl($key) }}" @if($range === $key) aria-current="true" @endif
                   class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $range === $key ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
            @endforeach
        </div>
        @foreach($filters as $key => $value)
            <a href="{{ $removeUrl($key) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 text-white hover:bg-slate-700" title="Remove filter">
                {{ ($filterLabels[$key])($value) }} <span aria-hidden="true" class="opacity-70">✕</span><span class="sr-only">Remove filter</span>
            </a>
        @endforeach
        @if($filters)
            <a href="{{ route('admin.visitors.index', ['range' => $range]) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 underline">Clear all</a>
        @endif
    </div>

    @if($totals['visits'] === 0)
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-sm">
            No visits recorded {{ $range === 'today' ? 'today' : 'in this period' }}{{ $filters ? ' for these filters' : '' }}.
            Your own browsing isn't counted while you're signed in as an admin; open the site in a private window to check that tracking works.
        </div>
    @endif

    {{-- KPI row --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @php $visitorDelta = $delta($totals['visitors'], $previous['visitors']); @endphp
        <div class="col-span-2 md:col-span-3 lg:col-span-1 lg:row-span-1 bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl p-5">
            <div class="text-xs font-semibold text-slate-500">Unique visitors</div>
            <div class="text-5xl font-bold text-slate-900 mt-1">{{ $fmt($totals['visitors']) }}</div>
            @if($visitorDelta)
                <div class="mt-2 text-xs font-semibold {{ $visitorDelta['class'] }}">
                    <span aria-hidden="true">{{ $visitorDelta['arrow'] }}</span> {{ $visitorDelta['text'] }} <span class="text-slate-400 font-normal">vs {{ $periodLabel }}</span>
                </div>
            @endif
        </div>
        @foreach($tiles as $tile)
            <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl p-5">
                <div class="text-xs font-semibold text-slate-500">{{ $tile['label'] }}</div>
                <div class="text-2xl font-bold text-slate-900 mt-1">{{ $tile['value'] }}</div>
                @if(!empty($tile['delta']))
                    <div class="mt-1.5 text-[11px] font-semibold {{ $tile['delta']['class'] }}">
                        <span aria-hidden="true">{{ $tile['delta']['arrow'] }}</span> {{ $tile['delta']['text'] }} <span class="text-slate-400 font-normal">vs {{ $periodLabel }}</span>
                    </div>
                @endif
                @if(!empty($tile['hint']))
                    <div class="mt-1 text-[11px] text-slate-400">{{ $tile['hint'] }}</div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Trend: single series column chart with per-column hover/focus tooltip and a table view --}}
    <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl p-5 mb-6"
         x-data="{
            tip: null,
            data: @js($trend),
            tipStyle() {
                const n = this.data.length, x = (this.tip + 0.5) / n * 100;
                const shift = x < 15 ? '0%' : (x > 85 ? '-100%' : '-50%');
                return `left: ${x}%; transform: translateX(${shift});`;
            }
         }">
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-800">{{ $trendTitle }}</h2>
            <span class="text-[11px] text-slate-400">{{ $range === 'today' ? now()->format('j M Y') : $from->format('j M Y') . ' – ' . now()->format('j M Y') }}</span>
        </div>
        <div class="flex">
            <div class="relative w-10 shrink-0 h-52 text-[10px] text-slate-400 tabular-nums" aria-hidden="true">
                @foreach($ticks as $tick)
                    <span class="absolute right-2 translate-y-1/2" style="bottom: {{ $yMax ? 100 * $tick / $yMax : 0 }}%">{{ $fmt($tick) }}</span>
                @endforeach
            </div>
            <div class="relative flex-1 min-w-0">
                <div class="relative h-52">
                    @foreach($ticks as $tick)
                        <div class="absolute inset-x-0 border-t {{ $tick === 0 ? 'border-slate-300' : 'border-slate-100' }}" style="bottom: {{ $yMax ? 100 * $tick / $yMax : 0 }}%" aria-hidden="true"></div>
                    @endforeach
                    <div class="absolute inset-0 flex items-end" role="group" aria-label="{{ $trendTitle }}">
                        @foreach($trend as $i => $bucket)
                            @php $pct = $yMax ? 100 * $bucket['visits'] / $yMax : 0; @endphp
                            <button type="button"
                                    class="group relative flex-1 h-full flex items-end justify-center px-px focus:outline-none"
                                    @mouseenter="tip = {{ $i }}" @mouseleave="tip = null" @focus="tip = {{ $i }}" @blur="tip = null"
                                    aria-label="{{ $bucket['label'] }}: {{ number_format($bucket['visits']) }} visits, {{ number_format($bucket['pageviews']) }} page views">
                                @if($bucket['visits'] > 0)
                                    <span class="block w-full max-w-[24px] rounded-t bg-emerald-600 group-hover:bg-emerald-500 group-focus-visible:bg-emerald-500 group-focus-visible:ring-2 group-focus-visible:ring-emerald-300"
                                          style="height: max(2px, {{ round($pct, 2) }}%)"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    <div x-show="tip !== null" x-cloak class="absolute top-0 z-10 pointer-events-none" :style="tipStyle()">
                        <div class="bg-white border border-slate-200 shadow-lg rounded-xl px-3 py-2 text-xs whitespace-nowrap">
                            <div class="text-slate-500" x-text="data[tip]?.label"></div>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="inline-block w-3 h-0.5 rounded bg-emerald-600" aria-hidden="true"></span>
                                <span class="font-bold text-slate-900 text-sm" x-text="data[tip]?.visits.toLocaleString()"></span>
                                <span class="text-slate-500">visits</span>
                            </div>
                            <div class="text-slate-500 mt-0.5"><span class="font-semibold text-slate-700" x-text="data[tip]?.pageviews.toLocaleString()"></span> page views</div>
                        </div>
                    </div>
                </div>
                <div class="relative h-5 mt-1 text-[10px] text-slate-400" aria-hidden="true">
                    @foreach($trend as $i => $bucket)
                        @if($i % $labelEvery === 0)
                            <span class="absolute -translate-x-1/2 whitespace-nowrap" style="left: {{ 100 * ($i + 0.5) / max(1, $bucketCount) }}%">{{ $bucket['short'] }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        <details class="mt-3">
            <summary class="text-xs font-semibold text-slate-500 cursor-pointer hover:text-slate-700">Show as table</summary>
            <div class="mt-2 max-h-64 overflow-y-auto border border-slate-100 rounded-xl">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-slate-500 sticky top-0">
                        <tr><th class="text-left px-3 py-2">{{ $range === 'today' ? 'Hour' : ($range === '12m' ? 'Week' : 'Day') }}</th><th class="text-right px-3 py-2">Visits</th><th class="text-right px-3 py-2">Page views</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 tabular-nums">
                        @foreach($trend as $bucket)
                            <tr><td class="px-3 py-1.5 text-slate-700">{{ $bucket['label'] }}</td><td class="px-3 py-1.5 text-right">{{ number_format($bucket['visits']) }}</td><td class="px-3 py-1.5 text-right">{{ number_format($bucket['pageviews']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>

    {{-- Locations --}}
    <div class="grid md:grid-cols-2 gap-4 mb-6">
        @include('admin.visitors.partials.ranked', ['title' => '🌍 Countries', 'rows' => $countries, 'total' => $totals['visits'], 'empty' => 'No locations yet.'])
        @include('admin.visitors.partials.ranked', ['title' => '📍 Cities', 'rows' => $cities, 'total' => $totals['visits'], 'empty' => 'No cities yet. City needs a location lookup or Cloudflare visitor-location headers.'])
    </div>

    {{-- Sources --}}
    <div class="grid md:grid-cols-2 gap-4 mb-6">
        @include('admin.visitors.partials.ranked', ['title' => '🔗 Referring sites', 'rows' => $referrers, 'total' => $totals['visits'], 'empty' => 'No referring sites yet. Everyone arrived directly.'])
        @include('admin.visitors.partials.ranked', ['title' => '🧭 Channels', 'rows' => $channels, 'total' => $totals['visits']])
    </div>

    {{-- Landing pages --}}
    @php $landingMax = max(1, (int) $landingPages->max('visits')); @endphp
    <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl overflow-hidden mb-6">
        <div class="px-5 pt-5 pb-3 flex items-baseline justify-between">
            <h2 class="text-sm font-bold text-slate-800">🛬 Landing pages</h2>
            <span class="text-[11px] text-slate-400">First page of each visit</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 border-y border-slate-200 text-slate-500 uppercase text-[11px] font-bold tracking-wider">
                    <tr>
                        <th class="px-5 py-3">Page</th>
                        <th class="px-3 py-3 text-right">Visits</th>
                        <th class="px-3 py-3 text-right">Visitors</th>
                        <th class="px-3 py-3 text-right">Pages / visit</th>
                        <th class="px-5 py-3 text-right">Bounce rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 tabular-nums">
                    @forelse($landingPages as $page)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-2.5 max-w-[420px]">
                                <div class="flex items-center gap-2">
                                    <a href="{{ $filterUrl(['landing' => $page['path']]) }}" class="font-semibold text-slate-800 hover:text-emerald-700 hover:underline truncate" title="Show only visits that landed here">{{ $page['path'] }}</a>
                                    <a href="{{ url($page['path']) }}" target="_blank" rel="noopener" class="text-slate-400 hover:text-emerald-600 shrink-0" title="Open page">↗</a>
                                </div>
                                <div class="mt-1 h-1 rounded-full bg-emerald-50" aria-hidden="true">
                                    <div class="h-1 rounded-full bg-emerald-600" style="width: max(2px, {{ round(100 * $page['visits'] / $landingMax, 2) }}%)"></div>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold text-slate-800">{{ number_format($page['visits']) }}</td>
                            <td class="px-3 py-2.5 text-right text-slate-600">{{ number_format($page['visitors']) }}</td>
                            <td class="px-3 py-2.5 text-right text-slate-600">{{ number_format($page['pages_per_visit'], 1) }}</td>
                            <td class="px-5 py-2.5 text-right text-slate-600">{{ $page['bounce_rate'] }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No landing pages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Devices --}}
    <div class="grid md:grid-cols-3 gap-4 mb-6">
        @include('admin.visitors.partials.ranked', ['title' => '📱 Devices', 'rows' => $devices, 'total' => $totals['visits']])
        @include('admin.visitors.partials.ranked', ['title' => '🌐 Browsers', 'rows' => $browsers, 'total' => $totals['visits']])
        @include('admin.visitors.partials.ranked', ['title' => '💻 Operating systems', 'rows' => $systems, 'total' => $totals['visits']])
    </div>

    {{-- Campaigns (utm_* links) --}}
    @if($campaigns->isNotEmpty())
        <div class="bg-white/80 backdrop-blur-md border border-slate-200 rounded-2xl overflow-hidden mb-6">
            <div class="px-5 pt-5 pb-3">
                <h2 class="text-sm font-bold text-slate-800">📣 Campaign links</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Visits from links tagged with utm_source / utm_medium / utm_campaign</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 border-y border-slate-200 text-slate-500 uppercase text-[11px] font-bold tracking-wider">
                        <tr><th class="px-5 py-3">Source</th><th class="px-3 py-3">Medium</th><th class="px-3 py-3">Campaign</th><th class="px-3 py-3 text-right">Visits</th><th class="px-5 py-3 text-right">Visitors</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 tabular-nums">
                        @foreach($campaigns as $c)
                            <tr>
                                <td class="px-5 py-2.5 font-semibold text-slate-800">{{ $c->utm_source ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-slate-600">{{ $c->utm_medium ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-slate-600">{{ $c->utm_campaign ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-right font-bold text-slate-800">{{ number_format($c->visits) }}</td>
                                <td class="px-5 py-2.5 text-right text-slate-600">{{ number_format($c->visitors) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Recent visits --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl overflow-hidden">
        <div class="px-5 pt-5 pb-3 flex items-baseline justify-between">
            <h2 class="text-sm font-bold text-slate-800">🕒 Recent visits</h2>
            <span class="text-[11px] text-slate-400">{{ number_format($visits->total()) }} in this period</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 border-y border-slate-200 text-slate-500 uppercase text-[11px] font-bold tracking-wider">
                    <tr>
                        <th class="px-5 py-3">When</th>
                        <th class="px-3 py-3">Location</th>
                        <th class="px-3 py-3">Came from</th>
                        <th class="px-3 py-3">Landing page</th>
                        <th class="px-3 py-3 text-right">Pages</th>
                        <th class="px-3 py-3">Device</th>
                        <th class="px-5 py-3">Visitor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($visits as $visit)
                        <tr class="hover:bg-slate-50/50 align-top">
                            <td class="px-5 py-3 whitespace-nowrap text-slate-600" title="{{ $visit->started_at->format('j M Y, H:i') }} UTC">
                                <div class="font-semibold text-slate-800">{{ $visit->started_at->diffForHumans() }}</div>
                                <div class="text-slate-400">{{ $visit->started_at->format('j M, H:i') }}</div>
                            </td>
                            <td class="px-3 py-3 text-slate-700">
                                <span aria-hidden="true">{{ Countries::flag($visit->country_code) }}</span>
                                {{ $visit->locationLabel() }}
                            </td>
                            <td class="px-3 py-3 max-w-[220px]">
                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold {{ $channelBadge[$visit->channel] ?? 'bg-slate-100 text-slate-700' }}">{{ $visit->channelLabel() }}</span>
                                @if($visit->referrer_host)
                                    <div class="mt-1 text-slate-700 truncate" title="{{ $visit->referrer_url }}">{{ $visit->referrer_host }}</div>
                                @endif
                                @if($visit->utm_source || $visit->utm_campaign)
                                    <div class="mt-0.5 text-slate-400 truncate">{{ collect([$visit->utm_source, $visit->utm_medium, $visit->utm_campaign])->filter()->implode(' / ') }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 max-w-[260px]">
                                <a href="{{ url($visit->landing_path) }}" target="_blank" rel="noopener" class="font-semibold text-slate-800 hover:text-emerald-700 hover:underline break-all">{{ $visit->landing_path }}</a>
                                @if($visit->pageviews > 1 && $visit->last_path && $visit->last_path !== $visit->landing_path)
                                    <div class="text-slate-400 mt-0.5 break-all">Last: {{ $visit->last_path }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-slate-800 tabular-nums">{{ $visit->pageviews }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">
                                <div>{{ ucfirst($visit->device_type ?? 'unknown') }}</div>
                                <div class="text-slate-400">{{ $visit->browser }} · {{ $visit->os }}</div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold {{ $visit->is_new_visitor ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $visit->is_new_visitor ? 'New' : 'Returning' }}</span>
                                @if($visit->user)
                                    <div class="mt-1 text-slate-700">{{ $visit->user->name }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">No visits in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($visits->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">{{ $visits->links() }}</div>
        @endif
    </div>
</div>
@endsection
