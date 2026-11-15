@extends('layouts.app')

@section('title', 'Admin Panel - Requirements Survey')
@section('robots', 'noindex, nofollow')

@push('styles')
<style>
    .viz-root {
        --surface-1: #ffffff;
        --text-primary: #0b0b0b;
        --text-secondary: #52514e;
        --text-muted: #898781;
        --baseline: #c3c2b7;
        --bar-single: #059669;      /* one series per chart: brand emerald, 3.8:1 on white */
        --series-love: #2a78d6;     /* categorical slot 1 */
        --series-try: #eb6834;      /* categorical slot 2 */
        --series-meh: #1baf7a;      /* categorical slot 3, under 3:1, so every value is also printed in the table */
    }
</style>
@endpush

@section('content')
@php
    $reactionMeta = [
        'love' => ['label' => 'Love it', 'var' => '--series-love'],
        'try' => ['label' => 'Want to try', 'var' => '--series-try'],
        'meh' => ['label' => 'Not for me', 'var' => '--series-meh'],
    ];
    $nps = $summary['nps'];
    $npsMax = max(1, max($nps['distribution']));
@endphp
<div class="max-w-7xl mx-auto animate-fadeIn viz-root"
    x-data="{
        tip: null,
        copied: false,
        show(e) { const r = e.currentTarget.getBoundingClientRect(); this.tip = { text: e.currentTarget.dataset.tip, x: r.left + r.width / 2, y: r.top }; },
        hide() { this.tip = null; },
        copy() { navigator.clipboard?.writeText(@js($surveyUrl)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1800); }); }
    }">

    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Requirements Survey</h1>
            <p class="text-slate-500 mt-1">What people need, which features they love, and what they want built next.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="copy()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm rounded-xl transition-all">
                <span x-show="!copied">🔗 Copy survey link</span>
                <span x-show="copied" x-cloak>✅ Copied</span>
            </button>
            <a href="{{ $surveyUrl }}" target="_blank" rel="noopener" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm rounded-xl transition-all">Open survey ↗</a>
            <a href="{{ route('admin.survey.export') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-xl transition-all">⬇ Export CSV</a>
        </div>
    </div>

    @include('admin.partials.tabs')

    {{-- Filter row --}}
    <form method="GET" action="{{ route('admin.survey.index') }}" class="mb-6 p-4 bg-white/80 border border-slate-200 rounded-2xl flex flex-wrap gap-3 items-center">
        <label for="role-filter" class="text-xs font-bold uppercase tracking-wider text-slate-500">Show answers from</label>
        <select id="role-filter" name="role" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-500">
            <option value="">Everyone</option>
            @foreach($roles as $value => [$emoji, $label])
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $emoji }} {{ $label }}</option>
            @endforeach
        </select>
        @if(request()->filled('role'))
            <a href="{{ route('admin.survey.index') }}" class="text-xs text-rose-500 font-bold hover:underline">Reset</a>
        @endif
    </form>

    {{-- Stat tiles --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-8">
        @foreach([
            ['Responses', number_format($summary['total']), null],
            ['In the last 7 days', number_format($summary['last_7_days']), null],
            ['Net Promoter Score', $nps['score'] === null ? '–' : ($nps['score'] > 0 ? '+' : '') . $nps['score'], $nps['average'] === null ? 'No scores yet' : 'Average ' . $nps['average'] . ' / 10'],
            ['Wrote a wish', number_format($summary['with_wish']), null],
            ['Want updates', number_format($summary['want_updates']), 'Left an email to hear back'],
        ] as [$label, $value, $sub])
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                <p class="text-3xl font-black text-slate-900 mt-1">{{ $value }}</p>
                @if($sub)<p class="text-[11px] text-slate-400 mt-0.5">{{ $sub }}</p>@endif
            </div>
        @endforeach
    </div>

    @if($summary['total'] === 0)
        <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center mb-8">
            <div class="text-4xl mb-3">🗳️</div>
            <h2 class="text-lg font-black text-slate-800">No responses yet</h2>
            <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">Share the survey link in an announcement, on social media, or inside the Android app (add <code class="text-xs bg-slate-100 px-1 rounded">?from=android</code> to tag those responses).</p>
            <button type="button" @click="copy()" class="mt-4 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-xl">
                <span x-show="!copied">🔗 Copy survey link</span><span x-show="copied" x-cloak>✅ Copied</span>
            </button>
        </div>
    @else
        {{-- Feature reactions --}}
        <section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-lg font-black text-slate-800">How people react to current features</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Share of reactions per feature, sorted by how many loved it. “Want to try” usually means they hadn't found it yet.</p>
                </div>
                <div class="flex flex-wrap gap-4 text-xs font-semibold" style="color: var(--text-secondary)">
                    @foreach($reactionMeta as $meta)
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-[3px]" style="background: var({{ $meta['var'] }})"></span>{{ $meta['label'] }}</span>
                    @endforeach
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider" style="color: var(--text-muted)">
                            <th class="font-bold pb-2 pr-4">Feature</th>
                            <th class="font-bold pb-2 pr-4 w-[38%]">Reactions</th>
                            @foreach($reactionMeta as $meta)
                                <th class="font-bold pb-2 px-2 text-right whitespace-nowrap">{{ $meta['label'] }}</th>
                            @endforeach
                            <th class="font-bold pb-2 pl-2 text-right whitespace-nowrap">Love rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary['features'] as $feature)
                            @php
                                $segments = array_filter($feature['counts']);
                                $lastKey = array_key_last($segments);
                                $tip = $feature['title'] . ': ' . collect($reactionMeta)->map(fn ($m, $k) => $m['label'] . ' ' . $feature['counts'][$k])->implode(' · ');
                            @endphp
                            <tr class="border-t border-slate-100">
                                <td class="py-3 pr-4 font-semibold whitespace-nowrap" style="color: var(--text-primary)">{{ $feature['emoji'] }} {{ $feature['title'] }}</td>
                                <td class="py-3 pr-4">
                                    @if($feature['reacted'] > 0)
                                        <div class="flex gap-[2px] h-3.5 py-0 cursor-default outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 rounded"
                                            tabindex="0" data-tip="{{ $tip }}" aria-label="{{ $tip }}"
                                            @mouseenter="show($event)" @mouseleave="hide()" @focus="show($event)" @blur="hide()">
                                            @foreach($segments as $key => $count)
                                                <div class="h-full {{ $key === $lastKey ? 'rounded-r-[4px]' : '' }}" style="flex: {{ $count }} 1 0; background: var({{ $reactionMeta[$key]['var'] }})"></div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs" style="color: var(--text-muted)">No reactions yet</span>
                                    @endif
                                </td>
                                @foreach(array_keys($reactionMeta) as $key)
                                    <td class="py-3 px-2 text-right tabular-nums" style="color: var(--text-secondary)">{{ $feature['counts'][$key] }}</td>
                                @endforeach
                                <td class="py-3 pl-2 text-right font-bold tabular-nums" style="color: var(--text-primary)">{{ $feature['reacted'] ? $feature['love_rate'] . '%' : '–' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Choice questions --}}
        <div class="grid lg:grid-cols-2 gap-6 mb-6">
            @foreach(['wishlist', 'goals', 'role', 'platform', 'daily_time', 'languages'] as $key)
                @php
                    $question = $summary['questions'][$key];
                    $topCount = max(1, $question['rows'][0]['count'] ?? 0);
                @endphp
                <section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6">
                    <h2 class="text-base font-black text-slate-800">{{ $question['title'] }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5 mb-4">
                        {{ $question['type'] === 'multi' ? 'Several picks per person, so shares add up to more than 100%.' : 'One answer per person.' }}
                        Percent is the share of all {{ number_format($summary['total']) }} respondents.
                    </p>
                    <div class="space-y-1">
                        @foreach($question['rows'] as $row)
                            @php $tip = $row['label'] . ': ' . $row['count'] . ' ' . \Illuminate\Support\Str::plural('person', $row['count']) . ' (' . $row['percent'] . '%)'; @endphp
                            <div class="grid grid-cols-[minmax(0,1fr)_5rem_auto] sm:grid-cols-[minmax(0,1fr)_8rem_auto] items-center gap-3 py-1.5 px-1 rounded-lg hover:bg-slate-50 outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                                tabindex="0" data-tip="{{ $tip }}" aria-label="{{ $tip }}"
                                @mouseenter="show($event)" @mouseleave="hide()" @focus="show($event)" @blur="hide()">
                                <span class="text-sm leading-snug" style="color: var(--text-primary)">{{ $row['emoji'] }} {{ $row['label'] }}</span>
                                <div class="h-3.5 border-l" style="border-color: var(--baseline)">
                                    @if($row['count'] > 0)
                                        <div class="h-full rounded-r-[4px]" style="width: {{ max(2, round($row['count'] / $topCount * 100)) }}%; background: var(--bar-single)"></div>
                                    @endif
                                </div>
                                <span class="text-xs tabular-nums text-right w-20" style="color: var(--text-secondary)">
                                    <span class="font-bold" style="color: var(--text-primary)">{{ $row['count'] }}</span> · {{ $row['percent'] }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        {{-- NPS distribution --}}
        <section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-5">
                <div>
                    <h2 class="text-base font-black text-slate-800">How likely people are to recommend the app (0 to 10)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Net Promoter Score = % scoring 9–10 minus % scoring 0–6.</p>
                </div>
                <p class="text-xs font-semibold" style="color: var(--text-secondary)">
                    👍 {{ $nps['promoters'] }} promoters · 😐 {{ $nps['passives'] }} passives · 👎 {{ $nps['detractors'] }} detractors
                </p>
            </div>
            <div class="grid grid-cols-11 gap-1.5 sm:gap-3 items-end h-32 border-b" style="border-color: var(--baseline)">
                @foreach($nps['distribution'] as $score => $count)
                    @php $tip = 'Score ' . $score . ': ' . $count . ' ' . \Illuminate\Support\Str::plural('person', $count); @endphp
                    <div class="h-full flex items-end justify-center rounded-t-md hover:bg-slate-50 outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 cursor-default"
                        tabindex="0" data-tip="{{ $tip }}" aria-label="{{ $tip }}"
                        @mouseenter="show($event)" @mouseleave="hide()" @focus="show($event)" @blur="hide()">
                        @if($count > 0)
                            <div class="w-full max-w-[24px] rounded-t-[4px]" style="height: {{ round($count / $npsMax * 100) }}%; background: var(--bar-single)"></div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-11 gap-1.5 sm:gap-3 mt-2 text-center">
                @foreach($nps['distribution'] as $score => $count)
                    <div>
                        <p class="text-xs font-bold" style="color: var(--text-primary)">{{ $score }}</p>
                        <p class="text-[11px] tabular-nums" style="color: var(--text-muted)">{{ $count }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Every participant, with their full answers one click away --}}
    <section id="participants" class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4">
            <div>
                <h2 class="text-base font-black text-slate-800">Participants</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ number_format($participants->total()) }} {{ \Illuminate\Support\Str::plural('response', $participants->total()) }}{{ request()->filled('role') ? ' for this role' : '' }}. Click a row to see everything they answered.</p>
            </div>
            <form method="GET" action="{{ route('admin.survey.index') }}#participants" class="flex gap-2">
                @if(request()->filled('role'))<input type="hidden" name="role" value="{{ request('role') }}">@endif
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or email" aria-label="Search participants"
                    class="w-56 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 focus:outline-none focus:border-emerald-500">
                <button class="px-3 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl">Search</button>
                @if(request()->filled('q'))
                    <a href="{{ route('admin.survey.index', request()->only('role')) }}#participants" class="self-center text-xs text-rose-500 font-bold hover:underline">Clear</a>
                @endif
            </form>
        </div>

        @if($participants->isEmpty())
            <p class="text-sm text-slate-400">{{ request()->filled('q') ? 'Nobody matches that search.' : 'No responses yet.' }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[760px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <th class="font-bold pb-2 pr-4">Person</th>
                            <th class="font-bold pb-2 pr-4">Role</th>
                            <th class="font-bold pb-2 pr-4">Wants most</th>
                            <th class="font-bold pb-2 pr-4 text-right">Score</th>
                            <th class="font-bold pb-2 pr-4">From</th>
                            <th class="font-bold pb-2 text-right">Submitted</th>
                        </tr>
                    </thead>
                    @foreach($participants as $response)
                        @php
                            $answers = $response->answers ?? [];
                            $role = $roles[$answers['role'] ?? ''] ?? null;
                            $displayName = $response->name ?: ($response->user->name ?? null);
                            $displayEmail = $response->email ?: ($response->user->email ?? null);
                            $labelFor = fn ($key, $value) => $questions[$key]['options'][$value] ?? null;
                            $wantsMost = collect((array) ($answers['wishlist'] ?? []))->map(fn ($v) => $labelFor('wishlist', $v))->filter();
                        @endphp
                        <tbody x-data="{ open: false }" class="border-t border-slate-100">
                            <tr class="cursor-pointer hover:bg-slate-50 align-top" @click="open = !open" @keydown.enter="open = !open" tabindex="0" :aria-expanded="open">
                                <td class="py-3 pr-4">
                                    <p class="font-semibold text-slate-800">{{ $displayName ?: 'Anonymous' }}
                                        @if($response->user?->is_researcher)<span class="ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-black uppercase">Researcher</span>@endif
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $displayEmail ?: 'No email' }}
                                        @if($response->notify)<span title="Wants to hear when things ship">🔔</span>@endif
                                        @if(!$response->user_id)<span class="text-slate-400">· guest</span>@endif
                                    </p>
                                </td>
                                <td class="py-3 pr-4 text-slate-700 whitespace-nowrap">{{ $role ? $role[0] . ' ' . $role[1] : '–' }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $wantsMost->map(fn ($o) => $o[0] . ' ' . $o[1])->implode(', ') ?: '–' }}</td>
                                <td class="py-3 pr-4 text-right font-bold tabular-nums text-slate-800">{{ $response->nps ?? '–' }}</td>
                                <td class="py-3 pr-4 text-xs text-slate-500">{{ $response->source }}</td>
                                <td class="py-3 text-right text-xs text-slate-500 whitespace-nowrap" title="{{ $response->created_at?->toDayDateTimeString() }}">
                                    {{ $response->created_at?->diffForHumans() }}
                                    <span class="inline-block ml-1 text-slate-400 transition-transform" :class="open && 'rotate-90'">›</span>
                                </td>
                            </tr>
                            <tr x-show="open" x-cloak>
                                <td colspan="6" class="pb-4">
                                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 grid sm:grid-cols-2 gap-x-8 gap-y-3 text-xs">
                                        @foreach($questions as $key => $question)
                                            @php $picked = collect((array) ($answers[$key] ?? []))->map(fn ($v) => $labelFor($key, $v))->filter(); @endphp
                                            <div>
                                                <p class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">{{ $question['title'] }}</p>
                                                <p class="text-slate-800 mt-0.5">{{ $picked->map(fn ($o) => $o[0] . ' ' . $o[1])->implode(', ') ?: 'Skipped' }}</p>
                                            </div>
                                        @endforeach
                                        <div class="sm:col-span-2">
                                            <p class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Feature reactions</p>
                                            <div class="mt-1 flex flex-wrap gap-1.5">
                                                @forelse((array) ($answers['features'] ?? []) as $featureKey => $reaction)
                                                    @if(isset($features[$featureKey], $reactionMeta[$reaction]))
                                                        <span class="px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-700">
                                                            {{ $features[$featureKey]['emoji'] }} {{ $features[$featureKey]['title'] }}: <strong>{{ $reactionMeta[$reaction]['label'] }}</strong>
                                                        </span>
                                                    @endif
                                                @empty
                                                    <span class="text-slate-400">No reactions</span>
                                                @endforelse
                                            </div>
                                        </div>
                                        @if($response->wish)
                                            <div class="sm:col-span-2">
                                                <p class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Their wish</p>
                                                <p class="text-slate-800 mt-0.5 whitespace-pre-line">{{ $response->wish }}</p>
                                            </div>
                                        @endif
                                        <p class="sm:col-span-2 text-[11px] text-slate-400">
                                            Response #{{ $response->id }} · {{ $response->created_at?->toDayDateTimeString() }}
                                            @if($response->duration_seconds) · took {{ \Carbon\CarbonInterval::seconds($response->duration_seconds)->cascade()->forHumans(['short' => true]) }}@endif
                                            @if($response->user_id) · signed-in account #{{ $response->user_id }}@endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
            <div class="mt-4">{{ $participants->fragment('participants')->links() }}</div>
        @endif
    </section>

    {{-- Open-text wishes --}}
    <section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6">
        <h2 class="text-base font-black text-slate-800">“If you could add one thing…”</h2>
        <p class="text-xs text-slate-500 mt-0.5 mb-4">Newest first. People who asked to hear back are marked 🔔.</p>

        @forelse($wishes as $response)
            @php $role = $roles[$response->answers['role'] ?? ''] ?? null; @endphp
            <article class="border-t border-slate-100 py-4 first-of-type:border-t-0 first-of-type:pt-0">
                <p class="text-sm text-slate-800 leading-relaxed whitespace-pre-line">{{ $response->wish }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                    @if($role)<span class="px-2 py-0.5 rounded-full bg-slate-100 font-semibold text-slate-600">{{ $role[0] }} {{ $role[1] }}</span>@endif
                    <span>{{ $response->name ?: ($response->user->name ?? 'Anonymous') }}</span>
                    @if($response->email)<span>{{ $response->email }}</span>@endif
                    @if($response->notify)<span title="Wants to hear when this ships">🔔</span>@endif
                    <span>{{ $response->source }}</span>
                    <span>{{ $response->created_at?->diffForHumans() }}</span>
                </div>
            </article>
        @empty
            <p class="text-sm text-slate-400">No written wishes yet.</p>
        @endforelse

        <div class="mt-4">{{ $wishes->links() }}</div>
    </section>

    {{-- Shared hover tooltip --}}
    <div x-show="tip" x-cloak class="fixed z-50 pointer-events-none px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold shadow-xl whitespace-nowrap"
        :style="tip && `left:${tip.x}px; top:${tip.y - 8}px; transform: translate(-50%, -100%)`" x-text="tip?.text"></div>
</div>
@endsection
