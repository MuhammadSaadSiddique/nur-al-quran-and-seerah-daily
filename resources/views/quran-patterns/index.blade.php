@extends('layouts.app')

@section('title', 'Quran Patterns — Ring, Math, and Language Notes')
@section('meta_description', 'Search any Quran verse or word for ring composition, text counts, and linguistic notes. Counts come from the stored text. Notes are study observations.')

@section('content')
<div class="max-w-3xl mx-auto pb-16">
    <p class="text-emerald-700 text-xs font-black tracking-[0.2em] uppercase">Patterns</p>
    <h1 class="text-4xl font-black text-slate-900 mt-2">Quran Patterns</h1>
    <p class="text-slate-500 mt-2">Ring, math, and language notes for any verse.</p>

    <form action="{{ route('patterns.index') }}" method="GET" class="mt-6 flex flex-col sm:flex-row gap-3">
        <label class="sr-only" for="pattern-search">Search a word or verse</label>
        <input id="pattern-search" name="q" value="{{ $query }}" placeholder="Search a word or verse, such as 86:1"
            class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-800 shadow-sm outline-none focus:border-emerald-600" />
        <button type="submit"
            class="rounded-xl bg-emerald-700 px-6 py-3 font-bold text-white hover:bg-emerald-800">Search</button>
    </form>

    @if($message)
        <p class="mt-4 text-sm font-semibold text-rose-700">{{ $message }}</p>
    @endif

    @if($suggested)
        <p class="mt-4 text-sm text-slate-500">These verses were suggested from the search. Open one to read the pattern notes.</p>
    @endif

    @if(count($hits) > 0)
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach($hits as $hit)
                <a href="{{ route('patterns.show', ['chapter' => $hit['chapter'], 'verse' => $hit['verse'], 'focus' => $query]) }}"
                    class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-semibold text-slate-700 hover:border-emerald-600 {{ isset($card) && $card && $card['verse_key'] === $hit['verse_key'] ? 'border-emerald-600 text-emerald-800' : '' }}">
                    {{ $hit['verse_key'] }} {{ $hit['surah_name'] }}
                </a>
            @endforeach
        </div>
    @endif

    @if($card)
        <article class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm" x-data="{ details: false }">
            <div class="grid gap-6 sm:grid-cols-2 sm:items-start">
                <div>
                    <p class="text-lg text-slate-700 leading-snug">{{ $card['gloss'] }}</p>
                    <p class="mt-8 text-sm text-slate-400">Verse</p>
                    <p class="text-5xl font-black tracking-tight text-slate-900">{{ $card['verse_key'] }}</p>
                </div>
                <div class="sm:border-l sm:border-slate-100 sm:pl-6">
                    <p class="text-3xl font-bold text-emerald-800 text-right" dir="rtl">{{ $card['arabic_term'] }}</p>
                    <div class="mt-6 space-y-4 text-sm">
                        <p><span class="font-black text-emerald-700">Ring</span><br><span class="text-slate-700">— {{ $card['ring_note'] }}</span></p>
                        <p><span class="font-black text-emerald-700">Math</span><br><span class="text-slate-700">— {{ $card['math']['summary'] ?? '' }}</span></p>
                        <p><span class="font-black text-emerald-700">Linguistic</span><br><span class="text-slate-700">— {{ $card['linguistic_note'] }}</span></p>
                    </div>
                </div>
            </div>

            @if(!empty($card['ring_parts']))
                <ol class="mt-6 space-y-2">
                    @foreach($card['ring_parts'] as $part)
                        <li class="rounded-xl px-3 py-2 {{ $part['center'] ? 'bg-amber-100 text-amber-950 font-bold' : 'bg-emerald-50 text-slate-800' }}">
                            <span class="mr-2 text-xs font-black {{ $part['center'] ? 'text-amber-700' : 'text-emerald-700' }}">{{ $part['n'] }}@if($part['pair']) · {{ $part['pair'] }}@endif</span>
                            {{ $part['text'] }}
                            @if($part['center'])
                                <span class="ml-2 text-xs font-semibold uppercase tracking-wide text-amber-700">Center</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-2">
                @foreach($card['topics'] as $topic)
                    <span class="rounded-full bg-emerald-700 px-3 py-1 text-sm font-bold text-white">{{ $topic }}</span>
                @endforeach
                <button type="button" @click="details = !details"
                    class="rounded-full bg-emerald-800 px-4 py-1 text-sm font-bold text-white">Details</button>
                <a href="{{ $card['resources']['verse_url'] }}"
                    class="rounded-full bg-emerald-900 px-4 py-1 text-sm font-bold text-white">Open verse</a>
            </div>

            <div x-show="details" x-cloak class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-sm text-slate-600">
                @if($card['translation'])
                    <p>{{ $card['translation'] }}</p>
                @endif
                <p>{{ $card['arabic_text'] }}</p>
                <p>Letters in this verse: {{ $card['math']['letters_in_verse'] ?? 0 }}.</p>
                @if(($card['math']['focus_count_in_surah'] ?? null) !== null)
                    <p>This focus word appears {{ $card['math']['focus_count_in_surah'] }} time(s) in the stored text of the surah.</p>
                @endif
                @foreach($card['resources']['science'] as $link)
                    <p><span class="font-bold text-slate-800">{{ $link['title'] }}</span>
                        @if($link['field']) ({{ $link['field'] }}) @endif
                        — {{ $link['description'] }}</p>
                @endforeach
            </div>
        </article>
        <p class="mt-4 text-center text-xs text-slate-400">{{ $card['disclaimer'] }}</p>
    @elseif($query === '')
        <p class="mt-8 text-sm text-slate-500">Search a word, a phrase, or a verse key. A card shows the ring note, counts from the stored Arabic, and a linguistic note.</p>
    @endif
</div>
<style>[x-cloak]{display:none !important}</style>
@endsection
