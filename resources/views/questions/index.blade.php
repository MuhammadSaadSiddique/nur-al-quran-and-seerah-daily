@extends('layouts.app')
@section('title', 'Question Bank - The Eternal Echo')
@section('meta_description', 'Browse your personal question bank. Review attempted and bookmarked Quran and Seerah quiz questions with explanations.')

@section('content')
<div class="max-w-7xl mx-auto space-y-10 pb-10 animate-fadeIn">

    {{-- Header --}}
    <div class="text-center space-y-3">
        <h1 class="text-3xl md:text-4xl font-black text-slate-900">Question Bank</h1>
        <p class="text-slate-500 font-medium">Your personal collection of attempted & bookmarked questions</p>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center justify-center gap-3">
        <a href="{{ route('questions.index', ['tab' => 'attempted']) }}"
           class="relative px-6 py-3 rounded-2xl font-black text-sm transition-all duration-300
                  {{ $tab === 'attempted'
                      ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200'
                      : 'bg-white text-slate-500 border-2 border-slate-100 hover:border-emerald-200 hover:text-emerald-600' }}">
            <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            My Attempted
            @if($attemptedCount > 0)
                <span class="ml-1.5 inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full text-[10px] font-black
                             {{ $tab === 'attempted' ? 'bg-white/25 text-white' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $attemptedCount }}
                </span>
            @endif
        </a>
        <a href="{{ route('questions.index', ['tab' => 'bookmarks']) }}"
           class="relative px-6 py-3 rounded-2xl font-black text-sm transition-all duration-300
                  {{ $tab === 'bookmarks'
                      ? 'bg-amber-500 text-white shadow-lg shadow-amber-200'
                      : 'bg-white text-slate-500 border-2 border-slate-100 hover:border-amber-200 hover:text-amber-600' }}">
            <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
            Bookmarked
            @if($bookmarkCount > 0)
                <span class="ml-1.5 inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full text-[10px] font-black
                             {{ $tab === 'bookmarks' ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-700' }}">
                    {{ $bookmarkCount }}
                </span>
            @endif
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-lg border border-slate-100 text-center">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                {{ $tab === 'bookmarks' ? 'Bookmarked' : 'Attempted' }}
            </p>
            <p class="text-3xl font-black text-emerald-600">{{ $totalQuestions }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-lg border border-slate-100 text-center">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Para Questions</p>
            <p class="text-3xl font-black text-blue-600">{{ $types['PARA'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-lg border border-slate-100 text-center">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Seerah Questions</p>
            <p class="text-3xl font-black text-violet-600">{{ ($types['SEERAH'] ?? 0) + ($types['SEERAH_INSIGHT'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-lg border border-slate-100 text-center">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Avg. Accuracy</p>
            <p class="text-3xl font-black text-amber-600">{{ $avgAccuracy }}%</p>
        </div>
    </div>

    {{-- Filters --}}
    <form action="{{ route('questions.index') }}" method="GET" class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100 space-y-4">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search questions..."
                   class="p-3 rounded-xl border-2 border-slate-100 font-bold text-slate-700 focus:border-emerald-500 outline-none">
            <select name="type" class="p-3 rounded-xl border-2 border-slate-100 font-bold text-slate-700 focus:border-emerald-500 outline-none">
                <option value="">All Types</option>
                <option value="PARA" {{ request('type') === 'PARA' ? 'selected' : '' }}>Para Quiz</option>
                <option value="SEERAH" {{ request('type') === 'SEERAH' ? 'selected' : '' }}>Seerah Quiz</option>
                <option value="SEERAH_INSIGHT" {{ request('type') === 'SEERAH_INSIGHT' ? 'selected' : '' }}>Seerah Insight</option>
                <option value="QURAN_HISTORY" {{ request('type') === 'QURAN_HISTORY' ? 'selected' : '' }}>Quranic History</option>
            </select>
            <select name="difficulty" class="p-3 rounded-xl border-2 border-slate-100 font-bold text-slate-700 focus:border-emerald-500 outline-none">
                <option value="">All Difficulty</option>
                <option value="Easy" {{ request('difficulty') === 'Easy' ? 'selected' : '' }}>Easy</option>
                <option value="Medium" {{ request('difficulty') === 'Medium' ? 'selected' : '' }}>Medium</option>
                <option value="Hard" {{ request('difficulty') === 'Hard' ? 'selected' : '' }}>Hard</option>
            </select>
            <select name="theme" class="p-3 rounded-xl border-2 border-slate-100 font-bold text-slate-700 focus:border-emerald-500 outline-none">
                <option value="">All Themes</option>
                @foreach($themes as $theme)
                    <option value="{{ $theme }}" {{ request('theme') === $theme ? 'selected' : '' }}>{{ $theme }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-emerald-600 text-white p-3 rounded-xl font-black hover:bg-emerald-700 transition-all">
                Filter
            </button>
        </div>
    </form>

    {{-- Questions Grid --}}
    @if($questions->isEmpty())
        <div class="bg-white rounded-[2rem] p-12 shadow-xl border border-slate-100 text-center space-y-4">
            @if($tab === 'bookmarks')
                <svg class="w-16 h-16 mx-auto text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" stroke-width="2" />
                </svg>
                <p class="text-slate-500 font-bold">No bookmarked questions yet.</p>
                <p class="text-slate-400 text-sm">Bookmark questions during quizzes to review them here later!</p>
            @else
                <svg class="w-16 h-16 mx-auto text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" />
                </svg>
                <p class="text-slate-500 font-bold">You haven't attempted any questions yet.</p>
                <p class="text-slate-400 text-sm">Start a quiz to build your personal question bank!</p>
            @endif
            <a href="{{ route('home') }}" class="inline-block bg-emerald-600 text-white px-6 py-3 rounded-xl font-black hover:bg-emerald-700 transition-all">Start a Quiz</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($questions as $q)
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100 hover:shadow-xl transition-all group" x-data="{ showAnswer: false }">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="flex-1 space-y-3">
                            {{-- Badges --}}
                            <div class="flex flex-wrap gap-2">
                                <span class="text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-lg border
                                    {{ $q->type === 'PARA' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                       ($q->type === 'SEERAH' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                       ($q->type === 'QURAN_HISTORY' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-violet-50 text-violet-700 border-violet-200')) }}">
                                    {{ str_replace('_', ' ', $q->type) }}
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-lg border
                                    {{ $q->difficulty === 'Easy' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                       ($q->difficulty === 'Medium' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200') }}">
                                    {{ $q->difficulty }}
                                </span>
                                @if($q->theme)
                                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-lg bg-slate-50 text-slate-500 border border-slate-200">{{ $q->theme }}</span>
                                @endif
                                <span class="text-[9px] font-bold px-2 py-1 rounded-lg bg-slate-50 text-slate-400 border border-slate-100">{{ $q->source_info }}</span>

                                {{-- Bookmark indicator --}}
                                @if($tab === 'bookmarks')
                                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-lg bg-amber-50 text-amber-600 border border-amber-200">
                                        <svg class="w-3 h-3 inline -mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                        Bookmarked
                                    </span>
                                @endif
                            </div>

                            {{-- Question Text --}}
                            <p class="text-lg font-bold text-slate-900 leading-relaxed">{{ $q->text }}</p>

                            {{-- Options --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach($q->options as $idx => $opt)
                                    <div class="p-3 rounded-xl border-2 text-sm font-semibold flex items-center space-x-3
                                        {{ $idx === $q->correct_answer_index ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-slate-100 bg-slate-50 text-slate-600' }}"
                                        :class="showAnswer ? '' : '!border-slate-100 !bg-slate-50 !text-slate-600'">
                                        <span class="w-6 h-6 rounded-md flex items-center justify-center text-xs font-black flex-shrink-0"
                                              :class="showAnswer && {{ $idx === $q->correct_answer_index ? 'true' : 'false' }} ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500'">
                                            {{ chr(65 + $idx) }}
                                        </span>
                                        <span>{{ $opt }}</span>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Explanation (shown on reveal) --}}
                            <div x-show="showAnswer" x-collapse class="p-4 bg-blue-50 rounded-xl border border-blue-100 mt-2">
                                <p class="text-blue-800 text-sm font-medium"><strong>Explanation:</strong> {{ $q->explanation }}</p>
                                @if($q->reference)
                                    <p class="text-blue-700 text-xs mt-2 font-semibold flex items-center gap-1">
                                        <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        <span>Ref: {{ $q->reference }}</span>
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Right side: Stats + Toggle --}}
                        <div class="flex md:flex-col items-center md:items-end gap-3 flex-shrink-0">
                            @if($q->times_answered > 0)
                                <div class="text-center bg-slate-50 px-3 py-2 rounded-xl border border-slate-100">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Accuracy</p>
                                    <p class="text-lg font-black {{ $q->accuracy_percent >= 70 ? 'text-emerald-600' : ($q->accuracy_percent >= 40 ? 'text-amber-600' : 'text-rose-600') }}">{{ $q->accuracy_percent }}%</p>
                                    <p class="text-[9px] text-slate-400">{{ $q->times_answered }} tries</p>
                                </div>
                            @endif
                            <button @click="showAnswer = !showAnswer"
                                    class="px-4 py-2 rounded-xl font-bold text-sm transition-all"
                                    :class="showAnswer ? 'bg-slate-200 text-slate-600' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'"
                                    x-text="showAnswer ? 'Hide Answer' : 'Show Answer'">
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $questions->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
