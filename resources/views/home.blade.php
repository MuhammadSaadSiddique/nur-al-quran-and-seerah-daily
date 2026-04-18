@extends('layouts.app')
@section('title', 'Dashboard - Nur Al-Quran & Seerah Daily')
@section('meta_description', 'Your personal Islamic learning dashboard. Take Quran Para quizzes, Seerah challenges, and Grand Quizzes to deepen your knowledge.')

@section('content')
    <div class="space-y-12 pb-10 animate-fadeIn" x-data="homePage()">
        {{-- Welcome --}}
        <div class="text-center space-y-4">
            <h2 class="text-3xl font-extrabold text-slate-900 md:text-4xl">
                Welcome, {{ $user->display_name ?: explode('@', $user->email)[0] }}
            </h2>
            <p class="text-slate-600 font-medium">Explore the Quranic Wisdom & Seerah Insights daily.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Quran Paras Card --}}
            <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-6">
                <div class="flex items-center space-x-4">
                    <div class="bg-emerald-100 p-4 rounded-2xl text-emerald-700">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                stroke-width="2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-900">Quran Paras</h3>
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Select a Para to begin
                            quiz</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Optional Theme Filter</label>
                    <select x-model="quranTheme"
                        class="w-full p-3 rounded-xl border-2 border-slate-100 font-bold text-slate-700 focus:border-emerald-500 outline-none">
                        <option>Any Theme</option>
                        @foreach($quranThemes as $theme)
                            <option>{{ $theme }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-5 sm:grid-cols-6 gap-2 max-h-[220px] overflow-y-auto pr-2 custom-scrollbar">
                    @php $completedParas = $user->completed_paras ?? []; @endphp
                    @for($n = 1; $n <= 30; $n++)
                        <button @click="selectedPara = {{ $n }}"
                            class="aspect-square flex flex-col items-center justify-center rounded-xl border-2 font-black transition-all {{ in_array($n, $completedParas) ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-slate-100 bg-slate-50 text-slate-700 hover:border-emerald-500 hover:bg-white' }}">
                            <span class="text-[9px] opacity-40">P</span>{{ $n }}
                        </button>
                    @endfor
                </div>
            </div>

            {{-- Seerah Card --}}
            <div
                class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-6 flex flex-col justify-between">
                <div class="space-y-6">
                    <div class="flex items-center space-x-4">
                        <div class="bg-blue-100 p-4 rounded-2xl text-blue-700">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
                                    stroke-width="2" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900">Seerah Journey</h3>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Life of Prophet
                                Muhammad (SAWW)</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Optional Theme Filter</label>
                        <select x-model="seerahTheme"
                            class="w-full p-3 rounded-xl border-2 border-slate-100 font-bold text-slate-700 focus:border-blue-500 outline-none">
                            <option>Any Theme</option>
                            @foreach($seerahThemes as $theme)
                                <option>{{ $theme }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-1 gap-3">
                        @foreach(['Easy', 'Medium', 'Hard'] as $level)
                            <form method="POST" action="{{ route('quiz.seerah') }}" class="seerah-form">
                                @csrf
                                <input type="hidden" name="difficulty" value="{{ $level }}">
                                <input type="hidden" name="theme" :value="seerahTheme">
                                <button type="submit"
                                    class="w-full p-4 rounded-xl border-2 border-slate-100 hover:border-blue-500 hover:bg-blue-50 transition-all font-black text-left flex items-center justify-between">
                                    <span class="text-slate-800">{{ $level }} Seerah Quiz</span>
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path d="M9 5l7 7-7 7" stroke-width="3" />
                                    </svg>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Grand Quiz Section --}}
        <div class="bg-gradient-to-br from-indigo-900 via-purple-900 to-slate-900 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden">
            {{-- Decorative pattern --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white rounded-full translate-y-1/2 -translate-x-1/2"></div>
            </div>
            <div class="relative z-10 space-y-6">
                <div class="text-center space-y-3">
                    <div class="inline-flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-1.5 border border-white/10">
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span class="text-xs font-black uppercase tracking-widest text-amber-200">Ultimate Challenge</span>
                    </div>
                    <h3 class="text-3xl md:text-4xl font-black">Grand Quiz</h3>
                    <p class="text-indigo-200 max-w-md mx-auto">Test your comprehensive knowledge with 20 questions across all topics. The ultimate challenge!</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">
                    {{-- Grand Quran Quiz --}}
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/10 space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="bg-emerald-500/20 p-2.5 rounded-xl">
                                <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-black">Grand Quran Quiz</h4>
                        </div>
                        <p class="text-indigo-200 text-sm">20 questions from all 30 Paras</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['Easy', 'Medium', 'Hard'] as $level)
                                <form method="POST" action="{{ route('quiz.grand') }}">
                                    @csrf
                                    <input type="hidden" name="quiz_type" value="QURAN">
                                    <input type="hidden" name="difficulty" value="{{ $level }}">
                                    <button type="submit"
                                        class="bg-white/15 hover:bg-white/25 px-5 py-2.5 rounded-xl text-sm font-black border border-white/15 transition-all hover:-translate-y-0.5">{{ $level }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    {{-- Grand Seerah Quiz --}}
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/10 space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="bg-blue-500/20 p-2.5 rounded-xl">
                                <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-black">Grand Seerah Quiz</h4>
                        </div>
                        <p class="text-indigo-200 text-sm">20 questions from all Seerah themes</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['Easy', 'Medium', 'Hard'] as $level)
                                <form method="POST" action="{{ route('quiz.grand') }}">
                                    @csrf
                                    <input type="hidden" name="quiz_type" value="SEERAH">
                                    <input type="hidden" name="difficulty" value="{{ $level }}">
                                    <button type="submit"
                                        class="bg-white/15 hover:bg-white/25 px-5 py-2.5 rounded-xl text-sm font-black border border-white/15 transition-all hover:-translate-y-0.5">{{ $level }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Coming Soon: Daily Insights --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Quran Insight Coming Soon --}}
            <div class="relative bg-gradient-to-br from-amber-50 to-amber-100/50 rounded-[2rem] p-8 shadow-lg border border-amber-200/50 overflow-hidden">
                <div class="absolute top-4 right-4 bg-amber-500 text-white text-[9px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full shadow-lg shadow-amber-500/30">
                    Coming Soon
                </div>
                <div class="space-y-4 opacity-80">
                    <div class="flex items-center space-x-3">
                        <div class="bg-amber-200 p-3 rounded-xl text-amber-700">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-amber-900">Quran Daily Insight</h3>
                            <p class="text-xs font-bold text-amber-600 uppercase tracking-widest">Historical Reflections</p>
                        </div>
                    </div>
                    <p class="text-amber-800 text-sm leading-relaxed">Daily insights into how the Quran was revealed, preserved, and compiled through history. Coming soon with AI-powered reflections.</p>
                </div>
            </div>

            {{-- Seerah Daily Insight Coming Soon --}}
            <div class="relative bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-[2rem] p-8 shadow-lg border border-blue-200/50 overflow-hidden">
                <div class="absolute top-4 right-4 bg-blue-500 text-white text-[9px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full shadow-lg shadow-blue-500/30">
                    Coming Soon
                </div>
                <div class="space-y-4 opacity-80">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-200 p-3 rounded-xl text-blue-700">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-blue-900">Seerah Daily Insight</h3>
                            <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Life of the Prophet ﷺ</p>
                        </div>
                    </div>
                    <p class="text-blue-800 text-sm leading-relaxed">Daily inspirational stories and reflections from the Prophet's life. Coming soon with interactive quiz questions.</p>
                </div>
            </div>
        </div>

        {{-- Para Selection Modal --}}
        <div x-show="selectedPara" x-cloak
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] flex items-center justify-center p-6 animate-fadeIn"
            @click.self="selectedPara = null">
            <div class="bg-white rounded-[2.5rem] p-8 max-w-lg w-full shadow-2xl space-y-6 animate-slideUp">
                <div class="text-center space-y-2">
                    <h3 class="text-2xl font-black text-slate-900 uppercase" x-text="'Para ' + selectedPara"></h3>
                    <p class="text-sm font-bold text-emerald-600 uppercase tracking-widest" x-text="quranTheme"></p>
                </div>
                <div class="grid grid-cols-1 gap-3">
                    @foreach(['Easy', 'Medium', 'Hard'] as $level)
                        <form method="POST" action="{{ route('quiz.para') }}">
                            @csrf
                            <input type="hidden" name="difficulty" value="{{ $level }}">
                            <input type="hidden" name="para" :value="selectedPara">
                            <input type="hidden" name="theme" :value="quranTheme">
                            <button type="submit"
                                class="w-full p-5 rounded-xl border-2 border-slate-100 hover:border-emerald-500 hover:bg-emerald-50 transition-all font-black text-lg text-left flex justify-between items-center group">
                                <span class="text-slate-800">{{ $level }}</span>
                                <svg class="w-5 h-5 text-slate-300 group-hover:text-emerald-500 transition-colors" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M9 5l7 7-7 7" stroke-width="3" />
                                </svg>
                            </button>
                        </form>
                    @endforeach
                </div>
                <button @click="selectedPara = null"
                    class="w-full text-slate-400 font-black uppercase text-xs tracking-widest hover:text-rose-500 transition-colors">Cancel
                    Selection</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function homePage() {
            return {
                selectedPara: null,
                quranTheme: 'Any Theme',
                seerahTheme: 'Any Theme',
            };
        }
    </script>
@endpush