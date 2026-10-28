@php
    $playStoreUrl = 'https://play.google.com/store/apps/details?id=com.asloobulhayat.eternalecho';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Eternal Echo — Quran & Seerah Quizzes</title>
    <meta name="description"
        content="Embark on an interactive journey through the 30 Paras of the Quran and the beautiful Seerah of Prophet Muhammad ﷺ. Personalized quizzes, knowledge tracking, and spiritual growth.">
    <meta name="keywords"
        content="Quran quiz, Islamic learning, Seerah, Prophet Muhammad, Islamic knowledge, Quran paras, Islamic quiz app, learn Quran online">
    <meta name="author" content="The Eternal Echo">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ route('quiz.learning') }}">
    <link rel="icon" href="{{ asset('eternal.png') }}" type="image/png">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('quiz.learning') }}">
    <meta property="og:title" content="The Eternal Echo — Quran & Seerah Quizzes">
    <meta property="og:description"
        content="Interactive Quran para quizzes and Seerah knowledge challenges. Free, from Asloob ul Hayat.">
    <meta property="og:image" content="{{ asset('eternal.png') }}">
    <meta property="og:site_name" content="The Eternal Echo">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="The Eternal Echo — Quran & Seerah Quizzes">
    <meta name="twitter:description"
        content="Interactive Quran para quizzes and Seerah knowledge challenges.">
    <meta name="twitter:image" content="{{ asset('eternal.png') }}">

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebApplication",
        "name": "The Eternal Echo",
        "description": "Quran para quizzes, Seerah challenges, and spiritual growth tracking",
        "url": "{{ route('quiz.learning') }}",
        "applicationCategory": "EducationalApplication",
        "operatingSystem": "Web",
        "offers": {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        }
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cinzel:wght@600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        quran: ['Amiri', 'serif'],
                        cinzel: ['Cinzel', 'serif']
                    },
                    colors: {
                        emerald: {
                            950: '#061712',
                            900: '#0a231b',
                            850: '#0d2b23',
                            800: '#11382e',
                            700: '#1b4d40',
                            600: '#236553'
                        },
                        gold: {
                            300: '#f6e58d',
                            400: '#e6ca65',
                            500: '#d4af37',
                            600: '#b89428',
                            700: '#997a1d'
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .islamic-pattern {
            background-image: radial-gradient(#d4af37 0.75px, transparent 0.75px), radial-gradient(#d4af37 0.75px, #071913 0.75px);
            background-size: 30px 30px;
            background-position: 0 0, 15px 15px;
            opacity: 0.06;
        }
        .glass-panel {
            background: rgba(13, 43, 35, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(212, 175, 55, 0.18);
        }
        .glass-card-hover { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .glass-card-hover:hover {
            border-color: rgba(212, 175, 55, 0.45);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.6);
        }
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-emerald-950 text-slate-100 font-sans selection:bg-gold-500 selection:text-emerald-950 antialiased overflow-x-hidden min-h-screen">

    <div class="fixed inset-0 pointer-events-none -z-10">
        <div class="islamic-pattern absolute inset-0"></div>
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[500px] bg-gold-500/10 rounded-full blur-[140px]"></div>
    </div>

    <header class="sticky top-0 z-50 backdrop-blur-md bg-emerald-950/80 border-b border-emerald-800/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            <a href="{{ route('lens.landing') }}" class="flex items-center gap-3 group min-w-0">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 p-[1px] shadow-lg shadow-gold-500/20 flex items-center justify-center shrink-0">
                    <div class="w-full h-full bg-emerald-900 rounded-[11px] flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('eternal.png') }}" alt="The Eternal Echo" class="w-8 h-8 object-contain">
                    </div>
                </div>
                <div class="min-w-0">
                    <span class="font-cinzel text-lg sm:text-xl font-bold tracking-wider text-slate-50 block truncate">ETERNAL ECHO</span>
                    <span class="text-[10px] sm:text-xs text-gold-400/80 tracking-widest font-mono block -mt-0.5">BY ASLOOB UL HAYAT</span>
                </div>
            </a>

            <nav class="hidden lg:flex items-center gap-7 text-sm font-medium text-slate-300">
                <a href="{{ route('lens.landing') }}" class="hover:text-gold-400 transition-colors">Home</a>
                <a href="#themes" class="text-gold-400">Quizzes</a>
                <a href="{{ route('welcome') }}" class="hover:text-gold-400 transition-colors">Quran</a>
                <a href="{{ route('duas.index') }}" class="hover:text-gold-400 transition-colors">Duas</a>
                <a href="{{ route('seerah') }}" class="hover:text-gold-400 transition-colors">Seerah</a>
            </nav>

            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <div class="relative lg:hidden" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="px-3 py-2 text-sm font-medium text-slate-200 hover:text-gold-400" aria-label="Open menu">Menu</button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                        class="absolute right-0 mt-2 w-48 rounded-2xl border border-gold-500/20 bg-emerald-950 py-2 shadow-2xl">
                        <a href="{{ route('lens.landing') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Home</a>
                        <a href="#themes" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Quizzes</a>
                        <a href="{{ route('welcome') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Quran</a>
                        <a href="{{ route('duas.index') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Duas</a>
                        <a href="{{ route('seerah') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Seerah</a>
                        <a href="{{ route('donate') }}" class="block px-4 py-2.5 text-sm text-gold-300 hover:bg-white/5">Support Us</a>
                    </div>
                </div>
                @auth
                    <a href="{{ route('profile') }}" class="hidden sm:inline text-sm font-medium text-slate-300 hover:text-gold-400">Profile</a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline text-sm font-medium text-slate-300 hover:text-gold-400">Sign In</a>
                @endauth
                <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-lg bg-gold-500 hover:bg-gold-400 text-emerald-950 font-semibold text-xs sm:text-sm shadow-md shadow-gold-500/20 transition-all">
                    <span class="hidden sm:inline">Get on</span> Play Store
                </a>
            </div>
        </div>
    </header>

    <main>
        <section class="relative pt-16 pb-20 lg:pt-24 lg:pb-28 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-gold-500/30 bg-gold-500/10 text-gold-400 text-xs font-medium tracking-wide">
                <span class="w-2 h-2 rounded-full bg-gold-400 animate-ping"></span>
                Quran paras and Seerah
            </div>

            <h1 class="mt-6 text-4xl sm:text-6xl font-bold tracking-tight text-white leading-[1.15]">
                Illuminating the <span class="text-gold-400">Path of Knowledge.</span>
            </h1>

            <p class="mt-6 max-w-2xl mx-auto text-lg text-slate-300 leading-relaxed font-light">
                Walk the 30 paras of the Quran and the Seerah of Prophet Muhammad ﷺ.
                Each quiz stays with the text, the history, and the meaning.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ auth()->check() ? route('home') : route('login') }}"
                    class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gold-500 hover:bg-gold-400 text-emerald-950 font-bold transition-all">
                    {{ auth()->check() ? 'Continue quizzes' : 'Start a quiz' }}
                </a>
                <a href="{{ route('themes.index') }}"
                    class="w-full sm:w-auto px-8 py-3.5 rounded-xl glass-panel hover:bg-emerald-800/40 text-slate-200 font-medium transition-all">
                    Browse themes
                </a>
                <a href="{{ route('leaderboard') }}"
                    class="w-full sm:w-auto px-8 py-3.5 rounded-xl glass-panel hover:bg-emerald-800/40 text-slate-200 font-medium transition-all">
                    Rankings
                </a>
            </div>

            <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto border-t border-emerald-800/40 pt-8">
                <div>
                    <div class="text-2xl font-bold text-gold-400">30</div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400 mt-1">Quran paras</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gold-400">{{ $seerahThemes->count() > 0 ? $seerahThemes->count() : '100+' }}</div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400 mt-1">Seerah themes</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gold-400">3</div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400 mt-1">Difficulty levels</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gold-400">Free</div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400 mt-1">Web and Android</div>
                </div>
            </div>
        </section>

        <section id="themes" class="py-16 border-y border-emerald-800/40 bg-emerald-900/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="text-center max-w-2xl mx-auto">
                    <span class="text-xs uppercase font-mono tracking-widest text-gold-400">Choose a set</span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mt-1">Quiz collections</h2>
                    <p class="text-slate-300 text-sm sm:text-base mt-2">Paras of the Quran and chapters of the Seerah. Open one to begin.</p>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-white mb-4">Quran paras</h3>
                    @if($quranThemes->isNotEmpty())
                        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            @foreach($quranThemes as $theme)
                                <a href="{{ route('themes.show', $theme) }}" class="glass-panel glass-card-hover rounded-2xl p-5 block">
                                    <p class="text-sm font-bold text-white">{{ $theme->name }}</p>
                                    <p class="text-xs text-slate-400 mt-2 line-clamp-2">{{ $theme->description ?: 'Questions drawn from this para.' }}</p>
                                    <p class="text-[11px] text-gold-400 mt-3">Open quiz</p>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-400">Para sets appear here as they are published. <a href="{{ route('login') }}" class="text-gold-400 hover:text-gold-300">Sign in</a> to start one from your dashboard.</p>
                    @endif
                </div>

                <div>
                    <h3 class="text-lg font-bold text-white mb-4">Seerah</h3>
                    @if($seerahThemes->isNotEmpty())
                        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            @foreach($seerahThemes as $theme)
                                <a href="{{ route('themes.show', $theme) }}" class="glass-panel glass-card-hover rounded-2xl p-5 block">
                                    <p class="text-sm font-bold text-white">{{ $theme->name }}</p>
                                    <p class="text-xs text-slate-400 mt-2 line-clamp-2">{{ $theme->description ?: 'Questions from this part of the Seerah.' }}</p>
                                    <p class="text-[11px] text-gold-400 mt-3">Open quiz</p>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-400">Seerah sets appear here as they are published. <a href="{{ route('seerah') }}" class="text-gold-400 hover:text-gold-300">Read the Seerah</a> in the meantime.</p>
                    @endif
                </div>
            </div>
        </section>

        <section id="features" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <h2 class="text-3xl sm:text-4xl font-bold text-white">How the quizzes work</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="glass-panel rounded-2xl p-6 space-y-3">
                    <h3 class="text-lg font-bold text-white">Quranic mastery</h3>
                    <p class="text-sm text-slate-300 leading-relaxed">Chapter-by-chapter questions across all 30 paras, from a first reading to a harder review.</p>
                </div>
                <div class="glass-panel rounded-2xl p-6 space-y-3">
                    <h3 class="text-lg font-bold text-white">Seerah timelines</h3>
                    <p class="text-sm text-slate-300 leading-relaxed">The life of the Prophet ﷺ in order, so the events stay attached to their time and place.</p>
                </div>
                <div class="glass-panel rounded-2xl p-6 space-y-3">
                    <h3 class="text-lg font-bold text-white">A short explanation</h3>
                    <p class="text-sm text-slate-300 leading-relaxed">After an answer, the note stays with the verse or the event, so the quiz is also a review.</p>
                </div>
            </div>
        </section>

        <section id="roadmap" class="py-16 border-t border-emerald-800/40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <span class="text-xs uppercase font-mono tracking-widest text-gold-400">Still ahead</span>
                    <h2 class="text-3xl font-bold text-white mt-1">The journey ahead</h2>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="glass-panel rounded-2xl p-6 space-y-2">
                        <h3 class="font-bold text-white">Study circles</h3>
                        <p class="text-sm text-slate-300">Shared reading and group challenges for a halaqah.</p>
                    </div>
                    <div class="glass-panel rounded-2xl p-6 space-y-2">
                        <h3 class="font-bold text-white">Scholarly literature</h3>
                        <p class="text-sm text-slate-300">Questions drawn from Hadith collections and works such as Ar-Raheeq Al-Makhtum.</p>
                    </div>
                    <div class="glass-panel rounded-2xl p-6 space-y-2">
                        <h3 class="font-bold text-white">Books of the scholars</h3>
                        <p class="text-sm text-slate-300">Paths built from the books and fatwas of named scholars.</p>
                    </div>
                    <div class="glass-panel rounded-2xl p-6 space-y-2">
                        <h3 class="font-bold text-white">Guided review</h3>
                        <p class="text-sm text-slate-300">A daily plan that returns to the paras and Seerah passages you missed.</p>
                    </div>
                </div>
            </div>
        </section>

        @if($testimonials->count() > 0)
            <section id="testimonials" class="py-16 border-t border-emerald-800/40 bg-emerald-900/20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-3xl font-bold text-white text-center mb-10">From learners</h2>
                    <div class="grid md:grid-cols-3 gap-6">
                        @foreach($testimonials as $testimonial)
                            <div class="glass-panel rounded-2xl p-6 space-y-4">
                                <p class="text-slate-200 leading-relaxed">"{{ $testimonial->feedback }}"</p>
                                <div class="flex items-center gap-3 pt-3 border-t border-emerald-800/50">
                                    <div class="w-10 h-10 rounded-full bg-gold-500/15 text-gold-400 flex items-center justify-center font-bold">
                                        {{ substr($testimonial->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h3 class="text-white font-bold text-sm">{{ $testimonial->name }}</h3>
                                        <p class="text-[10px] uppercase tracking-widest text-slate-500">Learner</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="py-20 text-center px-4">
            <p class="font-quran text-4xl sm:text-5xl text-gold-300">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p>
            <h2 class="text-2xl sm:text-4xl font-bold text-white mt-6">Begin when you are ready</h2>
            <p class="text-slate-400 mt-3">No payment. The same quizzes are on the web and on Android.</p>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ auth()->check() ? route('home') : route('login') }}"
                    class="px-8 py-3.5 rounded-xl bg-gold-500 hover:bg-gold-400 text-emerald-950 font-bold">
                    Enter the quizzes
                </a>
                <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer"
                    class="px-8 py-3.5 rounded-xl glass-panel text-slate-200 font-medium">
                    Get it on Google Play
                </a>
            </div>
        </section>
    </main>

    <footer class="border-t border-emerald-800/40 py-10 text-center text-xs text-slate-400 space-y-4">
        <div class="flex justify-center items-center gap-2">
            <img src="{{ asset('eternal.png') }}" alt="" class="w-7 h-7 object-contain">
            <span class="font-cinzel text-sm text-white tracking-wider">ETERNAL ECHO</span>
        </div>
        <p>© {{ date('Y') }} Asloob ul Hayat. <a href="https://asloobulhayat.com/" class="text-gold-400 hover:text-gold-300">Asloob ul Hayat</a></p>
        <div class="flex flex-wrap justify-center gap-4 sm:gap-6 uppercase tracking-widest text-[10px] font-bold">
            <a href="{{ route('donate') }}" class="text-gold-400 hover:text-gold-300">Sadaqah Jariyah</a>
            <a href="{{ route('privacy') }}" class="hover:text-gold-400">Privacy</a>
            <a href="{{ route('terms') }}" class="hover:text-gold-400">Terms</a>
            <a href="{{ route('account.delete') }}" class="hover:text-rose-300">Delete account</a>
        </div>
    </footer>

    @include('partials.app-feedback-modal')
</body>

</html>
