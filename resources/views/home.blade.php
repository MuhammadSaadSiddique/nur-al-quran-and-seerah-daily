@extends('layouts.app')
@section('title', 'Dashboard - The Eternal Echo')
@section('meta_description', 'Your personal Islamic learning dashboard. Take Quran Theme challenges, Seerah journeys, and Traditional Para quizzes.')

@section('content')
    <div class="space-y-12 pb-10 animate-fadeIn" x-data="homePage()">
        {{-- Welcome --}}
        <div class="text-center space-y-4">
            <h2 class="text-3xl font-extrabold text-slate-900 md:text-4xl">
                Welcome, {{ $user->display_name ?: explode('@', $user->email)[0] }}
            </h2>
            <p class="text-slate-600 font-medium italic">"Seeking knowledge is a duty upon every Muslim."</p>
        </div>

        {{-- Global Quiz Settings --}}
        <div class="max-w-xl mx-auto bg-white rounded-3xl p-6 shadow-sm border border-slate-100 space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-widest text-slate-400">Quiz Quantity</span>
                <div class="flex bg-slate-100 p-1 rounded-xl">
                    <template x-for="q in [20, 50, 100]">
                        <button @click="quantity = q"
                            :class="quantity === q ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                            class="px-6 py-2 rounded-lg text-sm font-black transition-all" x-text="q"></button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Main Theme Selection --}}
        <div class="bg-white rounded-[3rem] p-10 shadow-xl border border-slate-100 space-y-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center space-x-4">
                    <div class="bg-indigo-100 p-4 rounded-2xl text-indigo-700">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-900">Quran Themes</h3>
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest leading-none">Choose Quran
                            topic to explore full depth</p>
                    </div>
                </div>


            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-end">
                <div class="lg:col-span-8 space-y-2">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Select Theme</label>
                    <div class="relative">
                        <select x-model="selectedThemeId"
                            class="w-full p-4 pl-12 pr-10 rounded-2xl border-2 border-slate-100 font-bold text-slate-700 focus:border-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                            <option value="">Choose a theme...</option>
                            @foreach($quranThemes as $theme)
                                <option value="{{ $theme->id }}">{{ $theme->name }}</option>
                            @endforeach
                        </select>
                        <svg class="w-6 h-6 absolute left-4 top-1/2 -translate-y-1/2 text-slate-300" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                            <svg class="fill-current h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <form method="POST" action="{{ route('quiz.theme') }}" id="theme-quiz-form">
                        @csrf
                        <input type="hidden" name="theme_id" id="selected-theme-id">
                        <input type="hidden" name="quantity" :value="quantity">

                        <div class="flex gap-2">
                            @foreach(['Easy', 'Medium', 'Hard'] as $level)
                                <button type="button" @click="submitThemeQuiz('{{ $level }}')"
                                    class="bg-emerald-600 hover:bg-emerald-700 shadow-emerald-100 flex-1 py-4 rounded-2xl text-white font-black text-sm transition-all hover:-translate-y-1 shadow-xl">
                                    {{ $level }}
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="difficulty" id="selected-difficulty">
                    </form>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Traditional Mode Card --}}
            <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-6">
                <div class="flex items-center space-x-4">
                    <div class="bg-emerald-100 p-4 rounded-2xl text-emerald-700">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-900">Traditional Mode</h3>
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Study by Quran Paras</p>
                    </div>
                </div>

                <div class="grid grid-cols-5 sm:grid-cols-6 gap-2 overflow-y-auto pr-2 custom-scrollbar">
                    @php $completedParas = $user->completed_paras ?? []; @endphp
                    @for($n = 1; $n <= 30; $n++)
                        <button @click="selectedPara = {{ $n }}"
                            class="aspect-square flex flex-col items-center justify-center rounded-xl border-2 font-black transition-all {{ in_array($n, $completedParas) ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-slate-100 bg-slate-50 text-slate-700 hover:border-emerald-500 hover:bg-white' }}">
                            <span class="text-[9px] opacity-40">P</span>{{ $n }}
                        </button>
                    @endfor
                </div>
            </div>

            {{-- General Seerah Card --}}
            <div
                class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-6 flex flex-col justify-between">
                <div class="space-y-6">
                    <div class="flex items-center space-x-4">
                        <div class="bg-blue-100 p-4 rounded-2xl text-blue-700">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900">General Seerah</h3>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Global Life Journey
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Select Seerah Theme</label>
                        <div class="relative">
                            <select x-model="selectedSeerahThemeId"
                                class="w-full p-4 pl-4 pr-10 rounded-2xl border-2 border-slate-100 font-bold text-slate-700 focus:border-blue-500 outline-none transition-all appearance-none cursor-pointer">
                                <option value="">General (All Topics)</option>
                                @foreach($seerahThemes as $theme)
                                    <option value="{{ $theme->id }}">{{ $theme->name }}</option>
                                @endforeach
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                <svg class="fill-current h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('quiz.seerah') }}" id="general-seerah-form">
                        @csrf
                        <input type="hidden" name="difficulty" id="seerah-difficulty">
                        <input type="hidden" name="quantity" :value="quantity">
                    </form>

                    <div class="grid grid-cols-1 gap-3 pt-4">
                        @foreach(['Easy', 'Medium', 'Hard'] as $level)
                            <button type="button" @click="submitSeerahQuiz('{{ $level }}')"
                                class="w-full p-4 rounded-xl border-2 border-slate-100 hover:border-blue-500 hover:bg-blue-50 transition-all font-black text-left flex items-center justify-between">
                                <span class="text-slate-800">{{ $level }} Quiz</span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M9 5l7 7-7 7" stroke-width="3" />
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Daily Insights & Reflections Section --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Seerah Insights Card --}}
            <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-6 flex flex-col justify-between hover:shadow-2xl transition-all">
                <div class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="bg-blue-100 p-4 rounded-2xl text-blue-700">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900">Seerah Insights</h3>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Daily Prophetic Reflections</p>
                        </div>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed font-medium">
                        Read inspiring narratives from the life of Prophet Muhammad ﷺ, followed by a knowledge-check question.
                    </p>
                </div>
                <a href="{{ route('seerah') }}" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-black text-sm shadow-md transition-all">
                    Explore Seerah Insights
                </a>
            </div>

            {{-- Quran History Insights Card --}}
            <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 space-y-6 flex flex-col justify-between hover:shadow-2xl transition-all">
                <div class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="bg-amber-100 p-4 rounded-2xl text-amber-700">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900">Quran History</h3>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Compilation & Miracles</p>
                        </div>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed font-medium">
                        Delve into the preservation, historical compilation, and linguistic miracles of the Holy Quran.
                    </p>
                </div>
                <a href="{{ route('quran.history') }}" class="block w-full text-center bg-amber-600 hover:bg-amber-700 text-white py-4 rounded-xl font-black text-sm shadow-md transition-all">
                    Explore Quran History
                </a>
            </div>
        </div>

        {{-- Grand Quiz Section --}}
        <div
            class="bg-gradient-to-br from-indigo-900 via-purple-900 to-slate-900 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white rounded-full translate-y-1/2 -translate-x-1/2">
                </div>
            </div>
            <div class="relative z-10 space-y-6">
                <div class="text-center space-y-3">
                    <div
                        class="inline-flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-1.5 border border-white/10">
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                        <span class="text-xs font-black uppercase tracking-widest text-amber-200">Ultimate Challenge</span>
                    </div>
                    <h3 class="text-3xl md:text-4xl font-black">Grand Quiz</h3>
                    <p class="text-indigo-200 max-w-md mx-auto">Lock in your knowledge with an extensive session across all
                        topics.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">
                    {{-- Grand Quran Quiz --}}
                    <div
                        class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/10 space-y-4 text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start space-x-3">
                            <div class="bg-emerald-500/20 p-2.5 rounded-xl">
                                <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-black">Grand Quran Quiz</h4>
                        </div>
                        <p class="text-indigo-200 text-sm"><span x-text="quantity"></span> questions from all 30 Paras</p>
                        <div class="flex flex-wrap justify-center md:justify-start gap-2">
                            @foreach(['Easy', 'Medium', 'Hard'] as $level)
                                <form method="POST" action="{{ route('quiz.grand') }}">
                                    @csrf
                                    <input type="hidden" name="quiz_type" value="QURAN">
                                    <input type="hidden" name="difficulty" value="{{ $level }}">
                                    <input type="hidden" name="quantity" :value="quantity">
                                    <button type="submit"
                                        class="bg-white/15 hover:bg-white/25 px-5 py-2.5 rounded-xl text-sm font-black border border-white/15 transition-all hover:-translate-y-0.5">{{ $level }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    {{-- Grand Seerah Quiz --}}
                    <div
                        class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/10 space-y-4 text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start space-x-3">
                            <div class="bg-blue-500/20 p-2.5 rounded-xl">
                                <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-black">Grand Seerah Quiz</h4>
                        </div>
                        <p class="text-indigo-200 text-sm"><span x-text="quantity"></span> questions from all Seerah themes
                        </p>
                        <div class="flex flex-wrap justify-center md:justify-start gap-2">
                            @foreach(['Easy', 'Medium', 'Hard'] as $level)
                                <form method="POST" action="{{ route('quiz.grand') }}">
                                    @csrf
                                    <input type="hidden" name="quiz_type" value="SEERAH">
                                    <input type="hidden" name="difficulty" value="{{ $level }}">
                                    <input type="hidden" name="quantity" :value="quantity">
                                    <button type="submit"
                                        class="bg-white/15 hover:bg-white/25 px-5 py-2.5 rounded-xl text-sm font-black border border-white/15 transition-all hover:-translate-y-0.5">{{ $level }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Future Vision & Roadmap --}}
        <div class="bg-white rounded-[3rem] p-10 shadow-xl border border-slate-100 space-y-8">
            <div class="flex items-center space-x-4">
                <div class="bg-indigo-100 p-4 rounded-2xl text-indigo-700">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-900 font-sans">Future Roadmap & Vision</h3>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest leading-none">The path forward for The Eternal Echo</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 space-y-3 hover:border-indigo-500/30 transition-all duration-300">
                    <span class="text-2xl">👥</span>
                    <h4 class="font-bold text-slate-800 text-base">Study Clubs & Halaqas</h4>
                    <p class="text-slate-500 text-xs leading-relaxed">Collaborative circles with peer discussions, group tasks, and shared dashboards for mutual growth.</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 space-y-3 hover:border-indigo-500/30 transition-all duration-300">
                    <span class="text-2xl">📚</span>
                    <h4 class="font-bold text-slate-800 text-base">Scholarly Literature Quizzes</h4>
                    <p class="text-slate-500 text-xs leading-relaxed">Quizzes taken directly from classic Hadith books, *Ar-Raheeq Al-Makhtum*, and major theological works.</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 space-y-3 hover:border-indigo-500/30 transition-all duration-300">
                    <span class="text-2xl">🎓</span>
                    <h4 class="font-bold text-slate-800 text-base">Literature of Every Scholar</h4>
                    <p class="text-slate-500 text-xs leading-relaxed">Structured learning modules focusing on the specific books, fatwas, and literatures of every historical scholar.</p>
                </div>
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 space-y-3 hover:border-indigo-500/30 transition-all duration-300">
                    <span class="text-2xl">🤖</span>
                    <h4 class="font-bold text-slate-800 text-base">AI Virtual Tutors</h4>
                    <p class="text-slate-500 text-xs leading-relaxed">Interactive Quranic study buddies to analyze weak areas and customize daily spiritual regimens.</p>
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
                    <p class="text-sm font-bold text-emerald-600 uppercase tracking-widest">Traditional Study</p>
                </div>
                <div class="grid grid-cols-1 gap-3">
                    @foreach(['Easy', 'Medium', 'Hard'] as $level)
                        <form method="POST" action="{{ route('quiz.para') }}">
                            @csrf
                            <input type="hidden" name="difficulty" value="{{ $level }}">
                            <input type="hidden" name="para" :value="selectedPara">
                            <input type="hidden" name="quantity" :value="quantity">
                            <button type="submit"
                                class="w-full p-5 rounded-xl border-2 border-slate-100 hover:border-emerald-500 hover:bg-emerald-50 transition-all font-black text-lg text-left flex justify-between items-center group">
                                <span class="text-slate-800">{{ $level }}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[10px] text-slate-400" x-text="quantity + ' Qs'"></span>
                                    <svg class="w-5 h-5 text-slate-300 group-hover:text-emerald-500 transition-colors"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path d="M9 5l7 7-7 7" stroke-width="3" />
                                    </svg>
                                </div>
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
                selectedThemeId: '',
                selectedSeerahThemeId: '',
                quantity: 20,

                submitThemeQuiz(difficulty) {
                    if (!this.selectedThemeId) {
                        alert('Please select a Quran theme.');
                        return;
                    }

                    document.getElementById('selected-theme-id').value = this.selectedThemeId;
                    document.getElementById('selected-difficulty').value = difficulty;
                    document.getElementById('theme-quiz-form').querySelector('input[name="quantity"]').value = this.quantity;
                    document.getElementById('theme-quiz-form').submit();
                },

                submitSeerahQuiz(difficulty) {
                    if (this.selectedSeerahThemeId) {
                        document.getElementById('selected-theme-id').value = this.selectedSeerahThemeId;
                        document.getElementById('selected-difficulty').value = difficulty;
                        document.getElementById('theme-quiz-form').querySelector('input[name="quantity"]').value = this.quantity;
                        document.getElementById('theme-quiz-form').submit();
                    } else {
                        document.getElementById('seerah-difficulty').value = difficulty;
                        document.getElementById('general-seerah-form').querySelector('input[name="quantity"]').value = this.quantity;
                        document.getElementById('general-seerah-form').submit();
                    }
                }
            };
        }
    </script>
@endpush