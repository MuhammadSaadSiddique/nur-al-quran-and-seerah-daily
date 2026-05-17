@extends('layouts.app')
@section('title', 'Quiz History - The Eternal Echo')
@section('meta_description', 'View your complete quiz history. Review past Quran and Seerah quiz performance and track your improvement over time.')

@section('content')
    <div class="max-w-4xl mx-auto space-y-8 pb-10 animate-fadeIn">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-black text-slate-900">Quiz History</h1>
            <a href="{{ route('stats') }}" class="text-emerald-600 font-bold text-sm hover:underline">Back to Stats</a>
        </div>

        @if($quizzes->isEmpty())
            <div class="bg-white rounded-[2rem] p-12 shadow-xl border border-slate-100 text-center space-y-4">
                <svg class="w-16 h-16 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        stroke-width="2" />
                </svg>
                <p class="text-slate-500 font-bold">No quizzes completed yet.</p>
                <a href="{{ route('home') }}"
                    class="inline-block bg-emerald-600 text-white px-6 py-3 rounded-xl font-black hover:bg-emerald-700 transition-all">Start
                    a Quiz</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($quizzes as $quiz)
                    <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100 hover:shadow-xl transition-all"
                        x-data="{ expanded: false }">
                        <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
                            <div>
                                <h3 class="font-black text-slate-900">{{ $quiz->title }}</h3>
                                <div class="flex items-center space-x-4 mt-1">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest {{ $quiz->type === 'PARA' ? 'text-emerald-600' : 'text-blue-600' }}">{{ $quiz->type }}</span>
                                    <span class="text-xs text-slate-400 font-bold">{{ $quiz->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div class="text-right flex items-center space-x-4">
                                <div>
                                    <p class="text-lg font-black text-slate-900">{{ $quiz->points_gained }}/{{ $quiz->max_score }}
                                    </p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">
                                        {{ $quiz->difficulty }} • {{ $quiz->correct_answers_count }}/{{ $quiz->total_questions }}
                                        Correct
                                    </p>
                                </div>
                                <svg :class="expanded ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M19 9l-7 7-7-7" stroke-width="3" />
                                </svg>
                            </div>
                        </div>
                        <div x-show="expanded" x-collapse class="mt-6 space-y-3 border-t border-slate-100 pt-4">
                            @if(is_array($quiz->details) && isset($quiz->details['questions']))
                                @foreach($quiz->details['questions'] as $i => $q)
                                    @php
                                        $ua = $quiz->details['userAnswers'][$i] ?? -1;
                                        $correct = ($ua == ($q['correctAnswerIndex'] ?? -1));
                                    @endphp
                                    <div
                                        class="p-4 rounded-xl {{ $correct ? 'bg-emerald-50 border border-emerald-100' : 'bg-rose-50 border border-rose-100' }}">
                                        <p class="font-bold text-sm {{ $correct ? 'text-emerald-900' : 'text-rose-900' }}">
                                            <span class="mr-2">{{ $correct ? '✓' : '✗' }}</span>
                                            {{ $q['text'] ?? '' }}
                                        </p>
                                        @if(!$correct && isset($q['options'], $q['correctAnswerIndex']))
                                            <p class="text-xs text-emerald-700 mt-1 font-bold">Correct:
                                                {{ $q['options'][$q['correctAnswerIndex']] ?? '' }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection