@extends('layouts.app')

@section('title', 'Authentic Duas with Quran & Hadith References and Word-by-Word Meanings')
@section('meta_description', 'Explore a rich library of authentic Quranic and Prophetic Duas with word-by-word meanings, English and Urdu translations, spiritual commentary, audio recitation, and Hadith references.')

@section('content')
<div class="space-y-10 pb-16 animate-fadeIn" x-data="{
    activeTab: '{{ request('source_type', 'all') }}',
    selectedCategory: '{{ request('category', 'all') }}',
    searchQuery: '{{ request('search', '') }}',
    language: 'en',
    wordMode: {},
    counter: {},
    copiedId: null,
    speakingId: null,

    toggleWordMode(id) {
        this.wordMode[id] = !this.wordMode[id];
    },

    incrementCounter(id, max) {
        if (!this.counter[id]) this.counter[id] = 0;
        if (this.counter[id] < max) {
            this.counter[id]++;
            if (window.navigator && window.navigator.vibrate) {
                window.navigator.vibrate(50);
            }
        }
    },

    resetCounter(id) {
        this.counter[id] = 0;
    },

    speakArabic(text, id) {
        if (!('speechSynthesis' in window)) {
            alert('Speech synthesis is not supported on this browser.');
            return;
        }
        window.speechSynthesis.cancel();
        if (this.speakingId === id) {
            this.speakingId = null;
            return;
        }
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'ar-SA';
        utterance.rate = 0.85;
        this.speakingId = id;
        utterance.onend = () => { this.speakingId = null; };
        utterance.onerror = () => { this.speakingId = null; };
        window.speechSynthesis.speak(utterance);
    },

    copyDua(title, arabic, transliteration, meaning, ref, id) {
        const textToCopy = `${title}\n\n${arabic}\n\n${transliteration}\n\nMeaning:\n${meaning}\n\nReference: ${ref}\nSource: The Eternal Echo (Quranic Lens)`;
        navigator.clipboard.writeText(textToCopy).then(() => {
            this.copiedId = id;
            setTimeout(() => { this.copiedId = null; }, 2500);
        });
    }
}">

    {{-- Hero Section --}}
    <div class="relative bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 rounded-[2.5rem] p-8 md:p-14 text-white overflow-hidden shadow-2xl border border-slate-800">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-emerald-400 via-slate-900 to-slate-950 pointer-events-none"></div>
        <div class="absolute -right-24 -bottom-24 w-96 h-96 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-4xl space-y-6">
            <div class="inline-flex items-center space-x-2 bg-emerald-500/20 backdrop-blur-md rounded-full px-4.5 py-1.5 border border-emerald-400/20 shadow-sm">
                <span class="flex w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[10px] font-black tracking-widest uppercase text-emerald-200">Sacred Supplications • الأدعية النبوية والقرآنية</span>
            </div>

            <div class="space-y-2">
                <h1 class="text-3xl md:text-5xl font-black tracking-tight leading-tight">
                    Authentic Duas from the <br class="hidden md:block"/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-200 to-amber-200">Quran & Prophetic Sunnah</span>
                </h1>
                <p class="text-slate-300 font-medium text-sm md:text-base leading-relaxed max-w-2xl">
                    Deepen your spiritual connection with complete word-by-word meaning breakdowns, bilingual translations, theological explanations, audio pronunciation, and verified Quran and Hadith citations.
                </p>
            </div>

            {{-- Stats Badges --}}
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <div class="flex items-center space-x-2 bg-white/5 backdrop-blur-md px-3.5 py-1.5 rounded-xl border border-white/10 text-xs font-semibold text-emerald-300">
                    <span>✨</span>
                    <span>{{ $totalCount }} Authentic Duas</span>
                </div>
                <div class="flex items-center space-x-2 bg-white/5 backdrop-blur-md px-3.5 py-1.5 rounded-xl border border-white/10 text-xs font-semibold text-teal-300">
                    <span>📖</span>
                    <span>{{ $quranCount }} Quranic Prayers</span>
                </div>
                <div class="flex items-center space-x-2 bg-white/5 backdrop-blur-md px-3.5 py-1.5 rounded-xl border border-white/10 text-xs font-semibold text-amber-300">
                    <span>📜</span>
                    <span>{{ $hadithCount }} Hadith Supplications</span>
                </div>
                <a href="{{ route('daily.dua') }}" class="flex items-center space-x-1.5 bg-emerald-600 hover:bg-emerald-500 transition-all text-white px-4 py-1.5 rounded-xl text-xs font-bold shadow-lg shadow-emerald-900/30">
                    <span>🌟</span>
                    <span>Daily Dua of the Day</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Featured Dua Spotlight (if available) --}}
    @if($featuredDua && !request()->has('search') && !request()->has('category'))
    <div class="bg-gradient-to-r from-emerald-900/40 via-slate-900/40 to-teal-900/30 rounded-3xl p-6 md:p-8 border border-emerald-500/20 shadow-lg relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div class="flex items-center space-x-2">
                <span class="bg-amber-400/20 text-amber-300 text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider border border-amber-400/30">⭐ Featured Reflection</span>
                <span class="text-xs font-bold text-slate-400">{{ $featuredDua->category }}</span>
            </div>
            <div class="flex items-center space-x-2">
                @if($featuredDua->quran_lens_url)
                <a href="{{ $featuredDua->quran_lens_url }}" class="inline-flex items-center space-x-1.5 text-xs font-bold text-emerald-400 hover:text-emerald-300 bg-emerald-950/60 px-3 py-1.5 rounded-xl border border-emerald-800/60 transition">
                    <span>📖</span>
                    <span>Open in Quranic Lens</span>
                </a>
                @endif
                <a href="{{ route('duas.show', $featuredDua->slug) }}" class="inline-flex items-center space-x-1 text-xs font-bold text-emerald-300 hover:text-white bg-emerald-600/30 hover:bg-emerald-600 px-3 py-1.5 rounded-xl transition">
                    <span>Deep Study →</span>
                </a>
            </div>
        </div>

        <div class="space-y-4">
            <h2 class="text-xl md:text-2xl font-black text-slate-800 md:text-white">
                <a href="{{ route('duas.show', $featuredDua->slug) }}" class="hover:text-emerald-300 transition">
                    {{ $featuredDua->title }}
                </a>
            </h2>

            <p class="text-right font-arabic text-2xl md:text-3xl text-emerald-700 md:text-emerald-300 font-bold leading-relaxed tracking-wide" dir="rtl">
                {{ $featuredDua->arabic_text }}
            </p>

            <p class="text-slate-600 md:text-slate-300 italic text-sm font-medium">
                {{ $featuredDua->transliteration }}
            </p>

            <div class="p-4 rounded-2xl bg-white/80 md:bg-white/5 border border-slate-200 md:border-white/10 text-slate-700 md:text-slate-200 text-sm leading-relaxed">
                <span class="font-bold text-emerald-600 md:text-emerald-400">Meaning:</span> {{ $featuredDua->translation_en }}
            </div>
        </div>
    </div>
    @endif

    {{-- Filter & Search Controls --}}
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 space-y-6">
        <form method="GET" action="{{ route('duas.index') }}" class="space-y-6">
            {{-- Search Bar --}}
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search Duas by title, meaning (e.g. 'forgiveness', 'anxiety', 'parents'), Arabic, or reference..."
                    class="w-full pl-12 pr-28 py-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                >
                <div class="absolute inset-y-0 right-2 flex items-center space-x-1">
                    @if($search || $category || $sourceType)
                    <a href="{{ route('duas.index') }}" class="px-2.5 py-1.5 text-xs text-slate-400 hover:text-slate-600 font-bold">
                        Clear
                    </a>
                    @endif
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm">
                        Search
                    </button>
                </div>
            </div>

            {{-- Source Type Filter Tabs --}}
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Source:</span>
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl">
                        <a href="{{ route('duas.index', array_merge(request()->query(), ['source_type' => 'all'])) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ empty($sourceType) || $sourceType === 'all' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                            All Sources ({{ $totalCount }})
                        </a>
                        <a href="{{ route('duas.index', array_merge(request()->query(), ['source_type' => 'quran'])) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $sourceType === 'quran' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                            📖 Holy Quran ({{ $quranCount }})
                        </a>
                        <a href="{{ route('duas.index', array_merge(request()->query(), ['source_type' => 'hadith'])) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $sourceType === 'hadith' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                            📜 Sahih Hadith ({{ $hadithCount }})
                        </a>
                    </div>
                </div>

                {{-- Global Meaning Language Toggle --}}
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Meaning Language:</span>
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl">
                        <button type="button" @click="language = 'en'" :class="language === 'en' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600'" class="px-3 py-1 rounded-lg text-xs font-bold transition">
                            English
                        </button>
                        <button type="button" @click="language = 'ur'" :class="language === 'ur' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600'" class="px-3 py-1 rounded-lg text-xs font-bold transition">
                            اردو (Urdu)
                        </button>
                    </div>
                </div>
            </div>

            {{-- Category Filter Pills --}}
            <div class="flex items-center space-x-2 overflow-x-auto pb-2 custom-scrollbar">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">Categories:</span>
                <a href="{{ route('duas.index', array_merge(request()->query(), ['category' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ empty($category) || $category === 'all' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    All Categories
                </a>
                @foreach($categories as $cat)
                <a href="{{ route('duas.index', array_merge(request()->query(), ['category' => $cat->category])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ $category === $cat->category ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $cat->category }} <span class="opacity-70 text-[10px]">({{ $cat->count }})</span>
                </a>
                @endforeach
            </div>
        </form>
    </div>

    {{-- Duas Cards Listing --}}
    @if($duas->isEmpty())
    <div class="bg-white rounded-3xl p-12 text-center border border-slate-100 shadow-sm space-y-4">
        <div class="text-5xl">🔍</div>
        <h3 class="text-xl font-bold text-slate-800">No Duas Found</h3>
        <p class="text-slate-500 text-sm max-w-md mx-auto">
            We could not find any supplications matching your current search or category filter. Try searching for broader terms like "forgiveness", "guidance", or "protection".
        </p>
        <a href="{{ route('duas.index') }}" class="inline-block bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-emerald-700 transition">
            Reset Filters
        </a>
    </div>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        @foreach($duas as $dua)
        <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200/80 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-6 relative group" id="dua-{{ $dua->id }}">

            {{-- Top Header: Category, Badges, Copy, Audio --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="bg-slate-100 text-slate-700 text-xs font-extrabold px-3 py-1 rounded-full">
                            {{ $dua->category }}
                        </span>
                        @if($dua->source_type === 'quran' || $dua->source_type === 'both')
                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-[11px] font-bold px-2.5 py-0.5 rounded-full flex items-center space-x-1">
                            <span>📖</span>
                            <span>Quranic</span>
                        </span>
                        @endif
                        @if($dua->source_type === 'hadith' || $dua->source_type === 'both')
                        <span class="bg-amber-50 text-amber-800 border border-amber-200/60 text-[11px] font-bold px-2.5 py-0.5 rounded-full flex items-center space-x-1">
                            <span>📜</span>
                            <span>Hadith</span>
                        </span>
                        @endif
                    </div>

                    {{-- Action Icons: Speech, Copy --}}
                    <div class="flex items-center space-x-1.5">
                        {{-- Audio Speech Synthesis --}}
                        <button
                            type="button"
                            @click="speakArabic(`{{ addslashes($dua->arabic_text) }}`, {{ $dua->id }})"
                            :class="speakingId === {{ $dua->id }} ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-emerald-100 hover:text-emerald-700'"
                            class="p-2 rounded-xl text-xs font-bold transition flex items-center space-x-1"
                            title="Listen to Arabic Pronunciation"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            </svg>
                        </button>

                        {{-- 1-Click Copy --}}
                        <button
                            type="button"
                            @click="copyDua(
                                `{{ addslashes($dua->title) }}`,
                                `{{ addslashes($dua->arabic_text) }}`,
                                `{{ addslashes($dua->transliteration) }}`,
                                `{{ addslashes($dua->translation_en) }}`,
                                `{{ addslashes($dua->quran_reference ?: $dua->hadith_reference) }}`,
                                {{ $dua->id }}
                            )"
                            class="p-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-emerald-100 hover:text-emerald-700 transition flex items-center space-x-1"
                            title="Copy Dua Text & Reference"
                        >
                            <svg x-show="copiedId !== {{ $dua->id }}" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg x-show="copiedId === {{ $dua->id }}" x-cloak class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Title & Description --}}
                <h3 class="text-lg font-black text-slate-900 leading-snug">
                    <a href="{{ route('duas.show', $dua->slug) }}" class="hover:text-emerald-600 transition">
                        {{ $dua->title }}
                    </a>
                </h3>

                @if($dua->description)
                <p class="text-xs text-slate-500 font-medium leading-relaxed">
                    {{ $dua->description }}
                </p>
                @endif
            </div>

            {{-- Arabic Calligraphy & Transliteration --}}
            <div class="space-y-4 bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
                <p class="text-right font-arabic text-2xl md:text-3xl text-slate-900 font-bold leading-loose tracking-wide" dir="rtl">
                    {{ $dua->arabic_text }}
                </p>

                @if($dua->transliteration)
                <p class="text-xs text-emerald-800 font-medium italic border-t border-slate-200/60 pt-3">
                    {{ $dua->transliteration }}
                </p>
                @endif
            </div>

            {{-- Interactive Meaning Functionality Section --}}
            <div class="space-y-3" x-data="{ expandedExplanation: false }">
                {{-- View Mode Switcher (Sentence vs Word-by-Word) --}}
                <div class="flex items-center justify-between text-xs">
                    <span class="font-black text-slate-700 uppercase tracking-wider text-[11px]">
                        Meaning & Translation
                    </span>
                    @if(!empty($dua->word_by_word))
                    <button
                        type="button"
                        @click="toggleWordMode({{ $dua->id }})"
                        class="text-xs font-bold text-emerald-700 hover:text-emerald-800 underline flex items-center space-x-1"
                    >
                        <span x-show="!wordMode[{{ $dua->id }}]">Show Word-by-Word breakdown</span>
                        <span x-show="wordMode[{{ $dua->id }}]" x-cloak>Show sentence translation</span>
                    </button>
                    @endif
                </div>

                {{-- Continuous Sentence Translation (Default) --}}
                <div x-show="!wordMode[{{ $dua->id }}]" class="p-4 rounded-2xl bg-emerald-50/40 border border-emerald-100/80 text-sm leading-relaxed text-slate-800">
                    <div x-show="language === 'en'">
                        {{ $dua->translation_en }}
                    </div>
                    <div x-show="language === 'ur'" x-cloak class="font-arabic text-base text-right leading-loose" dir="rtl">
                        {{ $dua->translation_ur ?: $dua->translation_en }}
                    </div>
                </div>

                {{-- Word-by-Word Matrix (When toggled) --}}
                @if(!empty($dua->word_by_word))
                <div x-show="wordMode[{{ $dua->id }}]" x-cloak class="p-3 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-2">
                    <div class="text-[11px] font-bold text-slate-400 mb-1">Interactive Word-by-Word Analysis (Hover / Click):</div>
                    <div class="flex flex-wrap gap-2 justify-end" dir="rtl">
                        @foreach($dua->word_by_word as $word)
                        <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-sm text-center min-w-[70px] hover:border-emerald-500 hover:shadow transition-all group/word" dir="ltr">
                            <div class="font-arabic text-lg font-bold text-slate-900 group-hover/word:text-emerald-600">
                                {{ $word['arabic'] ?? '' }}
                            </div>
                            <div class="text-[10px] text-slate-400 italic">
                                {{ $word['transliteration'] ?? '' }}
                            </div>
                            <div class="text-[11px] font-bold text-emerald-800 mt-1" x-show="language === 'en'">
                                {{ $word['meaning_en'] ?? '' }}
                            </div>
                            <div class="text-xs font-bold text-emerald-800 mt-1 font-arabic" x-show="language === 'ur'" x-cloak dir="rtl">
                                {{ $word['meaning_ur'] ?? $word['meaning_en'] ?? '' }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Expandable Deep Spiritual Commentary & Vocabulary --}}
                @if($dua->meaning_explanation || $dua->benefits_and_virtues)
                <div class="border border-slate-100 rounded-2xl overflow-hidden">
                    <button
                        type="button"
                        @click="expandedExplanation = !expandedExplanation"
                        class="w-full flex items-center justify-between p-3 bg-slate-50/60 hover:bg-slate-100 transition text-xs font-bold text-slate-700"
                    >
                        <span class="flex items-center space-x-1.5">
                            <span>💡</span>
                            <span>Deep Meaning & Spiritual Nuances</span>
                        </span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="expandedExplanation ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="expandedExplanation" x-cloak class="p-4 bg-white text-xs text-slate-600 space-y-3 leading-relaxed border-t border-slate-100">
                        @if($dua->meaning_explanation)
                        <div>
                            <div class="font-bold text-slate-800 mb-1">Linguistic & Spiritual Commentary:</div>
                            <p>{{ $dua->meaning_explanation }}</p>
                        </div>
                        @endif

                        @if($dua->benefits_and_virtues)
                        <div class="pt-2 border-t border-slate-100">
                            <div class="font-bold text-slate-800 mb-1">Virtues & Rewards:</div>
                            <p>{{ $dua->benefits_and_virtues }}</p>
                        </div>
                        @endif

                        @if($dua->when_to_recite)
                        <div class="pt-2 border-t border-slate-100 flex items-center space-x-1.5 text-emerald-800 font-semibold">
                            <span>⏰</span>
                            <span>Recommended times: {{ $dua->when_to_recite }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- References & Sunnah Counter Bar --}}
            <div class="pt-4 border-t border-slate-100 space-y-4">
                {{-- Quran & Hadith References --}}
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="space-y-1">
                        @if($dua->quran_reference)
                        <div class="flex items-center space-x-1.5 text-slate-700 font-semibold">
                            <span class="text-emerald-600 font-bold">Quran:</span>
                            @if($dua->quran_lens_url)
                            <a href="{{ $dua->quran_lens_url }}" class="text-emerald-700 hover:text-emerald-900 underline font-bold" title="View verse commentary in Quranic Lens">
                                {{ $dua->quran_reference }} ↗
                            </a>
                            @else
                            <span>{{ $dua->quran_reference }}</span>
                            @endif
                        </div>
                        @endif

                        @if($dua->hadith_reference)
                        <div class="flex items-center space-x-1.5 text-slate-700 font-semibold">
                            <span class="text-amber-600 font-bold">Hadith:</span>
                            <span>{{ $dua->hadith_reference }}</span>
                            @if($dua->hadith_grading)
                            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-1.5 py-0.2 rounded">
                                {{ $dua->hadith_grading }}
                            </span>
                            @endif
                        </div>
                        @endif
                    </div>

                    {{-- Sunnah Tasbih Counter --}}
                    <div class="flex items-center space-x-2 bg-slate-50 px-3 py-1.5 rounded-2xl border border-slate-200/80">
                        <span class="text-[11px] font-bold text-slate-500">Sunnah Count:</span>
                        <button
                            type="button"
                            @click="incrementCounter({{ $dua->id }}, {{ $dua->repeat_count }})"
                            class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-black transition flex items-center space-x-1 shadow-sm"
                            title="Click to count repetition"
                        >
                            <span x-text="(counter[{{ $dua->id }}] || 0)"></span>
                            <span>/ {{ $dua->repeat_count }}</span>
                        </button>
                        <button
                            type="button"
                            @click="resetCounter({{ $dua->id }})"
                            class="text-slate-400 hover:text-slate-600 text-xs p-1"
                            title="Reset counter"
                        >
                            ↺
                        </button>
                    </div>
                </div>

                {{-- Bottom Link to Full Study --}}
                <div class="flex items-center justify-between pt-2">
                    <span class="text-[11px] text-slate-400 font-medium">
                        #{{ $dua->order ?: $dua->id }} in Authentic Collection
                    </span>
                    <a href="{{ route('duas.show', $dua->slug) }}" class="inline-flex items-center space-x-1 text-xs font-black text-emerald-700 hover:text-emerald-800 transition">
                        <span>Read Full Analysis & Tafsir</span>
                        <span>→</span>
                    </a>
                </div>
            </div>

        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="pt-6">
        {{ $duas->links() }}
    </div>
    @endif

</div>
@endsection
