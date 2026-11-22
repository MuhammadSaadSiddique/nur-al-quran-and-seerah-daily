@extends('layouts.app')

@section('title', 'Admin Panel - Researchers')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Researchers</h1>
            <p class="text-slate-500 mt-1">The research team, people who applied to join, and what each of them has done.</p>
        </div>
        <a href="{{ route('researchers.index') }}" target="_blank" rel="noopener" class="self-start md:self-auto px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm rounded-xl transition-all">Public directory ↗</a>
    </div>

    @include('admin.partials.tabs')

    @if(session('success'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    {{-- Stat tiles --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @foreach([
            ['On the research team', $stats['team'], null],
            ['Team members who took the pledge', $stats['pledged'], $stats['team'] - $stats['pledged'] > 0 ? ($stats['team'] - $stats['pledged']) . ' still need to, before they can approve' : 'Everyone can approve content'],
            ['Waiting for a decision', $stats['applicants'], 'Applied and pledged, not yet granted'],
            ['Survey: “Researcher or scholar”', $stats['survey_scholars'], 'Possible people to invite'],
        ] as [$label, $value, $sub])
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                <p class="text-3xl font-black text-slate-900 mt-1">{{ number_format($value) }}</p>
                @if($sub)<p class="text-[11px] text-slate-400 mt-0.5">{{ $sub }}</p>@endif
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.researchers.index') }}" class="mb-6 p-4 bg-white/80 border border-slate-200 rounded-2xl flex flex-wrap gap-3 items-center">
        <div class="flex rounded-xl bg-slate-100 p-1 text-xs font-bold">
            @foreach(['all' => 'Everyone', 'applicants' => 'Applicants', 'researchers' => 'Research team'] as $value => $label)
                <a href="{{ route('admin.researchers.index', array_filter(['status' => $value === 'all' ? null : $value, 'q' => request('q')])) }}"
                    class="px-3 py-1.5 rounded-lg {{ $status === $value ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">{{ $label }}</a>
            @endforeach
        </div>
        @if($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email or expertise" aria-label="Search researchers"
            class="w-64 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 focus:outline-none focus:border-emerald-500">
        <button class="px-3 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl">Search</button>
        @if(request()->filled('q'))
            <a href="{{ route('admin.researchers.index', array_filter(['status' => $status === 'all' ? null : $status])) }}" class="text-xs text-rose-500 font-bold hover:underline">Clear</a>
        @endif
    </form>

    {{-- People --}}
    <section class="space-y-3 mb-8">
        @forelse($researchers as $person)
            @php
                $onboarding = $person->researcherOnboarding;
                $stat = $activity[$person->id];
                $pledged = $onboarding?->pledged_at !== null;
            @endphp
            <article x-data="{ open: false }" class="bg-white border border-slate-200 rounded-2xl">
                <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center gap-4">
                    <button type="button" @click="open = !open" :aria-expanded="open" class="flex-1 min-w-0 flex items-start gap-3 text-left">
                        <div class="h-11 w-11 shrink-0 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-black text-lg">
                            {{ mb_strtoupper(mb_substr($onboarding->full_name ?? $person->name ?? 'R', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-slate-800 flex flex-wrap items-center gap-1.5">
                                {{ $onboarding->full_name ?? $person->name }}
                                @if($person->is_researcher)
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase">Research team</span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-black uppercase">Applicant</span>
                                @endif
                                @if($person->is_admin)<span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-black uppercase">Admin</span>@endif
                                @if(!$pledged)<span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[10px] font-black uppercase" title="Cannot approve content until they complete onboarding">No pledge yet</span>@endif
                            </p>
                            <p class="text-xs text-slate-500 truncate">{{ $person->email }}@if($onboarding && $onboarding->full_name !== $person->name) · account name “{{ $person->name }}”@endif</p>
                            <p class="text-xs text-slate-600 mt-1">
                                {{ $onboarding->expertise ?? 'No expertise given' }}
                                @if($person->expertCategory) · expert in <strong>{{ $person->expertCategory->name }}</strong>
                                @elseif($onboarding?->requestedExpertCategory) · asked for <strong>{{ $onboarding->requestedExpertCategory->name }}</strong>@endif
                            </p>
                        </div>
                    </button>

                    {{-- Activity --}}
                    <dl class="grid grid-cols-4 gap-2 text-center shrink-0">
                        @foreach([
                            ['Submitted', $stat['contributions'], $stat['published'] . ' published'],
                            ['Reviews', $stat['reviews'], 'votes on research'],
                            ['Lens items', $stat['lens_submitted'], 'submitted'],
                            ['Moderated', $stat['lens_moderated'], 'Lens items'],
                        ] as [$label, $value, $hint])
                            <div class="px-2 py-1.5 rounded-xl bg-slate-50 min-w-[4.5rem]" title="{{ $label }}: {{ $value }} ({{ $hint }})">
                                <dd class="text-lg font-black text-slate-900 tabular-nums">{{ $value }}</dd>
                                <dt class="text-[10px] font-semibold text-slate-500">{{ $label }}</dt>
                            </div>
                        @endforeach
                    </dl>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 shrink-0">
                        @unless($person->id === auth()->id())
                            <form action="{{ route('admin.users.toggle-researcher', $person) }}" method="POST"
                                onsubmit="return confirm(@js($person->is_researcher ? 'Remove ' . $person->email . ' from the research team?' : 'Give ' . $person->email . ' researcher access?'))">
                                @csrf @method('PATCH')
                                <button class="px-3 py-2 text-xs font-bold rounded-xl {{ $person->is_researcher ? 'bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-700' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                                    {{ $person->is_researcher ? 'Remove access' : 'Approve as researcher' }}
                                </button>
                            </form>
                        @endunless
                        <button type="button" @click="open = !open" class="px-2 py-2 text-slate-400 hover:text-slate-700" :aria-label="open ? 'Hide details' : 'Show details'">
                            <span class="inline-block transition-transform" :class="open && 'rotate-90'">›</span>
                        </button>
                    </div>
                </div>

                <div x-show="open" x-cloak class="border-t border-slate-100 p-4 sm:p-5 grid md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                    @if($onboarding)
                        @foreach([
                            'Credentials' => $onboarding->credentials,
                            'Background' => $onboarding->background,
                            'Sources they rely on' => $onboarding->sources,
                            'Languages' => $onboarding->languages,
                        ] as $label => $text)
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $label }}</p>
                                <p class="text-slate-800 mt-0.5 whitespace-pre-line">{{ $text ?: '–' }}</p>
                            </div>
                        @endforeach
                        <p class="md:col-span-2 text-xs text-slate-500">
                            @if($pledged)✅ Took the authenticity pledge {{ $onboarding->pledged_at->format('M j, Y') }} ({{ $onboarding->pledged_at->diffForHumans() }}).@endif
                            Account created {{ $person->created_at?->format('M j, Y') }}.
                        </p>
                    @else
                        <p class="md:col-span-2 text-slate-500">They were added to the team before onboarding existed and haven't filled in their details or taken the pledge yet, so they can't approve content.</p>
                    @endif

                    <form action="{{ route('researchers.update-expert', $person) }}" method="POST" class="md:col-span-2 flex flex-wrap items-center gap-2">
                        @csrf
                        <label for="expert-{{ $person->id }}" class="text-xs font-bold text-slate-500">Expert category</label>
                        <select id="expert-{{ $person->id }}" name="expert_category_id" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-700">
                            <option value="">None</option>
                            @foreach($scienceCategories as $category)
                                <option value="{{ $category->id }}" @selected($person->expert_category_id === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <button class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl">Save</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-10 text-center">
                <div class="text-4xl mb-3">🔬</div>
                <p class="text-sm text-slate-500">
                    @if(request()->filled('q')) Nobody matches that search.
                    @elseif($status === 'applicants') No one is waiting for a decision.
                    @else No researchers yet. People apply from the public researchers page. @endif
                </p>
            </div>
        @endforelse
        <div>{{ $researchers->links() }}</div>
    </section>

    {{-- Survey participants who describe themselves as researchers --}}
    <section class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-4">
            <div>
                <h2 class="text-base font-black text-slate-800">Survey participants who are researchers or scholars</h2>
                <p class="text-xs text-slate-500 mt-0.5">People who picked “Researcher or scholar” in the requirements survey. Good people to invite to the team.</p>
            </div>
            @if($stats['survey_scholars'] > 0)
                <a href="{{ route('admin.survey.index', ['role' => 'scholar']) }}#participants" class="text-xs font-bold text-emerald-700 hover:underline">See all {{ $stats['survey_scholars'] }} in the survey →</a>
            @endif
        </div>
        @forelse($surveyScholars as $response)
            <div class="border-t border-slate-100 py-3 first:border-t-0 first:pt-0 flex flex-wrap items-center justify-between gap-2 text-sm">
                <div>
                    <p class="font-semibold text-slate-800">{{ $response->name ?: ($response->user->name ?? 'Anonymous') }}
                        @if($response->user?->is_researcher)<span class="ml-1 px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase">On the team</span>@endif
                    </p>
                    <p class="text-xs text-slate-500">{{ $response->email ?: ($response->user->email ?? 'No email left') }} · {{ $response->created_at?->diffForHumans() }}</p>
                </div>
                @if($response->wish)
                    <p class="text-xs text-slate-600 max-w-md truncate" title="{{ $response->wish }}">“{{ $response->wish }}”</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-400">No survey participant has picked that role yet.</p>
        @endforelse
    </section>
</div>
@endsection
