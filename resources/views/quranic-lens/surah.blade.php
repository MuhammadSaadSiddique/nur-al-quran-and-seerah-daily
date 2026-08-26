@extends('layouts.app')

@section('title', $surahInfo['name_simple'] . ' - Quranic Lens')

@section('content')
<div class="space-y-8 animate-fadeIn">
    {{-- Breadcrumbs & Navigation --}}
    <div class="flex items-center space-x-2 text-xs font-black uppercase tracking-widest text-slate-400">
        <a href="{{ route('lens.index') }}" class="hover:text-emerald-600 transition-colors">Quranic Lens</a>
        <span>/</span>
        <span class="text-slate-600">{{ $surahInfo['name_simple'] }}</span>
    </div>

    {{-- Surah Header Card --}}
    <div class="bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 rounded-[3rem] p-10 text-white shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center md:justify-between gap-8">
        <div class="space-y-4">
            <div class="inline-flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-1 border border-white/10 text-[10px] font-black tracking-widest uppercase">
                <span>Surah {{ $surahInfo['id'] }}</span>
                <span>•</span>
                <span>{{ $surahInfo['verses_count'] }} Verses</span>
                <span>•</span>
                <span class="text-emerald-300 font-bold">{{ $surahInfo['revelation_place'] }}</span>
            </div>
            <h2 class="text-4xl md:text-5xl font-black tracking-tight flex items-baseline gap-3">
                {{ $surahInfo['name_simple'] }}
                <span class="text-lg text-emerald-200 font-medium font-sans">({{ $surahInfo['translated_name']['name'] }})</span>
            </h2>
        </div>
        <div class="text-right">
            <span class="font-arabic text-5xl md:text-6xl text-emerald-300 font-bold block">
                {{ $surahInfo['name_arabic'] }}
            </span>
        </div>
    </div>

    {{-- Bismillah Banner (exclude Surah At-Tawbah and Surah Al-Fatihah, as Al-Fatihah has Bismillah as verse 1) --}}
    @if($surahInfo['id'] !== 1 && $surahInfo['id'] !== 9)
        <div class="text-center py-6 font-arabic text-3xl font-bold text-slate-700 bg-white border border-slate-100 rounded-3xl shadow-sm">
            بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ
        </div>
    @endif

    {{-- Verses List --}}
    <div class="space-y-4">
        @foreach($verses as $verse)
            <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm hover:shadow-md hover:border-emerald-500/25 transition-all duration-300 flex flex-col md:flex-row md:items-start gap-6 relative group">
                {{-- Verse Number Badge --}}
                <div class="w-14 h-14 bg-slate-50 rounded-xl flex flex-col items-center justify-center font-black text-slate-400 shrink-0 group-hover:bg-emerald-50 group-hover:text-emerald-700 transition-colors border border-slate-100 p-1">
                    <span class="text-sm leading-none">{{ $verse['verse_number'] }}</span>
                    @if(isset($verse['juz']))
                        <span class="text-[8px] font-bold text-slate-400 mt-1">Para {{ $verse['juz'] }}</span>
                    @endif
                </div>

                {{-- Verse Content --}}
                <div class="flex-grow space-y-4">
                    {{-- Arabic text --}}
                    <div class="text-right font-arabic text-2xl md:text-3xl font-bold text-slate-800 leading-loose" dir="rtl">
                        {{ $verse['text_uthmani'] }}
                    </div>

                    {{-- English Translation --}}
                    <div class="text-slate-600 font-medium text-sm leading-relaxed max-w-4xl">
                        {{ $verse['translation'] }}
                    </div>

                    {{-- Relational Mapping Indicators --}}
                    @if(($verse['has_hadith'] ?? false) || ($verse['has_seerat'] ?? false) || ($verse['has_science'] ?? false) || ($verse['has_history'] ?? false) || ($verse['has_bible'] ?? false) || ($verse['has_torah'] ?? false))
                        <div class="flex flex-wrap gap-1.5 pt-2">
                            @if($verse['has_science'] ?? false)
                                <span class="text-[9px] font-black uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200/50 px-2.5 py-0.5 rounded-full select-none">🔬 Science</span>
                            @endif
                            @if($verse['has_seerat'] ?? false)
                                <span class="text-[9px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200/50 px-2.5 py-0.5 rounded-full select-none">🕌 Seerah</span>
                            @endif
                            @if($verse['has_hadith'] ?? false)
                                <span class="text-[9px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/50 px-2.5 py-0.5 rounded-full select-none">📚 Hadith</span>
                            @endif
                            @if($verse['has_history'] ?? false)
                                <span class="text-[9px] font-black uppercase tracking-wider bg-orange-50 text-orange-700 border border-orange-200/50 px-2.5 py-0.5 rounded-full select-none">🏛️ History</span>
                            @endif
                            @if($verse['has_bible'] ?? false)
                                <span class="text-[9px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200/50 px-2.5 py-0.5 rounded-full select-none">✝️ Bible</span>
                            @endif
                            @if($verse['has_torah'] ?? false)
                                <span class="text-[9px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200/50 px-2.5 py-0.5 rounded-full select-none">📜 Torah</span>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Hover Action overlay --}}
                <div class="shrink-0 flex md:self-center">
                    <a href="{{ route('lens.verse', [$surahInfo['id'], $verse['verse_number']]) }}" 
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest px-5 py-3 rounded-2xl shadow-lg shadow-emerald-600/10 hover:shadow-emerald-600/20 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                        <span>Analyze</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
