@extends('layouts.app')

@section('title', 'Explore Quranic Lens — Quranic Research & Tagging Platform')
@section('meta_description', 'Connect any verse of the Quran to classical Tafsir, Hadith, chronological Seerah, modern science, archaeology, and comparative scriptures.')

@section('content')
    <div class="space-y-12 pb-10 animate-fadeIn" x-data="{ searchSurah: '' }">

        {{-- Premium Hero Section --}}
        <div
            class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 rounded-[2.5rem] p-8 md:p-14 text-white overflow-hidden shadow-xl border border-slate-700/50">
            {{-- Subtle background decoration --}}
            <div
                class="absolute inset-0 opacity-10 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-emerald-300 via-slate-900 to-slate-950 pointer-events-none">
            </div>
            <div
                class="absolute -right-20 -bottom-20 w-80 h-80 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none">
            </div>

            <div class="relative z-10 max-w-4xl space-y-6">
                <div
                    class="inline-flex items-center space-x-2 bg-emerald-500/20 backdrop-blur-md rounded-full px-4.5 py-1.5 border border-emerald-400/20 shadow-sm">
                    <span class="flex w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[10px] font-black tracking-widest uppercase text-emerald-100">Quranic Lens &
                        Linguistic Research</span>
                </div>

                <h1 class="text-3xl md:text-5xl font-black tracking-tight leading-tight md:leading-none">
                    Linguistic and Scientific <br class="hidden md:block" />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-teal-100">Harmonies of
                        the Quran</span>
                </h1>

                <p class="text-slate-300 font-medium text-sm md:text-base leading-relaxed max-w-2xl">
                    Welcome to the research cockpit. Explore, verify, and document connections between Quranic verses and
                    classical Tafsir, Hadith, chronological Seerah events, modern scientific facts, historical contexts, and
                    comparative scriptural parallels.
                </p>
            </div>
        </div>

        {{-- Connection Statistics Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div
                class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-300">
                <span class="text-2xl mb-2">📖</span>
                <div>
                    <span class="text-2xl font-black text-slate-800 block">114</span>
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Surahs Cataloged</span>
                </div>
            </div>
            <div
                class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-300">
                <span class="text-2xl mb-2">🔬</span>
                <div>
                    <span class="text-2xl font-black text-purple-700 block">3,954</span>
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 font-semibold">Science
                        Links</span>
                </div>
            </div>
            <div
                class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-300">
                <span class="text-2xl mb-2">🕌</span>
                <div>
                    <span class="text-2xl font-black text-blue-700 block">477</span>
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Seerah Links</span>
                </div>
            </div>
            <div
                class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-300">
                <span class="text-2xl mb-2">🏛️</span>
                <div>
                    <span class="text-2xl font-black text-orange-700 block">1,916</span>
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 font-semibold">History
                        Links</span>
                </div>
            </div>
            <div
                class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-300 col-span-2 md:col-span-1">
                <span class="text-2xl mb-2">✝️</span>
                <div>
                    <span class="text-2xl font-black text-rose-700 block">18</span>
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Scriptural Parallels</span>
                </div>
            </div>
        </div>

        {{-- Global Search Cockpit --}}
        <div class="max-w-2xl mx-auto space-y-4">
            <div class="text-center">
                <span class="text-xs font-black uppercase tracking-widest text-slate-400">Linguistic & Content Search</span>
            </div>
            <form method="GET" action="{{ route('welcome') }}"
                class="bg-white rounded-3xl shadow-sm border border-slate-100 p-2.5 flex flex-col sm:flex-row gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="relative flex-grow">
                    <input type="text" name="search" value="{{ $search ?? '' }}"
                        placeholder="Search word, meaning, or coordinate (e.g. charity, sky, 2:255)..."
                        class="w-full py-3.5 pl-11 pr-4 rounded-2xl bg-slate-50 font-medium text-slate-700 placeholder-slate-400 outline-none focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all border border-slate-100" />
                    <svg class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest px-8 py-4 rounded-2xl transition-all shadow-lg shadow-emerald-600/10 hover:shadow-emerald-600/20 hover:-translate-y-0.5">
                        Search
                    </button>
                    @if(!empty($search))
                        <a href="{{ route('welcome', ['tab' => $tab]) }}"
                            class="w-full sm:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs uppercase tracking-widest px-5 py-4 rounded-2xl transition-all flex items-center justify-center">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Lens Filter Tabs (Includes Biology and Mathematics) --}}
        <div
            class="bg-white p-2 rounded-2xl border border-slate-100 shadow-sm flex flex-wrap gap-1 items-center justify-center max-w-5xl mx-auto">
            <a href="{{ route('welcome', ['tab' => 'surahs']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all {{ $tab === 'surahs' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                📖 Browse Surahs
            </a>
            <a href="{{ route('welcome', ['tab' => 'science']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all {{ $tab === 'science' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                🔬 Science Links
            </a>
            <a href="{{ route('welcome', ['tab' => 'seerat']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all {{ $tab === 'seerat' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                🕌 Seerah Links
            </a>
            <a href="{{ route('welcome', ['tab' => 'hadith']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all {{ $tab === 'hadith' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                📚 Hadith Links
            </a>
            <a href="{{ route('welcome', ['tab' => 'history']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all {{ $tab === 'history' ? 'bg-orange-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                🏛️ History Links
            </a>
            <a href="{{ route('welcome', ['tab' => 'bible']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all {{ $tab === 'bible' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                ✝️ Bible Links
            </a>
            <a href="{{ route('welcome', ['tab' => 'torah']) }}"
                class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all {{ $tab === 'torah' ? 'bg-amber-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                📜 Torah Links
            </a>
        </div>

        @if($tab === 'science' && !empty($scienceCategories))
            <div class="flex flex-wrap gap-1.5 items-center justify-center max-w-4xl mx-auto mt-4">
                <a href="{{ route('welcome', ['tab' => 'science']) }}"
                    class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wider transition-all {{ !request()->filled('science_field') ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-700 border border-slate-100' }}">
                    🌐 All Fields
                </a>
                @foreach($scienceCategories as $key => $category)
                    <a href="{{ route('welcome', ['tab' => 'science', 'science_field' => $key]) }}"
                        class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5 {{ request('science_field') === $key ? 'bg-purple-600 text-white shadow-sm' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-700 border border-slate-100' }}">
                        <span>{{ $category['emoji'] }}</span>
                        <span>{{ $category['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if(!empty($search))
            {{-- Search Results view --}}
            <div class="max-w-4xl mx-auto space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="font-extrabold text-slate-800 text-lg">
                        Search Results for <span class="text-emerald-600">"{{ $search }}"</span>
                    </h3>
                    <span class="text-xs text-slate-400 font-bold">
                        Found {{ $searchResults ? $searchResults->total() : 0 }} match(es)
                    </span>
                </div>

                @if(isset($searchResults) && $searchResults->count() > 0)
                    @foreach($searchResults as $item)
                        <div
                            class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm hover:shadow-md transition-all space-y-6 relative overflow-hidden group">
                            {{-- Top Metadata bar --}}
                            <div class="flex items-center justify-between border-b border-slate-50 pb-4">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-400">
                                    Surah {{ $item->surah_name }} ({{ $item->surah_number }}:{{ $item->verse_number }})
                                </span>
                                <span
                                    class="text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-800 border border-emerald-100 px-3 py-1 rounded-full">
                                    Para {{ $item->juz }}
                                </span>
                            </div>

                            {{-- Arabic and Translation --}}
                            <div class="space-y-4">
                                <div class="text-right font-arabic text-2xl md:text-3xl font-bold text-slate-800 leading-loose"
                                    dir="rtl">
                                    {{ $item->text_arabic }}
                                </div>
                                <div class="text-slate-600 font-medium text-sm leading-relaxed max-w-3xl italic">
                                    "{{ $item->translation }}"
                                </div>
                            </div>

                            {{-- Connections indicators --}}
                            @if($item->has_science || $item->has_seerat || $item->has_hadith || $item->has_history || $item->has_bible || $item->has_torah)
                                <div class="flex flex-wrap gap-1.5 pt-2 border-t border-slate-50">
                                    @if($item->has_science)
                                        <span
                                            class="text-[9px] font-black uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200/50 px-2.5 py-0.5 rounded-full select-none">🔬
                                            Science</span>
                                    @endif
                                    @if($item->has_seerat)
                                        <span
                                            class="text-[9px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200/50 px-2.5 py-0.5 rounded-full select-none">🕌
                                            Seerah</span>
                                    @endif
                                    @if($item->has_hadith)
                                        <span
                                            class="text-[9px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/50 px-2.5 py-0.5 rounded-full select-none">📚
                                            Hadith</span>
                                    @endif
                                    @if($item->has_history)
                                        <span
                                            class="text-[9px] font-black uppercase tracking-wider bg-orange-50 text-orange-700 border border-orange-200/50 px-2.5 py-0.5 rounded-full select-none">🏛️
                                            History</span>
                                    @endif
                                    @if($item->has_bible)
                                        <span
                                            class="text-[9px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200/50 px-2.5 py-0.5 rounded-full select-none">✝️
                                            Bible</span>
                                    @endif
                                    @if($item->has_torah)
                                        <span
                                            class="text-[9px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200/50 px-2.5 py-0.5 rounded-full select-none">📜
                                            Torah</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Action Buttons --}}
                            <div class="flex justify-end pt-2 border-t border-slate-50">
                                <a href="{{ route('lens.verse', [$item->surah_number, $item->verse_number]) }}"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest px-5 py-3 rounded-xl shadow-md transition-all flex items-center gap-2 hover:-translate-y-0.5">
                                    <span>Analyze & Tag</span>
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach

                    {{-- Pagination Links --}}
                    <div class="pt-6">
                        {{ $searchResults->links() }}
                    </div>
                @else
                    <div class="text-center py-16 bg-white rounded-3xl border border-slate-100 space-y-3">
                        <div class="text-4xl">🔍</div>
                        <h4 class="font-bold text-slate-700 text-sm">No verses found matching "{{ $search }}"</h4>
                        <p class="text-xs text-slate-400 max-w-xs mx-auto leading-relaxed">Try searching for other words like
                            "charity", "night", "sky", or enter a verse key like "2:255".</p>
                    </div>
                @endif
            </div>
        @elseif($tab === 'surahs')
            {{-- Search bar for Surah list --}}
            <div class="max-w-md mx-auto relative bg-white rounded-2xl shadow-sm border border-slate-100 p-2">
                <input type="text" x-model="searchSurah" placeholder="Filter Surah below by name (e.g. Al-Fatihah)..."
                    class="w-full py-3 pl-10 pr-4 rounded-xl border border-slate-100 bg-slate-50 font-medium text-slate-700 placeholder-slate-400 outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-all" />
                <svg class="w-5 h-5 text-slate-400 absolute left-5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            {{-- Surahs Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($chapters as $chapter)
                    <div x-show="searchSurah === '' || '{{ strtolower($chapter['name_simple']) }}'.includes(searchSurah.toLowerCase()) || '{{ strtolower($chapter['translated_name']['name']) }}'.includes(searchSurah.toLowerCase()) || '{{ $chapter['id'] }}' == searchSurah"
                        class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-md hover:border-emerald-500/30 transition-all duration-300 group flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            {{-- Chapter Number Badge --}}
                            <div
                                class="w-12 h-12 bg-slate-50 group-hover:bg-emerald-50 rounded-2xl flex items-center justify-center font-black text-slate-500 group-hover:text-emerald-700 transition-colors text-sm border border-slate-100">
                                {{ $chapter['id'] }}
                            </div>
                            <div>
                                <a href="{{ route('lens.surah', $chapter['id']) }}"
                                    class="text-lg font-bold text-slate-900 group-hover:text-emerald-700 transition-colors block">
                                    {{ $chapter['name_simple'] }}
                                </a>
                                <span class="text-xs text-slate-400 font-semibold block capitalize">
                                    {{ $chapter['translated_name']['name'] }} • {{ $chapter['verses_count'] }} Verses
                                </span>
                            </div>
                        </div>

                        <div class="text-right space-y-1.5">
                            <span
                                class="font-arabic text-xl font-bold text-slate-700 block group-hover:text-emerald-800 transition-colors">
                                {{ $chapter['name_arabic'] }}
                            </span>
                            <span
                                class="text-[9px] font-black tracking-widest uppercase rounded-full px-2.5 py-1 {{ $chapter['revelation_place'] === 'makkah' ? 'bg-amber-50 text-amber-800 border border-amber-100' : 'bg-blue-50 text-blue-800 border border-blue-100' }}">
                                {{ $chapter['revelation_place'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Connection-filtered Verses List --}}
            <div class="max-w-4xl mx-auto space-y-10">
                @if(isset($paginatedData) && $paginatedData->count() > 0)
                    @php
                        // Group the paginated items for categorized display
                        if ($tab === 'science') {
                            $groupedItems = $paginatedData->groupBy(function ($item) use ($scienceCategories) {
                                if (isset($scienceCategories)) {
                                    foreach ($scienceCategories as $key => $cat) {
                                        if (in_array(strtolower($item->link_extra), $cat['fields'])) {
                                            return $cat['emoji'] . ' ' . $cat['label'];
                                        }
                                    }
                                }
                                return '🔬 General Science';
                            });
                        } elseif ($tab === 'seerat') {
                            $groupedItems = $paginatedData->groupBy(function ($item) {
                                return '🕌 ' . ($item->link_extra ?: 'General Seerah');
                            });
                        } elseif ($tab === 'hadith') {
                            $groupedItems = $paginatedData->groupBy(function ($item) {
                                return '📚 ' . ($item->link_extra ?: 'Hadith Collections');
                            });
                        } elseif ($tab === 'history') {
                            $groupedItems = $paginatedData->groupBy(function ($item) {
                                return '🏛️ ' . ($item->link_extra ?: 'Historical Context');
                            });
                        } elseif ($tab === 'bible') {
                            $groupedItems = $paginatedData->groupBy(function ($item) {
                                return '✝️ ' . ($item->link_extra ?: 'Biblical Parallel');
                            });
                        } elseif ($tab === 'torah') {
                            $groupedItems = $paginatedData->groupBy(function ($item) {
                                return '📜 Torah Parallel';
                            });
                        } else {
                            $groupedItems = ['Connections' => $paginatedData];
                        }
                    @endphp

                    @foreach($groupedItems as $groupName => $items)
                        <div class="space-y-6">
                            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                                <h3 class="text-base font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                    {{ $groupName }}
                                </h3>
                                <span
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50 border border-slate-100 px-2.5 py-0.5 rounded-full">
                                    {{ count($items) }} {{ count($items) === 1 ? 'Connection' : 'Connections' }}
                                </span>
                            </div>

                            <div class="space-y-6">
                                @foreach($items as $item)
                                    @php
                                        // Explicit static assignment of classes for proper Tailwind CSS extraction
                                        $bgClass = 'bg-emerald-600 hover:bg-emerald-700';
                                        $bgLightClass = 'bg-emerald-50/30';
                                        $borderLightClass = 'border-emerald-100/50';
                                        $textDarkClass = 'text-emerald-800';
                                        $borderClass = 'border-emerald-100';
                                        $badgeClass = 'bg-emerald-100/50 text-emerald-900 border-emerald-200/50';

                                        if ($tab === 'science') {
                                            $bgClass = 'bg-purple-600 hover:bg-purple-700';
                                            $bgLightClass = 'bg-purple-50/30';
                                            $borderLightClass = 'border-purple-100/50';
                                            $textDarkClass = 'text-purple-800';
                                            $borderClass = 'border-purple-100';
                                            $badgeClass = 'bg-purple-100/50 text-purple-900 border-purple-200/50';
                                        } elseif ($tab === 'seerat') {
                                            $bgClass = 'bg-blue-600 hover:bg-blue-700';
                                            $bgLightClass = 'bg-blue-50/30';
                                            $borderLightClass = 'border-blue-100/50';
                                            $textDarkClass = 'text-blue-800';
                                            $borderClass = 'border-blue-100';
                                            $badgeClass = 'bg-blue-100/50 text-blue-900 border-blue-200/50';
                                        } elseif ($tab === 'history') {
                                            $bgClass = 'bg-orange-600 hover:bg-orange-700';
                                            $bgLightClass = 'bg-orange-50/30';
                                            $borderLightClass = 'border-orange-100/50';
                                            $textDarkClass = 'text-orange-800';
                                            $borderClass = 'border-orange-100';
                                            $badgeClass = 'bg-orange-100/50 text-orange-900 border-orange-200/50';
                                        } elseif ($tab === 'bible') {
                                            $bgClass = 'bg-rose-600 hover:bg-rose-700';
                                            $bgLightClass = 'bg-rose-50/30';
                                            $borderLightClass = 'border-rose-100/50';
                                            $textDarkClass = 'text-rose-800';
                                            $borderClass = 'border-rose-100';
                                            $badgeClass = 'bg-rose-100/50 text-rose-900 border-rose-200/50';
                                        } elseif ($tab === 'torah') {
                                            $bgClass = 'bg-amber-600 hover:bg-amber-700';
                                            $bgLightClass = 'bg-amber-50/30';
                                            $borderLightClass = 'border-amber-100/50';
                                            $textDarkClass = 'text-amber-800';
                                            $borderClass = 'border-amber-100';
                                            $badgeClass = 'bg-amber-100/50 text-amber-900 border-amber-200/50';
                                        }
                                    @endphp

                                    <div
                                        class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm hover:shadow-md transition-all space-y-6 relative overflow-hidden group">
                                        {{-- Top Metadata bar --}}
                                        <div class="flex items-center justify-between border-b border-slate-50 pb-4">
                                            <span class="text-xs font-black uppercase tracking-widest text-slate-400">
                                                Surah {{ $item->surah_name }} ({{ $item->surah_number }}:{{ $item->verse_number }})
                                            </span>
                                            <span
                                                class="text-[10px] font-black uppercase tracking-widest {{ $textDarkClass }} {{ $badgeClass }} px-3 py-1 rounded-full border">
                                                Para {{ $item->juz }}
                                            </span>
                                        </div>

                                        {{-- Arabic and Translation --}}
                                        <div class="space-y-4">
                                            <div class="text-right font-arabic text-2xl md:text-3xl font-bold text-slate-800 leading-loose"
                                                dir="rtl">
                                                {{ $item->text_arabic }}
                                            </div>
                                            <div class="text-slate-500 font-medium text-sm leading-relaxed max-w-3xl italic">
                                                "{{ $item->translation }}"
                                            </div>
                                        </div>

                                        {{-- Mapped Link Card --}}
                                        <div class="p-6 rounded-2xl {{ $bgLightClass }} border {{ $borderLightClass }} space-y-3">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <h4 class="font-extrabold text-slate-800 text-sm">
                                                    {{ $item->link_title }}
                                                </h4>
                                                @if($item->link_extra)
                                                    @php
                                                        $fLower = strtolower($item->link_extra);
                                                        $displayLabel = $item->link_extra;
                                                        $emoji = '🔬';

                                                        if ($tab === 'science' && isset($scienceCategories)) {
                                                            foreach ($scienceCategories as $cat) {
                                                                if (in_array($fLower, $cat['fields'])) {
                                                                    $displayLabel = $cat['label'];
                                                                    $emoji = $cat['emoji'];
                                                                    break;
                                                                }
                                                            }
                                                        } else {
                                                            if ($fLower === 'astronomy' || $fLower === 'cosmology')
                                                                $emoji = '🪐';
                                                            elseif ($fLower === 'geology')
                                                                $emoji = '🪨';
                                                            elseif ($fLower === 'neuroscience' || $fLower === 'psychology')
                                                                $emoji = '🧠';
                                                            elseif ($fLower === 'biology')
                                                                $emoji = '🧬';
                                                            elseif ($fLower === 'embryology')
                                                                $emoji = '🍼';
                                                            elseif ($fLower === 'oceanography')
                                                                $emoji = '🌊';
                                                            elseif ($fLower === 'hydrology')
                                                                $emoji = '💧';
                                                            elseif ($fLower === 'meteorology')
                                                                $emoji = '🌀';
                                                            elseif ($fLower === 'physics')
                                                                $emoji = '⚡';
                                                        }
                                                    @endphp
                                                    <span
                                                        class="text-[9px] font-black uppercase tracking-widest {{ $badgeClass }} px-2.5 py-1 rounded-full border flex items-center gap-1 select-none">
                                                        <span>{{ $emoji }}</span>
                                                        <span>{{ $displayLabel }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-slate-600 text-xs leading-relaxed">
                                                {{ $item->link_content }}
                                            </p>
                                        </div>

                                        {{-- Action Buttons --}}
                                        <div class="flex justify-end pt-2 border-t border-slate-50">
                                            <a href="{{ route('lens.verse', [$item->surah_number, $item->verse_number]) }}"
                                                class="{{ $bgClass }} text-white font-black text-xs uppercase tracking-widest px-5 py-3 rounded-xl shadow-md transition-all flex items-center gap-2 hover:-translate-y-0.5">
                                                <span>Analyze & Tag</span>
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                        d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- Pagination Links --}}
                    <div class="pt-6">
                        {{ $paginatedData->links() }}
                    </div>
                @else
                    <div class="text-center py-16 bg-white rounded-3xl border border-slate-100 space-y-3">
                        <div class="text-4xl">🔍</div>
                        <h4 class="font-bold text-slate-700 text-sm">No connections mapped for this perspective yet</h4>
                        <p class="text-xs text-slate-400 max-w-xs mx-auto leading-relaxed">Researchers are actively reviewing new
                            links. Check back soon!</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection