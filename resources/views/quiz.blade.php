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
                                Check • Question <span x-text="currentIndex + 1"></span> of <span
                                    x-text="questions.length"></span></span>
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

                        <button @click="showShareModal = true" title="Share to Social Media"
                            class="bg-slate-100 text-slate-400 hover:text-blue-500 hover:bg-blue-50 p-2 rounded-full transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
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
                        <p class="text-blue-800 text-md leading-relaxed font-medium" x-text="currentQuestion?.explanation">
                        </p>
                        <p x-show="currentQuestion?.reference"
                            class="text-blue-700 text-sm mt-2 font-semibold flex items-center gap-1">
                            <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            <span x-text="'Ref: ' + currentQuestion?.reference"></span>
                        </p>
                    </div>
                </div>

                {{-- Feedback Button --}}
                <div class="mt-4 pt-3 border-t border-blue-200 flex justify-end">
                    <button @click="showFeedbackModal = true"
                        class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 px-3 py-1.5 rounded-lg transition-all inline-flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                        </svg>
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
                    <div
                        class="w-24 h-24 bg-emerald-100 rounded-[2rem] flex items-center justify-center mx-auto text-emerald-600 shadow-inner group">
                        <svg class="w-12 h-12 transform group-hover:scale-110 transition-transform" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
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
                        <span x-text="score"></span><span class="text-slate-300 mx-1">/</span><span
                            x-text="maxScore"></span>
                    </div>
                </div>

                {{-- Testimonial Form --}}
                <div class="space-y-6" x-show="!testimonialSent">
                    <div class="space-y-2">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider text-center">Share Your Wisdom
                        </h4>
                        <p class="text-slate-500 text-xs font-medium text-center italic">How did this quiz impact your
                            journey?</p>
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
                    <p class="text-slate-500 text-sm font-medium italic">Your words will inspire others on their spiritual
                        path.</p>
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
        {{-- Share Modal --}}
        <div x-show="showShareModal" x-cloak
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[300] flex items-end sm:items-center justify-center p-4"
            @click.self="showShareModal = false" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            <div class="bg-white rounded-t-[2rem] sm:rounded-[2rem] w-full max-w-md shadow-2xl animate-slideUp" @click.stop>
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 pt-6 pb-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-900">Share Question</h3>
                        <p class="text-xs text-slate-400 font-medium">Challenge your friends!</p>
                    </div>
                    <button @click="showShareModal = false"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 p-2 rounded-full transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Question Preview --}}
                <div class="mx-6 mb-4 p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-sm font-semibold text-slate-700 line-clamp-2" x-text="currentQuestion?.text"></p>
                </div>

                {{-- Platform Grid --}}
                <div class="grid grid-cols-4 gap-3 px-6 pb-6">
                    {{-- WhatsApp --}}
                    <button @click="shareTo('whatsapp')"
                        class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-green-50 transition-all group">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all"
                            style="background: #25D366">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500">WhatsApp</span>
                    </button>

                    {{-- Facebook --}}
                    <button @click="shareTo('facebook')"
                        class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-blue-50 transition-all group">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all"
                            style="background: #1877F2">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500">Facebook</span>
                    </button>

                    {{-- Twitter/X --}}
                    <button @click="shareTo('twitter')"
                        class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-slate-50 transition-all group">
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all bg-black">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500">X</span>
                    </button>

                    {{-- Telegram --}}
                    <button @click="shareTo('telegram')"
                        class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-sky-50 transition-all group">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all"
                            style="background: #0088cc">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500">Telegram</span>
                    </button>

                    {{-- LinkedIn --}}
                    <button @click="shareTo('linkedin')"
                        class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-blue-50 transition-all group">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all"
                            style="background: #0A66C2">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500">LinkedIn</span>
                    </button>

                    {{-- Email --}}
                    <button @click="shareTo('email')"
                        class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-orange-50 transition-all group">
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all bg-gradient-to-br from-orange-400 to-rose-500">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500">Email</span>
                    </button>

                    {{-- Copy Link --}}
                    <button @click="shareTo('copy')"
                        class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-emerald-50 transition-all group">
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all bg-gradient-to-br from-emerald-400 to-teal-500">
                            <svg x-show="!linkCopied" class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                            <svg x-show="linkCopied" x-cloak class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold" :class="linkCopied ? 'text-emerald-600' : 'text-slate-500'"
                            x-text="linkCopied ? 'Copied!' : 'Copy Text'"></span>
                    </button>

                    {{-- More (native share) --}}
                    <button x-show="!!navigator.share" @click="shareTo('native')"
                        class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-violet-50 transition-all group">
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all bg-gradient-to-br from-violet-400 to-purple-600">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500">More</span>
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

                // Share state
                showShareModal: false,
                linkCopied: false,

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
                        const difficulty = this.currentQuestion.difficulty || this.difficulty;
                        let points = 5;
                        if (difficulty === 'Medium') points = 10;
                        if (difficulty === 'Hard') points = 15;
                        this.score += points;
                    }
                    this.userAnswers.push(idx);
                },

                get maxScore() {
                    return this.questions.reduce((total, q) => {
                        const diff = q.difficulty || this.difficulty;
                        let points = 5;
                        if (diff === 'Medium') points = 10;
                        if (diff === 'Hard') points = 15;
                        return total + points;
                    }, 0);
                },

                getShareText() {
                    const q = this.currentQuestion;
                    return `Challenge: Can you answer this?\n\n"${q.text}"\n\nTake the quiz at The Eternal Echo!\n#Quran #Seerah #IslamicQuiz #TheEternalEcho`;
                },

                async shareTo(platform) {
                    const text = this.getShareText();
                    const url = window.location.origin;
                    const encodedText = encodeURIComponent(text);
                    const encodedUrl = encodeURIComponent(url);

                    const shareUrls = {
                        whatsapp: `https://wa.me/?text=${encodeURIComponent(text + '\n' + url)}`,
                        facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}&quote=${encodedText}`,
                        twitter: `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}`,
                        telegram: `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`,
                        linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
                        email: `mailto:?subject=${encodeURIComponent('The Eternal Echo Quiz Challenge')}&body=${encodeURIComponent(text + '\n\n' + url)}`,
                    };

                    if (platform === 'copy') {
                        try {
                            await navigator.clipboard.writeText(text + '\n\n' + url);
                            this.linkCopied = true;
                            setTimeout(() => this.linkCopied = false, 2000);
                        } catch (e) {
                            // Fallback for older browsers
                            const ta = document.createElement('textarea');
                            ta.value = text + '\n\n' + url;
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                            this.linkCopied = true;
                            setTimeout(() => this.linkCopied = false, 2000);
                        }
                        return;
                    }

                    if (platform === 'native') {
                        try {
                            await navigator.share({ title: 'The Eternal Echo Quiz Challenge', text: text, url: url });
                        } catch (err) { console.log('Share canceled', err); }
                        this.showShareModal = false;
                        return;
                    }

                    if (shareUrls[platform]) {
                        window.open(shareUrls[platform], '_blank', 'noopener,noreferrer,width=600,height=500');
                    }
                    this.showShareModal = false;
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