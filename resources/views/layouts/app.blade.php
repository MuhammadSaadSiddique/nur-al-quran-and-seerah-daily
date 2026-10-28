<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO Meta Tags --}}
    <title>@yield('title', 'The Eternal Echo - AI-Powered Islamic Learning')</title>
    <meta name="description"
        content="@yield('meta_description', 'The Eternal Echo — An AI-powered Islamic learning platform. Take personalized Quran Para quizzes, Seerah knowledge challenges, and track your spiritual growth journey.')">
    @hasSection('keywords')
        <meta name="keywords" content="@yield('keywords')">
    @else
        <meta name="keywords" content="Quran quiz, Islamic learning, Seerah, Prophet Muhammad, AI education, Islamic knowledge, Quran paras, Islamic quiz app">
    @endif
    <meta name="author" content="The Eternal Echo">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('eternal.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('eternal.png') }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('title', 'The Eternal Echo - AI-Powered Islamic Learning')">
    <meta property="og:description"
        content="@yield('meta_description', 'AI-powered Islamic learning platform with Quran Para quizzes, Seerah challenges, and personalized insights.')">
    <meta property="og:image" content="@yield('og_image', asset('eternal.png'))">
    <meta property="og:image:alt" content="The Eternal Echo">
    <meta property="og:site_name" content="The Eternal Echo">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'The Eternal Echo - AI-Powered Islamic Learning')">
    <meta name="twitter:description"
        content="@yield('meta_description', 'AI-powered Islamic learning platform with Quran Para quizzes, Seerah challenges, and personalized insights.')">
    <meta name="twitter:image" content="@yield('og_image', asset('eternal.png'))">
    <meta name="twitter:image:alt" content="The Eternal Echo">

    {{-- JSON-LD Structured Data --}}
    @stack('schema')
    @hasSection('schema')
        @yield('schema')
    @else
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "EducationalApplication",
            "name": "The Eternal Echo",
            "description": "AI-powered Islamic learning platform with personalized Quran quizzes and Seerah knowledge challenges",
            "applicationCategory": "EducationalApplication",
            "operatingSystem": "Web",
            "offers": {
                "@@type": "Offer",
                "price": "0",
                "priceCurrency": "USD"
            }
        }
        </script>
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Amiri:wght@400;700&family=Scheherazade+New:wght@400;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .font-arabic {
            font-family: 'Scheherazade New', 'Amiri', serif;
        }

        [x-cloak] {
            display: none !important;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .animate-slideUp {
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        html.keyboard-open .mobile-bottom-nav {
            visibility: hidden;
            pointer-events: none;
        }

        @media (max-width: 900px) {
            button,
            input[type="submit"],
            input,
            textarea {
                scroll-margin-bottom: 24px;
            }
        }

        /* Keep fixed dialogs inside the area above the keyboard and let them scroll. */
        html.keyboard-open [class~="fixed"][class~="inset-0"] {
            top: var(--vv-offset-top, 0px) !important;
            bottom: auto !important;
            height: var(--vv-height, 100dvh) !important;
            max-height: var(--vv-height, 100dvh) !important;
            overflow-y: auto !important;
            align-items: flex-start !important;
            justify-content: flex-start !important;
            -webkit-overflow-scrolling: touch;
        }

        html.keyboard-open [class~="fixed"][class~="inset-0"] > :not([class~="fixed"]) {
            margin-top: auto !important;
            margin-bottom: auto !important;
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen flex flex-col bg-slate-50">

    {{-- Header --}}
    <header class="bg-slate-900/80 backdrop-blur-md border-b border-white/10 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('lens.landing') }}" class="flex items-center space-x-2">
                <div class="p-1 rounded-3xl">
                    <img src="{{ asset('eternal.png') }}" alt="The Eternal Echo" class="w-8 h-8 object-contain">
                </div>
                <span class="hidden xl:inline font-black text-xl tracking-tight text-white">The Eternal Echo</span>
            </a>

            @php
                if (auth()->check()) {
                    $navItems = [
                        ['route' => 'welcome', 'label' => 'Read Quran', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['route' => 'seerah', 'label' => 'Seerah', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                        ['route' => 'quran.history', 'label' => 'History', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => 'duas.index', 'label' => 'Duas', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                        ['route' => 'researchers.index', 'label' => 'Researchers', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['route' => 'home', 'label' => 'Quizzes', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ['route' => 'stats', 'label' => 'Progress', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                        ['route' => 'leaderboard', 'label' => 'Rankings', 'icon' => 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'],
                    
                    ];
                    if (auth()->user()->is_admin) {
                        $navItems[] = ['route' => 'admin.questions.index', 'label' => 'Admin', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'];
                    }
                    if (auth()->user()->is_admin || auth()->user()->is_researcher) {
                        $approvalsRoute = (auth()->user()->is_researcher && !auth()->user()->is_admin && !auth()->user()->hasAcceptedResearcherPledge())
                            ? 'researchers.onboarding'
                            : 'admin.lens.approvals.index';
                        $navItems[] = ['route' => $approvalsRoute, 'label' => 'Approvals', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'];
                    }
                } else {
                    $navItems = [
                        ['route' => 'welcome', 'label' => 'Read Quran', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['route' => 'seerah', 'label' => 'Seerah', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                        ['route' => 'quran.history', 'label' => 'History', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => 'duas.index', 'label' => 'Duas', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                        ['route' => 'quiz.learning', 'label' => 'Quiz Challenges', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ['route' => 'researchers.index', 'label' => 'Researchers', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['route' => 'leaderboard', 'label' => 'Rankings', 'icon' => 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'],
                    ];
                }
            @endphp

            @php
                $primaryRoutes = auth()->check()
                    ? ['welcome', 'seerah', 'duas.index', 'home']
                    : ['welcome', 'seerah', 'duas.index', 'quiz.learning'];
                $primaryNav = [];
                $moreNav = [];
                foreach ($navItems as $item) {
                    if (in_array($item['route'], $primaryRoutes, true)) {
                        $primaryNav[] = $item;
                    } else {
                        $moreNav[] = $item;
                    }
                }
                $moreIsActive = collect($moreNav)->contains(fn ($item) => request()->routeIs($item['route']));
            @endphp

            <nav class="hidden md:flex items-center space-x-1">
                @foreach($primaryNav as $item)
                    <a href="{{ route($item['route']) }}"
                        class="px-3 py-2 rounded-xl text-sm font-bold transition-all {{ request()->routeIs($item['route']) ? 'bg-white/20 text-white' : 'text-emerald-100 hover:text-white hover:bg-white/10' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
                @if(count($moreNav))
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open"
                            class="px-3 py-2 rounded-xl text-sm font-bold transition-all {{ $moreIsActive ? 'bg-white/20 text-white' : 'text-emerald-100 hover:text-white hover:bg-white/10' }}">
                            More
                        </button>
                        <div x-show="open" @click.outside="open = false" x-cloak
                            class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 z-50 text-slate-900">
                            @foreach($moreNav as $item)
                                <a href="{{ route($item['route']) }}"
                                    class="block px-4 py-2.5 text-sm font-bold transition-colors {{ request()->routeIs($item['route']) ? 'bg-emerald-50 text-emerald-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                            <a href="{{ route('donate') }}"
                                class="block px-4 py-2.5 text-sm font-bold text-emerald-700 hover:bg-emerald-50 transition-colors border-t border-slate-100 mt-1">
                                Support Us
                            </a>
                        </div>
                    </div>
                @endif
            </nav>

            <div class="flex items-center space-x-2">

                @auth
                    @php
                        $streakStatus = auth()->user()->getStreakStatus();
                    @endphp
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-black transition-all {{ $streakStatus['is_active_today'] ? 'bg-amber-500/20 text-amber-300 border border-amber-400/30' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 border border-slate-700' }}"
                                title="Daily Streak">
                            <span>🔥</span>
                            <span>{{ $streakStatus['current_streak'] }} {{ \Illuminate\Support\Str::plural('Day', $streakStatus['current_streak']) }}</span>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-cloak
                             class="absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-100 p-5 z-50 text-slate-900 animate-slideUp">
                            <div class="flex items-center justify-between mb-3">
                                <span class="font-black text-sm uppercase tracking-wider text-slate-400">Daily Streak</span>
                                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-full">🔥 Best: {{ $streakStatus['longest_streak'] }}d</span>
                            </div>
                            <div class="text-center py-2 space-y-1">
                                <p class="text-3xl font-black text-slate-900">{{ $streakStatus['current_streak'] }} <span class="text-lg text-amber-500">Days</span></p>
                                <p class="text-xs text-slate-500 font-medium">
                                    {{ $streakStatus['is_active_today'] ? 'Streak active today! Keep the consistency alive tomorrow.' : 'Check in today to keep your spiritual consistency!' }}
                                </p>
                            </div>
                            @if(!$streakStatus['is_active_today'])
                                <form action="{{ route('streak.check-in') }}" method="POST" class="mt-4">
                                    @csrf
                                    <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-orange-500 text-white font-black py-2.5 rounded-xl text-xs shadow-md hover:opacity-95 transition-all">
                                        ⚡ Claim Today's Streak
                                    </button>
                                </form>
                            @else
                                <div class="mt-3 text-center py-2 bg-emerald-50 rounded-xl border border-emerald-100">
                                    <span class="text-xs font-bold text-emerald-700">✅ Completed Today</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('profile') }}"
                        class="flex items-center p-2 rounded-xl text-xs font-black transition-all {{ request()->routeIs('profile') ? 'bg-white/25 text-white border border-white/40' : 'bg-white/10 hover:bg-white/20 text-emerald-100 hover:text-white border border-white/15' }}"
                        title="My Profile"
                        aria-label="My Profile">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </a>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="p-2 hover:bg-rose-500 rounded-xl transition-colors text-emerald-100 hover:text-white"
                            title="Logout">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                        class="bg-white text-emerald-800 px-6 py-2 rounded-xl text-sm font-black hover:bg-emerald-50 transition-all shadow-lg">
                        Login
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-grow container mx-auto px-4 py-8 max-w-7xl">
        @if(session('error'))
            <div class="mb-6 p-4 bg-rose-100 border border-rose-200 rounded-2xl text-rose-700 font-bold animate-fadeIn">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div
                class="mb-6 p-4 bg-emerald-100 border border-emerald-200 rounded-2xl text-emerald-700 font-bold animate-fadeIn">
                {{ session('success') }}
            </div>
        @endif
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-slate-500 py-12 text-center text-sm pb-24 md:pb-12">
        <div class="max-w-7xl mx-auto px-4 space-y-4">
            <p class="opacity-50 text-slate-400">&copy; {{ date('Y') }} The Eternal Echo. <a
                    href="https://asloobulhayat.com/">Asloob
                    ul Hayat Project.</a></p>
            <div class="flex justify-center">
                @include('partials.play-store-link')
            </div>
            <div
                class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-[10px] uppercase font-black tracking-widest text-slate-500">
                <a href="{{ route('donate') }}" class="text-emerald-400 hover:text-emerald-300 font-black transition-colors">🤲 Sadaqah Jariyah / Donate</a>
                <a href="{{ route('privacy') }}" class="hover:text-emerald-500 transition-colors">Privacy Policy</a>
                <a href="{{ route('terms') }}" class="hover:text-emerald-500 transition-colors">Terms of Service</a>
                <a href="{{ route('account.delete') }}" class="hover:text-rose-400 transition-colors">Delete Account</a>
            </div>
        </div>
    </footer>

    {{-- Mobile Nav --}}
    <div
        class="mobile-bottom-nav md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 flex justify-around p-2 z-50">
        @foreach(array_slice($navItems, 0, 5) as $item)
            <a href="{{ route($item['route']) }}"
                class="flex flex-col items-center p-2 flex-1 transition-colors {{ request()->routeIs($item['route']) ? 'text-emerald-600' : 'text-slate-400' }}">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="{{ request()->routeIs($item['route']) ? '2.5' : '2' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                </svg>
                <span class="text-[10px] mt-1 font-bold">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
    
    {{-- Global App Feedback Widget (Modal & Trigger) --}}
    @include('partials.app-feedback-modal')

    @stack('scripts')
    <script>
        (function () {
            var root = document.documentElement;

            function syncViewport() {
                var vv = window.visualViewport;
                var height = vv ? vv.height : window.innerHeight;
                var offsetTop = vv ? vv.offsetTop : 0;
                root.style.setProperty('--vv-height', Math.round(height) + 'px');
                root.style.setProperty('--vv-offset-top', Math.round(offsetTop) + 'px');
                var keyboardInset = Math.max(0, window.innerHeight - height - offsetTop);
                root.classList.toggle('keyboard-open', keyboardInset > 72);
            }

            syncViewport();
            if (window.visualViewport) {
                visualViewport.addEventListener('resize', syncViewport);
                visualViewport.addEventListener('scroll', syncViewport);
            }
            window.addEventListener('resize', syncViewport);

            function isTextField(el) {
                if (!el || el.nodeType !== 1 || el.disabled || el.readOnly) return false;
                if (el.tagName === 'TEXTAREA') return true;
                if (el.tagName !== 'INPUT') return false;
                var type = (el.getAttribute('type') || 'text').toLowerCase();
                return ['hidden', 'checkbox', 'radio', 'button', 'submit', 'file', 'range', 'color', 'image'].indexOf(type) === -1;
            }

            function nearestAction(field) {
                var node = field.parentElement;
                while (node && node !== document.body) {
                    var buttons = node.querySelectorAll('button, input[type="submit"]');
                    var fallback = null;
                    for (var i = 0; i < buttons.length; i++) {
                        var button = buttons[i];
                        if (button.disabled || button.offsetParent === null) continue;
                        if (!(field.compareDocumentPosition(button) & Node.DOCUMENT_POSITION_FOLLOWING)) continue;
                        var type = (button.getAttribute('type') || 'submit').toLowerCase();
                        if (type === 'submit') return button;
                        if (!fallback && type !== 'reset') fallback = button;
                    }
                    if (fallback) return fallback;
                    node = node.parentElement;
                }
                return null;
            }

            function revealAction(field) {
                var action = nearestAction(field);
                if (!action) return;
                var fieldRect = field.getBoundingClientRect();
                var actionRect = action.getBoundingClientRect();
                if (actionRect.top - fieldRect.bottom > 480) return;
                action.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }

            document.addEventListener('focusin', function (event) {
                if (!isTextField(event.target)) return;
                if (window.matchMedia('(min-width: 901px) and (pointer: fine)').matches) return;
                window.setTimeout(function () { revealAction(event.target); }, 60);
                window.setTimeout(function () { revealAction(event.target); }, 340);
            });
        })();
    </script>
</body>

</html>
