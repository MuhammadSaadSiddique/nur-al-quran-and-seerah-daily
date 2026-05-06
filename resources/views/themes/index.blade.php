@extends('layouts.app')

@section('title', 'Explore Islamic Themes & Quranic Knowledge - The Eternal Echo')
@section('meta_description', 'Browse our comprehensive collection of Quranic and Seerah themes. Explore questions on Prophets, Sahaba, Surahs, and more to deepen your Islamic knowledge.')

@push('styles')
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .theme-gradient-quran {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        .theme-gradient-seerah {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }

        .hero-pattern {
            background-color: #064e3b;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23065f46' fill-opacity='0.12'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zM6 30V20h2v10h10v2H8v10H6V32H0v-2h6zM30 0h-2v10H18v2h10v10h2V12h10v-2H30V0zM48 48h-4v2h4v4h2v-4h4v-2h-4v-4h-2v4zM24 24h-4v2h4v4h2v-4h4v-2h-4v-4h-2v4z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .animated-border {
            position: relative;
        }

        .animated-border::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: #059669;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .group:hover .animated-border::after {
            width: 80%;
        }
    </style>
@endpush

@section('content')
    <div class="animate-fadeIn">
        {{-- Hero Section with Banner --}}
        <div class="relative rounded-[3rem] overflow-hidden mb-16 shadow-2xl hero-pattern">
            <img src="{{ asset('islamic_knowledge_banner_1777953676083.png') }}"
                class="w-full h-80 md:h-[450px] object-cover opacity-60 mix-blend-overlay">
            <div class="absolute inset-0 bg-gradient-to-t from-emerald-950 via-emerald-900/40 to-transparent"></div>
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-6">
                <h1 class="text-5xl md:text-7xl font-black text-white tracking-tight mb-6 drop-shadow-lg">
                    Explore <span class="text-emerald-300">Wisdom</span>
                </h1>
                <p class="text-emerald-50/90 max-w-2xl text-lg md:text-xl font-medium leading-relaxed drop-shadow-md">
                    Journey through the thematic depths of the Holy Quran and the inspiring Life of the Prophet (PBUH).
                    Structured knowledge for the modern seeker.
                </p>
            </div>
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-20 -mt-24 relative z-10 px-4 md:px-12">
            <div class="bg-white/80 backdrop-blur-md p-6 rounded-[2rem] border border-white/50 shadow-xl text-center">
                <div class="text-3xl font-black text-emerald-600 mb-1">{{ $quranThemes->count() + $seerahThemes->count() }}
                </div>
                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Themes</div>
            </div>
            <div class="bg-white/80 backdrop-blur-md p-6 rounded-[2rem] border border-white/50 shadow-xl text-center">
                <div class="text-3xl font-black text-sky-600 mb-1">Global</div>
                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Knowledge Base</div>
            </div>
            <div class="bg-white/80 backdrop-blur-md p-6 rounded-[2rem] border border-white/50 shadow-xl text-center">
                <div class="text-3xl font-black text-amber-600 mb-1">100%</div>
                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Authentic</div>
            </div>
            <div class="bg-white/80 backdrop-blur-md p-6 rounded-[2rem] border border-white/50 shadow-xl text-center">
                <div class="text-3xl font-black text-emerald-600 mb-1">AI</div>
                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Powered</div>
            </div>
        </div>

        {{-- Quranic Themes --}}
        <section class="mb-24">
            <div class="flex items-center space-x-6 mb-12 px-4">
                <h2 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight">Quranic Para Themes</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-sky-200 to-transparent"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                @forelse($quranThemes as $theme)
                    <a href="{{ route('themes.show', $theme) }}"
                        class="group relative flex flex-col bg-white rounded-[2.5rem] p-8 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 overflow-hidden border border-slate-100">
                        <div
                            class="absolute top-0 right-0 w-32 h-32 bg-sky-50 rounded-bl-full -mr-10 -mt-10 group-hover:bg-sky-600 transition-colors duration-500 opacity-20 group-hover:opacity-100">
                        </div>

                        <div class="relative z-10">
                            <div
                                class="w-14 h-14 bg-sky-100 text-sky-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-white group-hover:scale-110 transition-all duration-500">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <h3
                                class="text-xl font-bold text-slate-800 group-hover:text-sky-900 transition-colors mb-3 animated-border inline-block">
                                {{ $theme->name }}
                            </h3>
                            <p class="text-slate-500 text-sm leading-relaxed mb-6 group-hover:text-slate-600">
                                {{ $theme->description ?? 'Explore various topics and lessons from this specific Quranic theme.' }}
                            </p>
                        </div>

                        <div
                            class="mt-auto pt-6 border-t border-slate-50 flex items-center justify-between text-[10px] font-black uppercase tracking-widest">
                            <span class="text-sky-600">Deep Insights</span>
                            <span
                                class="text-slate-300 group-hover:text-sky-600 group-hover:translate-x-2 transition-all">Explore
                                &rarr;</span>
                        </div>
                    </a>
                @empty
                    <p class="text-slate-400 col-span-full text-center py-12">More themes being added soon.</p>
                @endforelse
            </div>
        </section>

        {{-- Seerah Themes --}}
        <section class="mb-24">
            <div class="flex items-center space-x-6 mb-12 px-4">
                <h2 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight">Seerah & History</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-amber-200 to-transparent"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                @forelse($seerahThemes as $theme)
                    <a href="{{ route('themes.show', $theme) }}"
                        class="group relative flex flex-col bg-white rounded-[2.5rem] p-8 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 overflow-hidden border border-slate-100">
                        <div
                            class="absolute top-0 right-0 w-32 h-32 bg-amber-50 rounded-bl-full -mr-10 -mt-10 group-hover:bg-amber-600 transition-colors duration-500 opacity-20 group-hover:opacity-100">
                        </div>

                        <div class="relative z-10">
                            <div
                                class="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-white group-hover:scale-110 transition-all duration-500">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </div>
                            <h3
                                class="text-xl font-bold text-slate-800 group-hover:text-amber-900 transition-colors mb-3 animated-border inline-block">
                                {{ $theme->name }}
                            </h3>
                            <p class="text-slate-500 text-sm leading-relaxed mb-6 group-hover:text-slate-600">
                                {{ $theme->description ?? 'Discover detailed insights and historical milestones from the life of the Prophet (PBUH).' }}
                            </p>
                        </div>

                        <div
                            class="mt-auto pt-6 border-t border-slate-50 flex items-center justify-between text-[10px] font-black uppercase tracking-widest">
                            <span class="text-amber-600">Historical Facts</span>
                            <span
                                class="text-slate-300 group-hover:text-amber-600 group-hover:translate-x-2 transition-all">Explore
                                &rarr;</span>
                        </div>
                    </a>
                @empty
                    <p class="text-slate-400 col-span-full text-center py-12">History is unfolding. New themes coming.</p>
                @endforelse
            </div>
        </section>

        {{-- Premium Footer Section --}}
        <div class="relative rounded-[3.5rem] overflow-hidden p-12 md:p-20 bg-slate-900 text-white shadow-3xl">
            <div class="absolute top-0 right-0 w-96 h-96 bg-emerald-600 rounded-full blur-[120px] opacity-20 -mr-48 -mt-48">
            </div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-sky-600 rounded-full blur-[120px] opacity-10 -ml-48 -mb-48">
            </div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-4xl md:text-5xl font-black mb-8 leading-tight">Elevate Your <br><span
                            class="text-emerald-400">Islamic Learning</span></h2>
                    <div class="space-y-6 text-slate-400 text-lg leading-relaxed">
                        <p>Our thematic approach allows you to connect dots between different Surahs and historical events,
                            building a cohesive understanding of Islamic principles.</p>
                        <div class="flex flex-col space-y-4">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-6 h-6 bg-emerald-500/20 rounded-full flex items-center justify-center text-emerald-400">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-slate-300">Thematic consistency across all Para
                                    modules</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-6 h-6 bg-emerald-500/20 rounded-full flex items-center justify-center text-emerald-400">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-slate-300">In-depth Seerah timeline exploration</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-6 h-6 bg-emerald-500/20 rounded-full flex items-center justify-center text-emerald-400">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-slate-300">AI-curated difficulty progression</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-center justify-center space-y-8">
                    <div class="w-full bg-white/5 border border-white/10 p-8 rounded-[2.5rem] backdrop-blur-md">
                        <p class="text-center italic text-slate-400 mb-6">"Whoever travels a path in search of knowledge,
                            Allah will make easy for him a path to Paradise."</p>
                        <div class="flex items-center justify-center space-x-4">
                            <div class="w-10 h-px bg-white/20"></div>
                            <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Hadith, Sahih
                                Muslim</span>
                            <div class="w-10 h-px bg-white/20"></div>
                        </div>
                    </div>
                    <a href="{{ route('login') }}"
                        class="group px-12 py-5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-black transition-all shadow-2xl shadow-emerald-600/20 flex items-center space-x-3">
                        <span>Start Your Journey</span>
                        <svg class="w-5 h-5 group-hover:translate-x-2 transition-transform" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Breadcrumbs JSON-LD --}}
    @push('scripts')
        <script type="application/ld+json">
                                        {
                                          "@@context": "https://schema.org",
                                          "@@type": "BreadcrumbList",
                                          "itemListElement": [{
                                            "@@type": "ListItem",
                                            "position": 1,
                                            "name": "Home",
                                            "item": "{{ url('/') }}"
                                          },{
                                            "@@type": "ListItem",
                                            "position": 2,
                                            "name": "Themes",
                                            "item": "{{ route('themes.index') }}"
                                          }]
                                        }
                                        </script>
    @endpush
@endsection