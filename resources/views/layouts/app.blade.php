<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO Meta Tags --}}
    <title>@yield('title', 'The Eternal Echo - AI-Powered Islamic Learning')</title>
    <meta name="description"
        content="@yield('meta_description', 'The Eternal Echo — An AI-powered Islamic learning platform. Take personalized Quran Para quizzes, Seerah knowledge challenges, and track your spiritual growth journey.')">
    <meta name="keywords"
        content="Quran quiz, Islamic learning, Seerah, Prophet Muhammad, AI education, Islamic knowledge, Quran paras, Islamic quiz app">
    <meta name="author" content="The Eternal Echo">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'The Eternal Echo - AI-Powered Islamic Learning')">
    <meta property="og:description"
        content="@yield('meta_description', 'AI-powered Islamic learning platform with Quran Para quizzes, Seerah challenges, and personalized insights.')">
    <meta property="og:site_name" content="The Eternal Echo">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'The Eternal Echo - AI-Powered Islamic Learning')">
    <meta name="twitter:description"
        content="@yield('meta_description', 'AI-powered Islamic learning platform with Quran Para quizzes, Seerah challenges, and personalized insights.')">

    {{-- JSON-LD Structured Data --}}
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

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Amiri:wght@400;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .font-arabic {
            font-family: 'Amiri', serif;
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
    </style>
    @stack('styles')
</head>

<body class="min-h-screen flex flex-col bg-slate-50">

    {{-- Header --}}
    <header class="bg-slate-900/80 backdrop-blur-md border-b border-white/10 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ auth()->check() ? route('home') : route('welcome') }}" class="flex items-center space-x-2">
                <div class="p-1 rounded-3xl">
                    <img src="{{ asset('eternal.png') }}" alt="The Eternal Echo" class="w-8 h-8 object-contain">
                </div>
                <h1 class="text-xl font-bold tracking-tight hidden sm:block">The Eternal Echo</h1>
            </a>

            @php
                if (auth()->check()) {
                    $navItems = [
                        ['route' => 'home', 'label' => 'Home', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'themes.index', 'label' => 'Themes', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['route' => 'questions.index', 'label' => 'Bank', 'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => 'quiz.history', 'label' => 'History', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => 'stats', 'label' => 'Progress', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ['route' => 'leaderboard', 'label' => 'Rankings', 'icon' => 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'],
                        ['route' => 'profile', 'label' => 'Profile', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ];
                    if (auth()->user()->is_admin) {
                        $navItems[] = ['route' => 'admin.questions.index', 'label' => 'Admin', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'];
                    }
                } else {
                    $navItems = [
                        ['route' => 'welcome', 'label' => 'Welcome', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'themes.index', 'label' => 'Explore Themes', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                    ];
                }
            @endphp

            <nav class="hidden md:flex space-x-1">
                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all {{ request()->routeIs($item['route']) ? 'bg-white/20 text-white' : 'text-emerald-100 hover:text-white hover:bg-white/10' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center space-x-4">
                @auth
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
            <div
                class="flex items-center justify-center space-x-6 text-[10px] uppercase font-black tracking-widest text-slate-600">
                <a href="{{ route('privacy') }}" class="hover:text-emerald-500 transition-colors">Privacy Policy</a>
                <a href="{{ route('terms') }}" class="hover:text-emerald-500 transition-colors">Terms of Service</a>
            </div>
        </div>
    </footer>

    {{-- Mobile Nav --}}
    <div
        class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 flex justify-around p-2 z-50">
        @foreach($navItems as $item)
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

    @stack('scripts')
</body>

</html>