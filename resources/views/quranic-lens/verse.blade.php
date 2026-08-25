@extends('layouts.app')

@section('title', "Surah {$surahInfo['name_simple']} — Verse {$verse} Analysis")

@section('content')
<div class="space-y-10 animate-fadeIn" x-data="verseAnalysisPage()">
    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="bg-emerald-50 text-emerald-800 border border-emerald-100 p-4 rounded-2xl text-sm font-semibold flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 text-rose-800 border border-rose-100 p-4 rounded-2xl text-sm font-semibold flex items-center space-x-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Breadcrumbs --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2 text-xs font-black uppercase tracking-widest text-slate-400">
            <a href="{{ route('lens.index') }}" class="hover:text-emerald-600 transition-colors">Quranic Lens</a>
            <span>/</span>
            <a href="{{ route('lens.surah', $surahInfo['id']) }}" class="hover:text-emerald-600 transition-colors">{{ $surahInfo['name_simple'] }}</a>
            <span>/</span>
            <span class="text-slate-600">Verse {{ $verse }}</span>
        </div>
        
        <div class="flex space-x-2">
            @if($verse > 1)
                <a href="{{ route('lens.verse', [$surahInfo['id'], $verse - 1]) }}" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-black border border-slate-100 shadow-sm transition-all">&larr; Previous Verse</a>
            @endif
            @if($verse < $surahInfo['verses_count'])
                <a href="{{ route('lens.verse', [$surahInfo['id'], $verse + 1]) }}" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-black border border-slate-100 shadow-sm transition-all">Next Verse &rarr;</a>
            @endif
        </div>
    </div>

    {{-- Verse Details Card --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 p-8 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-slate-50 pb-4">
            <span class="text-xs font-black uppercase tracking-widest text-slate-400">Verse Text</span>
            <div class="flex items-center space-x-2">
                @if(isset($juz))
                    <span class="text-[10px] font-black uppercase tracking-widest bg-indigo-50 text-indigo-800 border border-indigo-100 px-3 py-1 rounded-full">
                        Para {{ $juz }}
                    </span>
                @endif
                <span class="text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-800 border border-emerald-100 px-3 py-1 rounded-full">
                    Surah {{ $surahInfo['id'] }} : {{ $verse }}
                </span>
            </div>
        </div>

        {{-- Uthmani Arabic Text --}}
        <div class="text-right font-arabic text-3xl md:text-4xl font-bold text-slate-800 leading-loose py-4" dir="rtl">
            {{ $verseDetail['text_uthmani'] }}
        </div>

        {{-- English Translation --}}
        <div class="text-slate-600 font-medium text-base leading-relaxed border-t border-slate-50 pt-4 max-w-4xl">
            <p class="italic text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Dr. Mustafa Khattab (The Clear Quran)</p>
            {{ strip_tags($verseDetail['translations'][0]['text'] ?? '') }}
        </div>
    </div>

    {{-- Word-by-Word Mapping & Tagging --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 p-8 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-50 pb-4">
            <div>
                <h3 class="text-xl font-black text-slate-900">Word-by-Word Analysis</h3>
                <p class="text-xs text-slate-400 font-bold">Click any word below to tag it with linguistic or thematic terminology.</p>
            </div>
        </div>

        {{-- Words Grid --}}
        <div class="flex flex-wrap gap-4 justify-end pt-4" dir="rtl">
            @foreach($verseDetail['words'] as $index => $word)
                @php
                    $wordPos = $word['position'];
                    $hasTags = isset($wordTags[$wordPos]);
                @endphp
                {{-- Word Badge Card --}}
                <div @click="openWordTagModal({{ $wordPos }}, '{{ addslashes($word['text_uthmani']) }}')"
                    class="bg-slate-50/50 hover:bg-emerald-50/40 rounded-2xl p-4 border-2 {{ $hasTags ? 'border-emerald-500/20 bg-emerald-50/10' : 'border-slate-100' }} hover:border-emerald-500/40 transition-all duration-200 cursor-pointer text-center min-w-[100px] shrink-0 group relative">
                    
                    {{-- Arabic Word --}}
                    <span class="font-arabic text-2xl font-bold text-slate-800 block mb-1 group-hover:text-emerald-700 transition-colors">
                        {{ $word['text_uthmani'] }}
                    </span>

                    {{-- Transliteration --}}
                    <span class="text-[10px] text-slate-400 font-bold block mb-1" dir="ltr">
                        {{ $word['transliteration']['text'] ?? '' }}
                    </span>

                    {{-- English Translation --}}
                    <span class="text-xs text-slate-500 font-medium block" dir="ltr">
                        {{ $word['translation']['text'] ?? '' }}
                    </span>

                    {{-- Word tags indicator pills --}}
                    @if($hasTags)
                        <div class="flex flex-wrap gap-1 justify-center mt-2.5" dir="ltr">
                            @foreach($wordTags[$wordPos] as $tag)
                                @php
                                    $tagEmoji = '';
                                    if ($tag->tag_type === 'science' && isset($scienceCategories)) {
                                        foreach ($scienceCategories as $cat) {
                                            if ($cat['label'] === $tag->tag_value) {
                                                $tagEmoji = $cat['emoji'] . ' ';
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                <span class="text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-full {{ $tag->tag_type === 'grammar' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : ($tag->tag_type === 'science' ? 'bg-purple-50 text-purple-700 border border-purple-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100') }}"
                                     title="{{ $tag->explanation }}">
                                     {{ $tagEmoji }}{{ $tag->tag_value }}
                                 </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Verse Tags & Meta --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Tags Column --}}
        <div class="lg:col-span-4 bg-white rounded-[2.5rem] border border-slate-100 p-8 shadow-sm space-y-6 self-start">
            <div class="flex items-center justify-between border-b border-slate-50 pb-4">
                <h3 class="text-lg font-black text-slate-900">Verse Tags</h3>
                @auth
                    <button @click="showVerseTagModal = true" class="text-xs font-black text-emerald-600 hover:text-emerald-700 uppercase tracking-widest">+ Add Tag</button>
                @endauth
            </div>

            @if($verseTags->count() > 0)
                <div class="flex flex-wrap gap-2.5">
                    @foreach($verseTags as $tag)
                        @php
                            $tagEmoji = '';
                            if ($tag->tag_type === 'science' && isset($scienceCategories)) {
                                foreach ($scienceCategories as $cat) {
                                    if ($cat['label'] === $tag->tag_value) {
                                        $tagEmoji = $cat['emoji'] . ' ';
                                        break;
                                    }
                                }
                            }
                        @endphp
                        <div class="group relative flex items-center bg-slate-50 border border-slate-100 rounded-2xl px-4 py-2 hover:border-emerald-500/20 transition-all"
                            title="{{ $tag->explanation }}">
                            <div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 block">{{ $tag->tag_type }}</span>
                                <span class="text-xs font-bold text-slate-700">{{ $tagEmoji }}{{ $tag->tag_value }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6 text-slate-400 font-bold text-sm">
                    No approved tags for this verse yet.
                </div>
            @endif
        </div>

        {{-- Lenses Column --}}
        <div class="lg:col-span-8 bg-white rounded-[2.5rem] border border-slate-100 p-8 shadow-sm space-y-6">
            {{-- Tabs --}}
            <div class="flex flex-wrap border-b border-slate-100 pb-2 gap-1">
                <template x-for="lens in lenses">
                    <button @click="activeLens = lens.id"
                        :class="activeLens === lens.id ? 'border-emerald-600 text-emerald-700 bg-emerald-50/30' : 'border-transparent text-slate-400 hover:text-slate-600 hover:bg-slate-50'"
                        class="px-5 py-3 rounded-2xl text-xs font-black uppercase tracking-widest border-2 transition-all"
                        x-text="lens.label"></button>
                </template>
            </div>

            {{-- Tab Content --}}
            <div class="space-y-6">
                {{-- Local Database and Community Analyses Display --}}
                <div class="space-y-6">
                    {{-- Local database Tafsirs --}}
                    @if($localTafsir->count() > 0)
                        <div x-show="activeLens === 'tafsir'" class="space-y-4" x-cloak>
                            @foreach($localTafsir as $taf)
                                <div class="p-6 rounded-3xl border border-slate-100 bg-amber-50/10 hover:border-amber-200 transition-all space-y-3">
                                    <div class="flex items-center justify-between border-b border-amber-50 pb-2">
                                        <span class="text-xs font-black text-amber-700 uppercase tracking-widest bg-amber-50/80 border border-amber-100/50 px-3 py-1 rounded-full">
                                            Classical Tafsir: {{ $taf->tafsir_source }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $taf->language }}</span>
                                    </div>
                                    <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line font-medium">{{ $taf->text }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Local database Hadith links --}}
                    @if($localHadith->count() > 0)
                        <div x-show="activeLens === 'hadith'" class="space-y-4" x-cloak>
                            @foreach($localHadith as $had)
                                <div class="p-6 rounded-3xl border border-slate-100 bg-emerald-50/10 hover:border-emerald-200 transition-all space-y-4">
                                    <div class="flex items-center justify-between border-b border-emerald-50 pb-3">
                                        <div>
                                            <span class="text-xs font-black text-emerald-800 uppercase tracking-widest bg-emerald-50/80 border border-emerald-100/50 px-3 py-1 rounded-full">
                                                {{ $had->collection_name }} ({{ $had->hadith_number }})
                                            </span>
                                            @if($had->book_name)
                                                <span class="text-xs text-slate-400 font-bold ml-2">Book: {{ $had->book_name }}</span>
                                            @endif
                                        </div>
                                        @if($had->grading)
                                            <span class="text-[10px] font-black uppercase tracking-widest rounded-full px-2.5 py-1 {{ str_contains(strtolower($had->grading), 'sahih') ? 'bg-emerald-100/80 text-emerald-950 border border-emerald-200/50' : 'bg-amber-100/80 text-amber-950 border border-amber-200/50' }}">
                                                {{ $had->grading }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($had->narrator_chain)
                                        <p class="text-xs text-slate-500 font-bold italic">Narrated by: {{ $had->narrator_chain }}</p>
                                    @endif
                                    @if($had->text_arabic)
                                        <p class="text-right font-arabic text-xl font-bold text-slate-800 leading-loose py-2" dir="rtl">{{ $had->text_arabic }}</p>
                                    @endif
                                    @if($had->text_english)
                                        <p class="text-slate-700 text-sm leading-relaxed font-medium">{{ $had->text_english }}</p>
                                    @endif
                                    @if($had->link_description)
                                        <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                            <span class="font-bold uppercase tracking-wider text-[10px] block text-slate-400 mb-1">Relevance Context</span>
                                            {{ $had->link_description }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Local database Seerat links --}}
                    @if($localSeerat->count() > 0)
                        <div x-show="activeLens === 'seerat'" class="space-y-4" x-cloak>
                            @foreach($localSeerat as $seer)
                                <div class="p-6 rounded-3xl border border-slate-100 bg-blue-50/10 hover:border-blue-200 transition-all space-y-4">
                                    <div class="flex items-center justify-between border-b border-blue-50 pb-3">
                                        <span class="text-xs font-black text-blue-800 uppercase tracking-widest bg-blue-50/80 border border-blue-100/50 px-3 py-1 rounded-full">
                                            Seerat Event: {{ $seer->title }}
                                        </span>
                                        @if($seer->date_hijri || $seer->date_ce)
                                            <span class="text-[10px] text-slate-400 font-black tracking-widest uppercase">
                                                {{ $seer->date_hijri ? $seer->date_hijri . ' AH' : '' }} {{ $seer->date_ce ? '/ ' . $seer->date_ce : '' }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-slate-700 text-sm leading-relaxed">{{ $seer->description }}</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-bold text-slate-500 pt-2 border-t border-slate-50">
                                        @if($seer->location)
                                            <div>📍 Location: <span class="text-slate-700">{{ $seer->location }}</span></div>
                                        @endif
                                        @if($seer->category)
                                            <div>🏷️ Category: <span class="text-slate-700 capitalize">{{ $seer->category }}</span></div>
                                        @endif
                                        @if($seer->source_book)
                                            <div class="col-span-1 sm:col-span-2">📚 Source: <span class="text-slate-700">{{ $seer->source_book }} {{ $seer->source_reference }}</span></div>
                                        @endif
                                    </div>
                                    @if($seer->link_description)
                                        <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                            <span class="font-bold uppercase tracking-wider text-[10px] block text-slate-400 mb-1">Relevance Context</span>
                                            {{ $seer->link_description }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Local database Science links --}}
                    @if($localScience->count() > 0)
                        <div x-show="activeLens === 'science'" class="space-y-4" x-cloak>
                            @foreach($localScience as $sci)
                                <div class="p-6 rounded-3xl border border-slate-100 bg-purple-50/10 hover:border-purple-200 transition-all space-y-4">
                                    <div class="flex items-center justify-between border-b border-purple-50 pb-3">
                                        <span class="text-xs font-black text-purple-800 uppercase tracking-widest bg-purple-50/80 border border-purple-100/50 px-3 py-1 rounded-full">
                                            {{ $sci->title }}
                                        </span>
                                        @if($sci->field)
                                            @php
                                                $fLower = strtolower($sci->field);
                                                $displayLabel = $sci->field;
                                                $emoji = '🔬';
                                                
                                                if (isset($scienceCategories)) {
                                                    foreach ($scienceCategories as $cat) {
                                                        if (in_array($fLower, $cat['fields'])) {
                                                            $displayLabel = $cat['label'];
                                                            $emoji = $cat['emoji'];
                                                            break;
                                                        }
                                                    }
                                                } else {
                                                    if ($fLower === 'astronomy' || $fLower === 'cosmology') $emoji = '🪐';
                                                    elseif ($fLower === 'geology') $emoji = '🪨';
                                                    elseif ($fLower === 'neuroscience' || $fLower === 'psychology') $emoji = '🧠';
                                                    elseif ($fLower === 'biology') $emoji = '🧬';
                                                    elseif ($fLower === 'embryology') $emoji = '🍼';
                                                    elseif ($fLower === 'oceanography') $emoji = '🌊';
                                                    elseif ($fLower === 'hydrology') $emoji = '💧';
                                                    elseif ($fLower === 'meteorology') $emoji = '🌀';
                                                    elseif ($fLower === 'physics') $emoji = '⚡';
                                                }
                                            @endphp
                                            <span class="text-[10px] text-purple-600 bg-purple-50/80 border border-purple-100/50 rounded-full px-2.5 py-0.5 font-bold uppercase tracking-widest flex items-center gap-1 select-none">
                                                <span>{{ $emoji }}</span>
                                                <span>{{ $displayLabel }}</span>
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-slate-700 text-sm leading-relaxed font-medium">{{ $sci->description }}</p>
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500 pt-2 border-t border-slate-50">
                                        @if($sci->source_name)
                                            <div>🔬 Study Source: <span class="font-bold text-slate-700">{{ $sci->source_name }}</span></div>
                                        @endif
                                        @if($sci->credibility_score)
                                            <div>Credibility Score: <span class="font-black text-purple-700 bg-purple-50 border border-purple-100 px-2.5 py-0.5 rounded-lg">{{ $sci->credibility_score }}/10</span></div>
                                        @endif
                                    </div>
                                    @if($sci->link_description)
                                        <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                            <span class="font-bold uppercase tracking-wider text-[10px] block text-slate-400 mb-1">Relevance Context</span>
                                            {{ $sci->link_description }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Local database History links --}}
                    @if($localHistory->count() > 0)
                        <div x-show="activeLens === 'history'" class="space-y-4" x-cloak>
                            @foreach($localHistory as $hist)
                                <div class="p-6 rounded-3xl border border-slate-100 bg-orange-50/10 hover:border-orange-200 transition-all space-y-4">
                                    <div class="flex items-center justify-between border-b border-orange-50 pb-3">
                                        <span class="text-xs font-black text-orange-800 uppercase tracking-widest bg-orange-50/80 border border-orange-100/50 px-3 py-1 rounded-full">
                                            Historical Context: {{ $hist->title }}
                                        </span>
                                        @if($hist->date_range)
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $hist->date_range }}</span>
                                        @endif
                                    </div>
                                    <p class="text-slate-700 text-sm leading-relaxed font-medium">{{ $hist->description }}</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-bold text-slate-500 pt-2 border-t border-slate-50">
                                        @if($hist->civilization)
                                            <div>🏛️ Civilization: <span class="text-slate-700">{{ $hist->civilization }}</span></div>
                                        @endif
                                        @if($hist->region)
                                            <div>📍 Region: <span class="text-slate-700">{{ $hist->region }}</span></div>
                                        @endif
                                    </div>
                                    @if($hist->link_description)
                                        <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                            <span class="font-bold uppercase tracking-wider text-[10px] block text-slate-400 mb-1">Relevance Context</span>
                                            {{ $hist->link_description }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Local database Bible links --}}
                    @if($localBible->count() > 0)
                        <div x-show="activeLens === 'bible'" class="space-y-4" x-cloak>
                            @foreach($localBible as $bib)
                                <div class="p-6 rounded-3xl border border-slate-100 bg-rose-50/10 hover:border-rose-200 transition-all space-y-3">
                                    <div class="flex items-center justify-between border-b border-rose-50 pb-2">
                                        <span class="text-xs font-black text-rose-800 uppercase tracking-widest bg-rose-50/80 border border-rose-100/50 px-3 py-1 rounded-full">
                                            Biblical Parallel: {{ $bib->book }} {{ $bib->chapter }}:{{ $bib->verse_number }}
                                        </span>
                                        @if($bib->testament)
                                            <span class="text-[9px] font-black uppercase tracking-widest bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded-full">{{ $bib->testament }}</span>
                                        @endif
                                    </div>
                                    @if($bib->text_kjv)
                                        <p class="text-slate-700 text-sm leading-relaxed font-medium italic">"{{ $bib->text_kjv }}" <span class="text-[10px] font-bold text-slate-400 not-italic uppercase tracking-widest">(KJV)</span></p>
                                    @endif
                                    @if($bib->text_niv)
                                        <p class="text-slate-700 text-sm leading-relaxed font-medium italic">"{{ $bib->text_niv }}" <span class="text-[10px] font-bold text-slate-400 not-italic uppercase tracking-widest">(NIV)</span></p>
                                    @endif
                                    @if($bib->link_description)
                                        <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                            <span class="font-bold uppercase tracking-wider text-[10px] block text-slate-400 mb-1">Relevance Context</span>
                                            {{ $bib->link_description }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Local database Torah links --}}
                    @if($localTorah->count() > 0)
                        <div x-show="activeLens === 'torah'" class="space-y-4" x-cloak>
                            @foreach($localTorah as $tor)
                                <div class="p-6 rounded-3xl border border-slate-100 bg-rose-50/10 hover:border-rose-200 transition-all space-y-3">
                                    <div class="flex items-center justify-between border-b border-rose-50 pb-2">
                                        <span class="text-xs font-black text-rose-800 uppercase tracking-widest bg-rose-50/80 border border-rose-100/50 px-3 py-1 rounded-full">
                                            Torah Parallel: {{ $tor->book }} {{ $tor->chapter }}:{{ $tor->verse_number }}
                                        </span>
                                    </div>
                                    @if($tor->text_hebrew)
                                        <p class="text-right font-serif text-lg font-bold text-slate-800 leading-loose py-1" dir="rtl">{{ $tor->text_hebrew }}</p>
                                    @endif
                                    @if($tor->text_english)
                                        <p class="text-slate-700 text-sm leading-relaxed font-medium italic">"{{ $tor->text_english }}"</p>
                                    @endif
                                    @if($tor->link_description)
                                        <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                            <span class="font-bold uppercase tracking-wider text-[10px] block text-slate-400 mb-1">Relevance Context</span>
                                            {{ $tor->link_description }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Community Submitted Analyses --}}
                    @foreach($analyses as $analysis)
                        <div x-show="activeLens === '{{ $analysis->lens_type }}'" class="p-6 rounded-3xl border border-slate-100 bg-slate-50/50 space-y-3" x-cloak>
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-800 text-base">{{ $analysis->title }}</h4>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">By {{ $analysis->user ? ($analysis->user->display_name ?: explode('@', $analysis->user->email)[0]) : 'System' }}</span>
                            </div>
                            <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-line">{{ $analysis->content }}</p>

                            @if($analysis->theme)
                                <div class="mt-4 p-5 rounded-2xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-100/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="space-y-1">
                                        <span class="text-[8px] font-black uppercase tracking-widest text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">Connected Theme Quiz</span>
                                        <h5 class="text-xs font-black text-slate-800">{{ $analysis->theme->name }} Challenge</h5>
                                        <p class="text-[10px] text-slate-500 font-semibold">Test your knowledge with thematic questions dynamically loaded or generated by AI.</p>
                                    </div>
                                    <form action="{{ route('quiz.theme') }}" method="POST" class="flex items-center gap-1.5 shrink-0">
                                        @csrf
                                        <input type="hidden" name="theme_id" value="{{ $analysis->theme->id }}">
                                        <input type="hidden" name="difficulty" value="Medium">
                                        <input type="hidden" name="quantity" value="20">
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-[10px] uppercase tracking-widest px-4 py-2.5 rounded-xl transition-all shadow-sm flex items-center gap-1 hover:-translate-y-0.5">
                                            <span>Start Challenge</span>
                                            <span>🎯</span>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Empty State --}}
                    <div x-show="!hasDataForActiveLens()" class="text-center py-12 bg-slate-50/50 rounded-3xl border border-dashed border-slate-200 space-y-3">
                        <div class="text-3xl">🔍</div>
                        <h4 class="font-bold text-slate-700 text-sm">No analysis published under this lens</h4>
                        <p class="text-xs text-slate-400 max-w-xs mx-auto leading-relaxed">Submit a research-backed analysis or ask AI to start mapping this perspective.</p>
                    </div>
                </div>

                {{-- Action Panel for Submissions --}}
                @auth
                    <div class="border-t border-slate-50 pt-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">Collaborate on this Lens</h4>
                            <div class="flex space-x-2">
                                <button type="button" @click="generateAiDraft()" :disabled="aiGenerating"
                                    class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-4 py-2 rounded-xl text-xs font-black border border-indigo-100 transition-all flex items-center gap-1.5 disabled:opacity-50">
                                    <span x-text="aiGenerating ? 'AI Analyzing...' : 'Ask AI to Draft'"></span>
                                </button>
                                <button @click="showSubmitForm = !showSubmitForm"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-black transition-all">
                                    <span x-text="showSubmitForm ? 'Hide Form' : 'Submit Analysis'"></span>
                                </button>
                            </div>
                        </div>

                        {{-- Submission Form --}}
                        <div x-show="showSubmitForm" x-transition class="bg-slate-50 border border-slate-100 p-6 rounded-3xl space-y-4">
                            <form action="{{ route('lens.analysis.store') }}" method="POST" class="space-y-4">
                                @csrf
                                <input type="hidden" name="chapter_number" value="{{ $chapter }}">
                                <input type="hidden" name="verse_number" value="{{ $verse }}">
                                <input type="hidden" name="lens_type" :value="activeLens">

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Analysis Title</label>
                                    <input type="text" name="title" x-model="formTitle" placeholder="e.g., Theological Context, Scientific Parallel..." required
                                        class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-white font-bold text-slate-700 focus:border-emerald-500 outline-none transition-all text-sm" />
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Content (Research & Citations)</label>
                                    <textarea name="content" x-model="formContent" rows="6" placeholder="Provide details, referencing authentic scholars or research articles..." required
                                        class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-white font-medium text-slate-700 focus:border-emerald-500 outline-none transition-all text-sm leading-relaxed"></textarea>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Link to Quiz Theme (Optional)</label>
                                    <select name="theme_id" class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-white font-bold text-slate-700 focus:border-emerald-500 outline-none transition-all text-sm">
                                        <option value="">-- Select Quiz Theme --</option>
                                        @foreach($themes as $theme)
                                            <option value="{{ $theme->id }}">{{ $theme->name }} ({{ $theme->type }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-600/10 hover:shadow-emerald-600/20 hover:-translate-y-0.5 transition-all">
                                        Submit for Moderation
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4 bg-amber-50 rounded-2xl border border-amber-100 text-xs font-bold text-amber-800">
                        Please <a href="{{ route('login') }}" class="underline hover:text-amber-900">login</a> to participate in mapping this verse.
                    </div>
                @endauth
            </div>
        </div>
    </div>

    {{-- Word Tag Modal --}}
    <div x-show="showWordTagModal" x-cloak
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] flex items-center justify-center p-6 animate-fadeIn"
        @click.self="showWordTagModal = false">
        <div class="bg-white rounded-[2.5rem] p-8 max-w-lg w-full shadow-2xl space-y-6 animate-slideUp">
            <div class="text-center space-y-2 border-b border-slate-50 pb-4">
                <span class="text-xs font-black text-emerald-600 uppercase tracking-widest">Tag Individual Word</span>
                <h3 class="text-2xl font-black text-slate-900" x-text="'Tag Word: ' + selectedWordText"></h3>
            </div>
            
            <form action="{{ route('lens.tag.word.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="chapter_number" value="{{ $chapter }}">
                <input type="hidden" name="verse_number" value="{{ $verse }}">
                <input type="hidden" name="word_position" :value="selectedWordPosition">
                <input type="hidden" name="word_text" :value="selectedWordText">

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Tag Type</label>
                    <select name="tag_type" required x-model="selectedWordTagType"
                        class="w-full p-4 rounded-2xl border-2 border-slate-100 font-bold text-slate-700 focus:border-emerald-500 outline-none transition-all appearance-none cursor-pointer text-sm">
                        <option value="grammar">Grammar (e.g. Ism, Fi'l, Harf)</option>
                        <option value="root_word">Root Word (e.g. letters like k-t-b)</option>
                        <option value="thematic">Thematic Tag</option>
                        <option value="science">Scientific Category</option>
                        <option value="custom">Custom Tag</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Tag Value</label>
                    
                    <!-- Text input for non-science tags -->
                    <input x-show="selectedWordTagType !== 'science'" type="text" name="tag_value" placeholder="e.g. Fi'l Madhi, k-t-b, Mercy" :required="selectedWordTagType !== 'science'" :disabled="selectedWordTagType === 'science'"
                        class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-white font-bold text-slate-700 focus:border-emerald-500 outline-none transition-all text-sm" />
                    
                    <!-- Select dropdown for science categories -->
                    <select x-show="selectedWordTagType === 'science'" name="tag_value" :disabled="selectedWordTagType !== 'science'" :required="selectedWordTagType === 'science'"
                        class="w-full p-4 rounded-2xl border-2 border-slate-100 font-bold text-slate-700 focus:border-emerald-500 outline-none transition-all appearance-none cursor-pointer text-sm" x-cloak>
                        <option value="">-- Select Scientific Category --</option>
                        @foreach($scienceCategories as $key => $cat)
                            <option value="{{ $cat['label'] }}">{{ $cat['emoji'] }} {{ $cat['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Explanation (Brief rationale)</label>
                    <textarea name="explanation" rows="3" placeholder="Provide a brief explanation of why this tag applies..."
                        class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-white font-medium text-slate-700 focus:border-emerald-500 outline-none transition-all text-sm"></textarea>
                </div>

                <div class="flex gap-3 pt-4 border-t border-slate-50">
                    <button type="button" @click="showWordTagModal = false"
                        class="flex-1 py-4 border-2 border-slate-100 hover:border-rose-500 rounded-2xl text-slate-500 hover:text-rose-500 font-black uppercase text-xs tracking-widest transition-all">Cancel</button>
                    <button type="submit"
                        class="flex-1 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-emerald-600/10 transition-all">Submit Tag</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Verse Tag Modal --}}
    <div x-show="showVerseTagModal" x-cloak
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] flex items-center justify-center p-6 animate-fadeIn"
        @click.self="showVerseTagModal = false">
        <div class="bg-white rounded-[2.5rem] p-8 max-w-lg w-full shadow-2xl space-y-6 animate-slideUp">
            <div class="text-center space-y-2 border-b border-slate-50 pb-4">
                <span class="text-xs font-black text-emerald-600 uppercase tracking-widest">Tag Verse</span>
                <h3 class="text-2xl font-black text-slate-900">Add Verse Tag</h3>
            </div>
            
            <form action="{{ route('lens.tag.verse.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="chapter_number" value="{{ $chapter }}">
                <input type="hidden" name="verse_number" value="{{ $verse }}">

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Tag Type</label>
                    <select name="tag_type" required x-model="selectedVerseTagType"
                        class="w-full p-4 rounded-2xl border-2 border-slate-100 font-bold text-slate-700 focus:border-emerald-500 outline-none transition-all appearance-none cursor-pointer text-sm">
                        <option value="theme">Thematic (e.g. Creation, Justice)</option>
                        <option value="law">Jurisprudence (Law/Shariah)</option>
                        <option value="theology">Theological (Aqeedah)</option>
                        <option value="prophecy">Prophecies & History</option>
                        <option value="science">Scientific Category</option>
                        <option value="custom">Custom Tag</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Tag Value</label>
                    
                    <!-- Text input for non-science tags -->
                    <input x-show="selectedVerseTagType !== 'science'" type="text" name="tag_value" placeholder="e.g. Monotheism, Charity, Laws of War" :required="selectedVerseTagType !== 'science'" :disabled="selectedVerseTagType === 'science'"
                        class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-white font-bold text-slate-700 focus:border-emerald-500 outline-none transition-all text-sm" />
                    
                    <!-- Select dropdown for science categories -->
                    <select x-show="selectedVerseTagType === 'science'" name="tag_value" :disabled="selectedVerseTagType !== 'science'" :required="selectedVerseTagType === 'science'"
                        class="w-full p-4 rounded-2xl border-2 border-slate-100 font-bold text-slate-700 focus:border-emerald-500 outline-none transition-all appearance-none cursor-pointer text-sm" x-cloak>
                        <option value="">-- Select Scientific Category --</option>
                        @foreach($scienceCategories as $key => $cat)
                            <option value="{{ $cat['label'] }}">{{ $cat['emoji'] }} {{ $cat['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Explanation (Brief rationale)</label>
                    <textarea name="explanation" rows="3" placeholder="Explain the significance of this tag in this verse..."
                        class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-white font-medium text-slate-700 focus:border-emerald-500 outline-none transition-all text-sm"></textarea>
                </div>

                <div class="flex gap-3 pt-4 border-t border-slate-50">
                    <button type="button" @click="showVerseTagModal = false"
                        class="flex-1 py-4 border-2 border-slate-100 hover:border-rose-500 rounded-2xl text-slate-500 hover:text-rose-500 font-black uppercase text-xs tracking-widest transition-all">Cancel</button>
                    <button type="submit"
                        class="flex-1 py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-emerald-600/10 transition-all">Submit Tag</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function verseAnalysisPage() {
        return {
            activeLens: (new URLSearchParams(window.location.search)).get('lens') || 'science',
            showSubmitForm: false,
            showWordTagModal: false,
            showVerseTagModal: false,
            selectedWordPosition: null,
            selectedWordText: '',
            selectedWordTagType: 'grammar',
            selectedVerseTagType: 'theme',
            
            formTitle: '',
            formContent: '',
                    lenses: [
                { id: 'tafsir', label: 'Tafsir' },
                { id: 'hadith', label: 'Hadith' },
                { id: 'seerat', label: 'Seerah' },
                { id: 'science', label: 'Science' },
                { id: 'biology', label: 'Biology' },
                { id: 'maths', label: 'Mathematics' },
                { id: 'history', label: 'History' },
                { id: 'bible', label: 'Bible' },
                { id: 'torah', label: 'Torah' },
                { id: 'psychology', label: 'Psychology' }
            ],

            approvedAnalenses: @json($analyses),

            hasDataForActiveLens() {
                if (this.approvedAnalenses.some(a => a.lens_type === this.activeLens)) {
                    return true;
                }
                if (this.activeLens === 'tafsir' && {{ $localTafsir->count() }} > 0) return true;
                if (this.activeLens === 'hadith' && {{ $localHadith->count() }} > 0) return true;
                if (this.activeLens === 'seerat' && {{ $localSeerat->count() }} > 0) return true;
                if (this.activeLens === 'science' && {{ $localScience->count() }} > 0) return true;
                if (this.activeLens === 'history' && {{ $localHistory->count() }} > 0) return true;
                if (this.activeLens === 'bible' && {{ $localBible->count() }} > 0) return true;
                if (this.activeLens === 'torah' && {{ $localTorah->count() }} > 0) return true;
                return false;
            },

            openWordTagModal(position, text) {
                @auth
                    this.selectedWordPosition = position;
                    this.selectedWordText = text;
                    this.showWordTagModal = true;
                @else
                    alert('Please login to tag individual words.');
                @endauth
            },

            generateAiDraft() {
                this.aiGenerating = true;
                axios.post("{{ route('lens.analysis.ai') }}", {
                    chapter_number: {{ $chapter }},
                    verse_number: {{ $verse }},
                    arabic: "{{ addslashes($verseDetail['text_uthmani']) }}",
                    translation: "{{ addslashes(strip_tags($verseDetail['translations'][0]['text'] ?? '')) }}",
                    lens_type: this.activeLens
                })
                .then(response => {
                    if (response.data.success) {
                        this.formTitle = 'AI Draft Analysis - ' + this.activeLens.toUpperCase();
                        this.formContent = response.data.analysis;
                        this.showSubmitForm = true;
                    }
                })
                .catch(error => {
                    alert(error.response?.data?.message || 'Error occurred while generating AI analysis.');
                })
                .finally(() => {
                    this.aiGenerating = false;
                });
            }
        };
    }
</script>
@endpush
