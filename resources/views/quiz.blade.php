@extends('layouts.app')
@section('title', $title . ' Quiz - The Eternal Echo')
@section('meta_description', 'Take the ' . $title . ' quiz at ' . $difficulty . ' difficulty. Test your Islamic knowledge with AI-generated questions.')

@section('content')
    <div class="max-w-3xl mx-auto space-y-8 pb-10 animate-fadeIn"
        x-data="quizApp({{ json_encode($questions) }}, '{{ $type }}', '{{ $title }}', '{{ $difficulty }}', '{{ $theme }}', {{ $paraNumber ?? 'null' }})">

        {{-- Header --}}
        <div class="flex justify-between items-center px-2">
            <button
                @click="if(confirm('End quiz session? Progress will be lost.')) window.location.href='{{ route('home') }}'"
                class="text-rose-500 font-black uppercase text-[10px] tracking-widest hover:bg-rose-50 px-3 py-2 rounded-lg transition-colors">Exit
                Quiz</button>
            <div class="text-right">
                <p class="text-lg font-black text-slate-900 leading-none" x-text="quizTitle"></p>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1"
                    x-text="currentQuestion?.theme || 'General Study'"></p>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden shadow-inner">
            <div class="h-full bg-emerald-500 transition-all duration-500"
                :style="'width: ' + ((currentIndex + 1) / questions.length * 100) + '%'"></div>
        </div>

        {{-- Question Card --}}
        <div
            class="bg-white rounded-2xl shadow-2xl p-6 md:p-8 max-w-2xl w-full mx-auto border border-slate-100 animate-fadeIn relative">
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3">
                        <div class="flex items-center space-x-2">
                            <span class="h-1 w-8 bg-emerald-500 rounded-full"></span>
                            <span class="text-emerald-700 font-bold text-xs tracking-widest uppercase">Knowledge
                                Check</span>
                        </div>
                        {{-- Source Info Badge (Para number) --}}
                        <span x-show="currentQuestion?.source_info"
                            class="mt-1 sm:mt-0 bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest border border-purple-100"
                            x-text="currentQuestion?.source_info"></span>
                        <span x-show="currentQuestion?.theme"
                            class="mt-1 sm:mt-0 bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest border border-blue-100"
                            x-text="currentQuestion?.theme"></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span x-show="currentQuestion?.difficulty" :class="difficultyClass(currentQuestion?.difficulty)"
                            class="text-[10px] font-black uppercase px-2 py-0.5 rounded border"
                            x-text="currentQuestion?.difficulty"></span>
                            
                        <button @click="shareQuestion()" title="Share to Social Media"
                            class="bg-slate-100 text-slate-400 hover:text-blue-500 hover:bg-blue-50 p-2 rounded-full transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                        </button>

                        <button @click="toggleBookmark()"
                            :class="isBookmarked ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-400 hover:text-amber-500 hover:bg-amber-50'"
                            class="p-2 rounded-full transition-all duration-200">
                            <svg :class="isBookmarked ? 'fill-current' : 'fill-none'" class="w-5 h-5" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mt-2 leading-tight" x-text="currentQuestion?.text"></h2>
            </div>

            <div class="space-y-4">
                <template x-for="(option, idx) in currentQuestion?.options" :key="idx">
                    <button :disabled="answered" @click="selectAnswer(idx)" :class="getOptionClass(idx)"
                        class="w-full text-left p-5 rounded-xl border-2 transition-all duration-300 flex items-center justify-between group">
                        <div class="flex items-center space-x-4">
                            <span :class="getLetterClass(idx)"
                                class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm transition-colors"
                                x-text="String.fromCharCode(65 + idx)"></span>
                            <span class="font-semibold text-lg" x-text="option"></span>
                        </div>
                    </button>
                </template>
            </div>

            {{-- Explanation --}}
            <div x-show="answered"
                class="mt-8 p-6 bg-blue-50 rounded-2xl border-2 border-blue-100 shadow-inner animate-slideUp">
                <div class="flex items-start space-x-3">
                    <div class="mt-1 bg-blue-500 p-1 rounded-md flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-blue-900 font-bold text-sm uppercase tracking-wider mb-1">Deep Insight</h4>
                        <p class="text-blue-800 text-md leading-relaxed font-medium" x-text="currentQuestion?.explanation"></p>
                        <p x-show="currentQuestion?.reference" class="text-blue-700 text-sm mt-2 font-semibold flex items-center gap-1">
                            <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            <span x-text="'Ref: ' + currentQuestion?.reference"></span>
                        </p>
                    </div>
                </div>

                {{-- Feedback Button --}}
                <div class="mt-4 pt-3 border-t border-blue-200 flex justify-end">
                    <button @click="showFeedbackModal = true"
                        class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 px-3 py-1.5 rounded-lg transition-all inline-flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        <span>Report / Feedback</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Next / Finish Button --}}
        <button x-show="answered" @click="nextQuestion()"
            class="w-full bg-emerald-700 text-white py-5 rounded-2xl font-black text-xl shadow-xl hover:bg-emerald-800 transition-all active:scale-[0.98]"
            x-text="currentIndex === questions.length - 1 ? 'Finish Journey' : 'Next Question'"></button>

        {{-- Result & Testimonial Modal --}}
        <div x-show="showResultModal" x-cloak
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[200] flex items-center justify-center p-4 md:p-6 overflow-y-auto">
            <div class="bg-white rounded-[3rem] p-8 md:p-12 max-w-lg w-full shadow-2xl space-y-8 animate-slideUp my-auto">
                {{-- Celebration Header --}}
                <div class="text-center space-y-4">
                    <div class="w-24 h-24 bg-emerald-100 rounded-[2rem] flex items-center justify-center mx-auto text-emerald-600 shadow-inner group">
                        <svg class="w-12 h-12 transform group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-3xl font-black text-slate-900">Journey Complete</h3>
                        <p class="text-emerald-600 font-black uppercase tracking-[0.2em] text-xs">Masha'Allah</p>
                    </div>
                </div>

                {{-- Score Display --}}
                <div class="bg-slate-50 rounded-3xl p-6 border-2 border-slate-100 text-center space-y-1">
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Your Knowledge Score</p>
                    <div class="text-5xl font-black text-slate-900">
                        <span x-text="score"></span><span class="text-slate-300 mx-1">/</span><span x-text="questions.length"></span>
                    </div>
                </div>

                {{-- Testimonial Form --}}
                <div class="space-y-6" x-show="!testimonialSent">
                    <div class="space-y-2">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider text-center">Share Your Wisdom</h4>
                        <p class="text-slate-500 text-xs font-medium text-center italic">How did this quiz impact your journey?</p>
                    </div>

                    <div class="space-y-4">
                        <input type="text" x-model="testimonialName" placeholder="Your Name"
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-5 py-3 font-bold text-slate-800 focus:border-emerald-500 outline-none transition-all shadow-sm">
                        
                        <textarea x-model="testimonialFeedback" rows="3" placeholder="Share a few words of inspiration..."
                            class="w-full bg-white border-2 border-slate-100 rounded-2xl px-5 py-3 font-bold text-slate-800 focus:border-emerald-500 outline-none transition-all shadow-sm resize-none"></textarea>

                        <button @click="submitQuizTestimonial()" :disabled="!testimonialFeedback || testimonialSubmitting"
                            class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-black hover:bg-emerald-700 transition-all active:scale-[0.98] shadow-lg shadow-emerald-500/30 disabled:opacity-50">
                            <span x-show="!testimonialSubmitting">Share with the Ummah</span>
                            <span x-show="testimonialSubmitting">Spreading Wisdom...</span>
                        </button>
                    </div>
                </div>

                {{-- Success Message --}}
                <div x-show="testimonialSent" x-cloak class="text-center space-y-4 py-4 animate-fadeIn">
                    <div class="text-emerald-500 font-black text-lg">JazakAllah Khairan!</div>
                    <p class="text-slate-500 text-sm font-medium italic">Your words will inspire others on their spiritual path.</p>
                </div>

                {{-- Final Action --}}
                <div class="pt-4 border-t border-slate-100">
                    <button @click="goToDashboard()" 
                        class="w-full text-slate-400 font-black uppercase text-xs tracking-[0.2em] hover:text-emerald-600 transition-colors py-2">
                        Continue to Dashboard
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function quizApp(questions, type, title, difficulty, theme, paraNumber) {
            return {
                questions,
                type,
                quizTitle: title,
                difficulty,
                theme,
                paraNumber,
                currentIndex: 0,
                score: 0,
                selectedAnswer: null,
                answered: false,
                userAnswers: [],
                isBookmarked: false,

                // Feedback state
                showFeedbackModal: false,
                feedbackType: 'error',
                feedbackMessage: '',
                feedbackSubmitting: false,

                // Result & Testimonial state
                showResultModal: false,
                quizFinished: false,
                testimonialSent: false,
                testimonialName: '{{ Auth::user()->display_name ?: explode("@", Auth::user()->email)[0] }}',
                testimonialFeedback: '',
                testimonialSubmitting: false,

                get currentQuestion() { return this.questions[this.currentIndex]; },

                selectAnswer(idx) {
                    if (this.answered) return;
                    this.selectedAnswer = idx;
                    this.answered = true;
                    if (idx === this.currentQuestion.correctAnswerIndex) {
                        this.score++;
                    }
                    this.userAnswers.push(idx);
                },

                async shareQuestion() {
                    const q = this.currentQuestion;
                    const text = `Challenge: Can you answer this?\n\n"${q.text}"\n\nTake the quiz at The Eternal Echo!\n#Quran #Seerah #IslamicQuiz #TheEternalEcho`;
                    
                    if (navigator.share) {
                        try {
                            await navigator.share({
                                title: 'The Eternal Echo Quiz Challenge',
                                text: text,
                                url: window.location.origin
                            });
                        } catch (err) {
                            console.log('Share canceled or failed', err);
                        }
                    } else {
                        const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(window.location.origin)}`;
                        window.open(twitterUrl, '_blank');
                    }
                },

                nextQuestion() {
                    if (this.currentIndex === this.questions.length - 1) {
                        this.finishQuiz();
                    } else {
                        this.currentIndex++;
                        this.selectedAnswer = null;
                        this.answered = false;
                        this.isBookmarked = false;
                    }
                },

                async finishQuiz() {
                    try {
                        const res = await fetch('{{ route("quiz.finish") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                type: this.type,
                                title: this.quizTitle,
                                difficulty: this.difficulty,
                                score: this.score,
                                totalQuestions: this.questions.length,
                                questions: JSON.stringify(this.questions),
                                userAnswers: JSON.stringify(this.userAnswers),
                                paraNumber: this.paraNumber,
                            }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.quizFinished = true;
                            this.showResultModal = true;
                        }
                    } catch (e) {
                        alert('Failed to save quiz. Please try again.');
                    }
                },

                async toggleBookmark() {
                    try {
                        const res = await fetch('{{ route("bookmark.toggle") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                question: this.currentQuestion,
                                sourceInfo: this.type === 'PARA' ? 'Para ' + this.paraNumber : 'Seerah',
                            }),
                        });
                        const data = await res.json();
                        this.isBookmarked = data.bookmarked;
                    } catch (e) { console.error(e); }
                },

                async submitFeedback() {
                    if (!this.feedbackMessage) return;
                    this.feedbackSubmitting = true;
                    try {
                        const res = await fetch('{{ route("quiz.feedback") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                question_db_id: this.currentQuestion.dbId,
                                question_text: this.currentQuestion.text,
                                type: this.feedbackType,
                                message: this.feedbackMessage,
                            }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert(data.message);
                            this.showFeedbackModal = false;
                            this.feedbackMessage = '';
                        }
                    } catch (e) {
                        alert('Failed to submit feedback.');
                    } finally {
                        this.feedbackSubmitting = false;
                    }
                },

                async submitQuizTestimonial() {
                    if (!this.testimonialFeedback) return;
                    this.testimonialSubmitting = true;
                    try {
                        const res = await fetch('{{ route("testimonials.submit") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                name: this.testimonialName,
                                feedback: this.testimonialFeedback,
                            }),
                        });
                        const data = await res.json();
                        this.testimonialSent = true;
                    } catch (e) {
                        alert('Failed to submit testimonial.');
                    } finally {
                        this.testimonialSubmitting = false;
                    }
                },

                goToDashboard() {
                    window.location.href = '{{ route("stats") }}';
                },

                getOptionClass(idx) {
                    if (!this.answered) return 'border-slate-200 bg-white hover:border-emerald-500 hover:bg-emerald-50 hover:shadow-md active:scale-[0.99] text-slate-700';
                    const isCorrect = idx === this.currentQuestion.correctAnswerIndex;
                    const isSelected = idx === this.selectedAnswer;
                    if (isCorrect) return 'border-emerald-600 bg-emerald-100 text-emerald-900 ring-4 ring-emerald-50 z-10 scale-[1.02] shadow-sm';
                    if (isSelected) return 'border-rose-600 bg-rose-100 text-rose-900 shadow-sm';
                    return 'border-slate-100 bg-slate-50 text-slate-400 opacity-60 cursor-not-allowed';
                },

                getLetterClass(idx) {
                    if (!this.answered) return 'bg-slate-100 text-slate-500 group-hover:bg-emerald-500 group-hover:text-white';
                    const isCorrect = idx === this.currentQuestion.correctAnswerIndex;
                    const isSelected = idx === this.selectedAnswer;
                    if (isCorrect) return 'bg-emerald-600 text-white';
                    if (isSelected) return 'bg-rose-600 text-white';
                    return 'bg-slate-200 text-slate-400';
                },

                difficultyClass(d) {
                    if (d === 'Easy') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
                    if (d === 'Medium') return 'bg-amber-100 text-amber-700 border-amber-200';
                    if (d === 'Hard') return 'bg-rose-100 text-rose-700 border-rose-200';
                    return '';
                },
            };
        }
    </script>
@endpush