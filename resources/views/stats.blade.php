@extends('layouts.app')
@section('title', 'Your Progress - The Eternal Echo')
@section('meta_description', 'Track your Quran and Seerah learning progress. View quiz statistics, accuracy rates, and spiritual growth milestones.')

@section('content')
    <div class="max-w-5xl mx-auto space-y-10 pb-10 animate-fadeIn">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
            <div class="bg-white rounded-[2rem] p-6 shadow-xl border border-slate-100 text-center">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Accuracy</p>
                <p class="text-4xl font-black text-emerald-600">{{ $user->accuracy }}%</p>
            </div>
            <div class="bg-white rounded-[2rem] p-6 shadow-xl border border-slate-100 text-center">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Questions</p>
                <p class="text-4xl font-black text-slate-900">{{ $user->total_questions }}</p>
            </div>
            <div class="bg-white rounded-[2rem] p-6 shadow-xl border border-slate-100 text-center">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Paras Done</p>
                <p class="text-4xl font-black text-blue-600">{{ count($user->completed_paras ?? []) }}/30</p>
            </div>
            <div class="bg-white rounded-[2rem] p-6 shadow-xl border border-slate-100 text-center">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Insights</p>
                <p class="text-4xl font-black text-amber-600">
                    {{ $user->seerah_read_count + $user->quran_history_read_count }}
                </p>
            </div>
        </div>

        {{-- Score Breakdown --}}
        <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-6">
            <h2 class="text-xl font-black text-slate-900 uppercase tracking-wider">Score Breakdown</h2>
            <div class="flex items-center justify-between">
                <span class="font-bold text-slate-600">Total Score</span>
                <span class="font-black text-emerald-600">{{ $user->total_score }} / {{ $user->max_possible_score }}</span>
            </div>
            @if($user->total_questions > 0)
                <div class="w-full h-4 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $user->accuracy }}%"></div>
                </div>
            @endif
        </div>

        {{-- Difficulty Stats --}}
        @if($user->difficulty_stats)
            <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-6">
                <h2 class="text-xl font-black text-slate-900 uppercase tracking-wider">Difficulty Analysis</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach(['Easy' => 'emerald', 'Medium' => 'amber', 'Hard' => 'rose'] as $level => $color)
                        @php
                            $total = $user->difficulty_stats['total'][$level] ?? 0;
                            $correct = $user->difficulty_stats['correct'][$level] ?? 0;
                            $pct = $total > 0 ? round(($correct / $total) * 100) : 0;
                        @endphp
                        <div class="bg-{{ $color }}-50 rounded-2xl p-6 border border-{{ $color }}-100 text-center">
                            <p class="text-[10px] font-black uppercase tracking-widest text-{{ $color }}-600 mb-2">{{ $level }}</p>
                            <p class="text-3xl font-black text-{{ $color }}-700">{{ $pct }}%</p>
                            <p class="text-xs text-{{ $color }}-500 mt-1">{{ $correct }}/{{ $total }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Quick Links --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('quiz.history') }}"
                class="bg-slate-900 text-white p-6 rounded-2xl font-black flex items-center justify-between hover:bg-slate-800 transition-all">
                Quiz History
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M9 5l7 7-7 7" stroke-width="3" />
                </svg>
            </a>
            <a href="{{ route('bookmarks') }}"
                class="bg-amber-600 text-white p-6 rounded-2xl font-black flex items-center justify-between hover:bg-amber-700 transition-all">
                Bookmarks
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M9 5l7 7-7 7" stroke-width="3" />
                </svg>
            </a>
        </div>
    </div>
@endsection