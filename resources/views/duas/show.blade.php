@extends('layouts.app')

@section('title', $dua->title . ' — Meaning, Quran & Hadith Reference')
@section('meta_description', 'Detailed word-by-word breakdown, translation, spiritual commentary, and authentic references for ' . $dua->title . '.')

@section('content')
<div class="max-w-5xl mx-auto space-y-10 pb-16 animate-fadeIn" x-data="{
    fontSize: 32,
    language: 'en',
    counter: 0,
    maxCount: {{ $dua->repeat_count }},
    copied: false,
    speaking: false,

    increment() {
        if (this.counter < this.maxCount) {
            this.counter++;
            if (window.navigator && window.navigator.vibrate) {
                window.navigator.vibrate(50);
            }
        }
    },

    reset() {
        this.counter = 0;
    },

    speak() {
        if (!('speechSynthesis' in window)) {
            alert('Speech synthesis is not supported on this browser.');
            return;
        }
        window.speechSynthesis.cancel();
        if (this.speaking) {
            this.speaking = false;
            return;
        }
        const utterance = new SpeechSynthesisUtterance(`{{ addslashes($dua->arabic_text) }}`);
        utterance.lang = 'ar-SA';
        utterance.rate = 0.85;
        this.speaking = true;
        utterance.onend = () => { this.speaking = false; };
        utterance.onerror = () => { this.speaking = false; };
        window.speechSynthesis.speak(utterance);
    },

    copyDua() {
        const textToCopy = `{{ addslashes($dua->title) }}\n\n{{ addslashes($dua->arabic_text) }}\n\n{{ addslashes($dua->transliteration) }}\n\nMeaning:\n{{ addslashes($dua->translation_en) }}\n\nReference: {{ addslashes($dua->quran_reference ?: $dua->hadith_reference) }}\nSource: The Eternal Echo (Quranic Lens)`;
        navigator.clipboard.writeText(textToCopy).then(() => {
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2500);
        });
    }
}">

    {{-- Breadcrumb & Back Navigation --}}
    <div class="flex flex-wrap items-center justify-between gap-4 text-xs font-bold text-slate-500">
        <div class="flex items-center space-x-2">
            <a href="{{ route('duas.index') }}" class="text-emerald-700 hover:text-emerald-800 flex items-center space-x-1">
                <span>←</span>
                <span>Back to All Duas</span>
            </a>
            <span>/</span>
            <span class="text-slate-400">{{ $dua->category }}</span>
            <span>/</span>
            <span class="text-slate-700 truncate max-w-xs">{{ $dua->title }}</span>
        </div>

        <div class="flex items-center space-x-2">
            @if($prevDua)
            <a href="{{ route('duas.show', $prevDua->slug) }}" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:text-emerald-700 hover:border-emerald-300 transition" title="{{ $prevDua->title }}">
                ← Previous
            </a>
            @endif
            @if($nextDua)
            <a href="{{ route('duas.show', $nextDua->slug) }}" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:text-emerald-700 hover:border-emerald-300 transition" title="{{ $nextDua->title }}">
                Next →
            </a>
            @endif
        </div>
    </div>

    {{-- Main Dua Showcase Card --}}
    <div class="bg-white rounded-[2.5rem] p-8 md:p-12 border border-slate-200/80 shadow-md space-y-8">
        {{-- Header Badges --}}
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-6">
            <div class="flex flex-wrap items-center gap-2">
                <span class="bg-emerald-50 text-emerald-800 text-xs font-black px-3.5 py-1.5 rounded-full border border-emerald-200">
                    {{ $dua->category }}
                </span>
                @if($dua->source_type === 'quran' || $dua->source_type === 'both')
                <span class="bg-teal-50 text-teal-800 text-xs font-bold px-3 py-1 rounded-full border border-teal-200">
                    📖 Holy Quran
                </span>
                @endif
                @if($dua->source_type === 'hadith' || $dua->source_type === 'both')
                <span class="bg-amber-50 text-amber-800 text-xs font-bold px-3 py-1 rounded-full border border-amber-200">
                    📜 Sahih Hadith
                </span>
                @endif
                @if($dua->hadith_grading)
                <span class="bg-slate-100 text-slate-700 text-xs font-bold px-2.5 py-1 rounded-full">
                    Grading: {{ $dua->hadith_grading }}
                </span>
                @endif
            </div>

            {{-- Actions: Font size, Audio, Copy --}}
            <div class="flex items-center space-x-2">
                {{-- Font Size Controls --}}
                <div class="hidden sm:flex items-center space-x-1 bg-slate-100 p-1 rounded-xl text-xs font-bold text-slate-600">
                    <button type="button" @click="if(fontSize > 20) fontSize -= 4" class="px-2 py-1 hover:bg-white rounded-lg transition" title="Decrease font size">A-</button>
                    <button type="button" @click="if(fontSize < 52) fontSize += 4" class="px-2 py-1 hover:bg-white rounded-lg transition" title="Increase font size">A+</button>
                </div>

                {{-- Audio Reciter --}}
                <button
                    type="button"
                    @click="speak()"
                    :class="speaking ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-emerald-100 hover:text-emerald-800'"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                    </svg>
                    <span x-text="speaking ? 'Playing...' : 'Pronounce'"></span>
                </button>

                {{-- Copy Dua --}}
                <button
                    type="button"
                    @click="copyDua()"
                    class="px-3.5 py-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-emerald-100 hover:text-emerald-800 transition text-xs font-bold flex items-center space-x-1.5 shadow-sm"
                >
                    <svg x-show="!copied" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span x-text="copied ? 'Copied!' : 'Copy Dua'"></span>
                </button>
            </div>
        </div>

        {{-- Title and Context --}}
        <div class="space-y-3">
            <h1 class="text-2xl md:text-4xl font-black text-slate-900 leading-tight">
                {{ $dua->title }}
            </h1>
            @if($dua->description)
            <p class="text-slate-600 text-sm md:text-base leading-relaxed font-medium">
                {{ $dua->description }}
            </p>
            @endif
        </div>

        {{-- Arabic Calligraphy Panel --}}
        <div class="bg-gradient-to-b from-slate-50 to-emerald-50/20 p-8 md:p-12 rounded-3xl border border-slate-200/80 shadow-inner space-y-6">
            <p
                class="text-center font-arabic text-slate-950 font-bold leading-loose tracking-wide select-all"
                :style="`font-size: ${fontSize}px; line-height: 2.2;`"
                dir="rtl"
            >
                {{ $dua->arabic_text }}
            </p>

            @if($dua->transliteration)
            <div class="border-t border-slate-200 pt-5 text-center">
                <p class="text-xs md:text-sm font-semibold text-emerald-900 italic tracking-wide max-w-3xl mx-auto">
                    {{ $dua->transliteration }}
                </p>
            </div>
            @endif
        </div>

        {{-- Primary Meaning / Translation Box --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Translation & Meaning</h3>
                <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-bold">
                    <button type="button" @click="language = 'en'" :class="language === 'en' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600'" class="px-3 py-1 rounded-lg transition">English</button>
                    <button type="button" @click="language = 'ur'" :class="language === 'ur' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600'" class="px-3 py-1 rounded-lg transition">اردو</button>
                </div>
            </div>

            <div class="p-6 rounded-3xl bg-emerald-50/50 border border-emerald-100 text-slate-800 text-base md:text-lg leading-relaxed font-medium">
                <div x-show="language === 'en'">
                    "{{ $dua->translation_en }}"
                </div>
                <div x-show="language === 'ur'" x-cloak class="font-arabic text-xl text-right leading-loose" dir="rtl">
                    "{{ $dua->translation_ur ?: $dua->translation_en }}"
                </div>
            </div>
        </div>

        {{-- Interactive Word-by-Word Matrix --}}
        @if(!empty($dua->word_by_word))
        <div class="space-y-4 pt-4 border-t border-slate-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-base">🔍</span>
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">Interactive Word-by-Word Lexical Breakdown</h3>
                </div>
                <span class="text-xs text-slate-400 font-medium">Hover or tap on tokens</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3" dir="rtl">
                @foreach($dua->word_by_word as $word)
                <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm hover:border-emerald-500 hover:shadow-md transition-all text-center flex flex-col justify-between" dir="ltr">
                    <div class="font-arabic text-2xl font-bold text-slate-900 hover:text-emerald-700 transition mb-1">
                        {{ $word['arabic'] ?? '' }}
                    </div>
                    <div class="text-[11px] text-slate-400 italic mb-2">
                        {{ $word['transliteration'] ?? '' }}
                    </div>
                    <div class="pt-2 border-t border-slate-100">
                        <div class="text-xs font-black text-emerald-800" x-show="language === 'en'">
                            {{ $word['meaning_en'] ?? '' }}
                        </div>
                        <div class="text-xs font-bold text-emerald-800 font-arabic" x-show="language === 'ur'" x-cloak dir="rtl">
                            {{ $word['meaning_ur'] ?? $word['meaning_en'] ?? '' }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Sunnah Tasbih Repetition Counter --}}
        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 p-6 md:p-8 rounded-3xl text-white flex flex-col sm:flex-row items-center justify-between gap-6 shadow-lg">
            <div class="space-y-1 text-center sm:text-left">
                <div class="inline-flex items-center space-x-1.5 text-xs font-black uppercase tracking-wider text-emerald-400">
                    <span>📿</span>
                    <span>Sunnah Tasbih Counter</span>
                </div>
                <h4 class="text-lg font-bold text-white">Recommended Repetitions: {{ $dua->repeat_count }} {{ $dua->repeat_count === 1 ? 'time' : 'times' }}</h4>
                <p class="text-xs text-slate-400">
                    {{ $dua->when_to_recite ?: 'Recite with sincere contemplation and mindful faith.' }}
                </p>
            </div>

            <div class="flex items-center space-x-4">
                <button
                    type="button"
                    @click="increment()"
                    class="w-20 h-20 rounded-2xl bg-emerald-600 hover:bg-emerald-500 active:scale-90 text-white font-black text-2xl flex flex-col items-center justify-center shadow-lg shadow-emerald-900/50 transition-all border-2 border-emerald-400/40"
                    title="Tap to count"
                >
                    <span x-text="counter"></span>
                    <span class="text-[9px] uppercase tracking-widest text-emerald-200">Tap</span>
                </button>

                <div class="space-y-2 text-center">
                    <button
                        type="button"
                        @click="reset()"
                        class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-bold text-slate-300 transition block w-full"
                    >
                        Reset
                    </button>
                    <span x-show="counter >= maxCount" x-cloak class="inline-block bg-emerald-500/30 text-emerald-300 text-[10px] font-black px-2 py-0.5 rounded-full border border-emerald-400/30 animate-pulse">
                        Completed!
                    </span>
                </div>
            </div>
        </div>

        {{-- Deep Spiritual Insights & Tafsir / Sharh --}}
        @if($dua->meaning_explanation || $dua->benefits_and_virtues)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
            @if($dua->meaning_explanation)
            <div class="p-6 rounded-3xl bg-slate-50 border border-slate-100 space-y-3">
                <div class="flex items-center space-x-2 text-emerald-700 font-black text-sm">
                    <span>💡</span>
                    <span>Linguistic & Spiritual Meaning</span>
                </div>
                <p class="text-slate-700 text-sm leading-relaxed font-medium">
                    {{ $dua->meaning_explanation }}
                </p>
            </div>
            @endif

            @if($dua->benefits_and_virtues)
            <div class="p-6 rounded-3xl bg-amber-50/40 border border-amber-100/70 space-y-3">
                <div class="flex items-center space-x-2 text-amber-800 font-black text-sm">
                    <span>🏆</span>
                    <span>Authentic Virtues & Rewards</span>
                </div>
                <p class="text-slate-700 text-sm leading-relaxed font-medium">
                    {{ $dua->benefits_and_virtues }}
                </p>
            </div>
            @endif
        </div>
        @endif

        {{-- Detailed Authenticity References --}}
        <div class="p-6 rounded-3xl bg-slate-50 border border-slate-200 space-y-4">
            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Authentic Citations & Cross-References</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if($dua->quran_reference)
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                    <div class="text-xs font-bold text-emerald-700 uppercase tracking-wider flex items-center space-x-1">
                        <span>📖</span>
                        <span>Quran Citation</span>
                    </div>
                    <div class="text-base font-black text-slate-900">
                        {{ $dua->quran_reference }}
                    </div>
                    @if($dua->quran_lens_url)
                    <div class="pt-2">
                        <a href="{{ $dua->quran_lens_url }}" class="inline-flex items-center space-x-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-900 underline">
                            <span>Open in Quranic Lens (Verse Explorer)</span>
                            <span>↗</span>
                        </a>
                    </div>
                    @endif
                </div>
                @endif

                @if($dua->hadith_reference)
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                    <div class="text-xs font-bold text-amber-700 uppercase tracking-wider flex items-center space-x-1">
                        <span>📜</span>
                        <span>Hadith Citation</span>
                    </div>
                    <div class="text-base font-black text-slate-900">
                        {{ $dua->hadith_reference }}
                    </div>
                    @if($dua->hadith_grading)
                    <span class="inline-block bg-amber-100 text-amber-900 text-xs font-bold px-2 py-0.5 rounded">
                        Status: {{ $dua->hadith_grading }}
                    </span>
                    @endif
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Related Duas in Same Category --}}
    @if($relatedDuas->isNotEmpty())
    <div class="space-y-4">
        <h3 class="text-xl font-black text-slate-900">More Duas in {{ $dua->category }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($relatedDuas as $rel)
            <a href="{{ route('duas.show', $rel->slug) }}" class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-300 transition flex flex-col justify-between space-y-4 group">
                <div class="space-y-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full">
                        {{ $rel->category }}
                    </span>
                    <h4 class="text-sm font-bold text-slate-900 group-hover:text-emerald-700 transition line-clamp-2">
                        {{ $rel->title }}
                    </h4>
                </div>
                <p class="font-arabic text-right text-lg text-slate-800 line-clamp-1" dir="rtl">
                    {{ $rel->arabic_text }}
                </p>
                <div class="text-xs font-bold text-emerald-700 flex items-center space-x-1">
                    <span>Explore Dua</span>
                    <span>→</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
