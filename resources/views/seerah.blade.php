@extends('layouts.app')
@section('title', 'Seerah Insight - Nur Al-Quran')

@section('content')
    <div class="max-w-3xl mx-auto space-y-8 pb-10 animate-fadeIn"
        x-data="insightQuiz({{ json_encode($insight['question']) }})">
        {{-- Insight Content --}}
        <div class="bg-white rounded-[2rem] p-10 shadow-xl border border-slate-100 space-y-6">
            <div class="flex items-center space-x-2 text-blue-600 font-black uppercase text-xs tracking-widest">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" />
                </svg>
                <span>Reflection Mode</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900 leading-tight">{{ $insight['title'] }}</h1>
            <div class="text-slate-700 text-lg leading-relaxed italic border-l-4 border-blue-200 pl-6 py-2">
                "{{ $insight['content'] }}"
            </div>
        </div>

        {{-- Quiz Question --}}
        <div class="bg-white rounded-2xl shadow-2xl p-6 md:p-8 border border-slate-100">
            <div class="mb-6">
                <div class="flex items-center space-x-2 mb-2">
                    <span class="h-1 w-8 bg-emerald-500 rounded-full"></span>
                    <span class="text-emerald-700 font-bold text-xs tracking-widest uppercase">Knowledge Check</span>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mt-2 leading-tight" x-text="question.text"></h2>
            </div>
            <div class="space-y-4">
                <template x-for="(option, idx) in question.options" :key="idx">
                    <button :disabled="answered" @click="selectAnswer(idx)" :class="getOptionClass(idx)"
                        class="w-full text-left p-5 rounded-xl border-2 transition-all duration-300 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <span :class="getLetterClass(idx)"
                                class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm"
                                x-text="String.fromCharCode(65 + idx)"></span>
                            <span class="font-semibold text-lg" x-text="option"></span>
                        </div>
                    </button>
                </template>
            </div>
            <div x-show="answered"
                class="mt-8 p-6 bg-blue-50 rounded-2xl border-2 border-blue-100 shadow-inner animate-slideUp">
                <h4 class="text-blue-900 font-bold text-sm uppercase tracking-wider mb-1">Deep Insight</h4>
                <p class="text-blue-800 text-md leading-relaxed font-medium" x-text="question.explanation"></p>
            </div>
        </div>

        {{-- Return Button --}}
        <a x-show="answered" href="{{ route('home') }}"
            class="block w-full bg-slate-900 text-white py-5 rounded-2xl font-black text-lg shadow-lg hover:bg-slate-800 transition-all active:scale-[0.99] text-center">
            Return to Home
        </a>
    </div>
@endsection

@push('scripts')
    <script>
        function insightQuiz(question) {
            return {
                question,
                selectedAnswer: null,
                answered: false,

                async selectAnswer(idx) {
                    if (this.answered) return;
                    this.selectedAnswer = idx;
                    this.answered = true;
                    const correct = idx === this.question.correctAnswerIndex;
                    try {
                        await fetch('{{ route("insight.answer") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ correct, questionId: this.question.id }),
                        });
                    } catch (e) { }
                },

                getOptionClass(idx) {
                    if (!this.answered) return 'border-slate-200 bg-white hover:border-emerald-500 hover:bg-emerald-50 text-slate-700';
                    if (idx === this.question.correctAnswerIndex) return 'border-emerald-600 bg-emerald-100 text-emerald-900 ring-4 ring-emerald-50 scale-[1.02]';
                    if (idx === this.selectedAnswer) return 'border-rose-600 bg-rose-100 text-rose-900';
                    return 'border-slate-100 bg-slate-50 text-slate-400 opacity-60 cursor-not-allowed';
                },
                getLetterClass(idx) {
                    if (!this.answered) return 'bg-slate-100 text-slate-500';
                    if (idx === this.question.correctAnswerIndex) return 'bg-emerald-600 text-white';
                    if (idx === this.selectedAnswer) return 'bg-rose-600 text-white';
                    return 'bg-slate-200 text-slate-400';
                },
            };
        }
    </script>
@endpush