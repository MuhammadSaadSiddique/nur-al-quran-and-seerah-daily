@php
    $playStoreUrl = 'https://play.google.com/store/apps/details?id=com.asloobulhayat.eternalecho';
    $projectUrl = 'https://asloobulhayat.com';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eternal Echo | Quranic Research, Seerah & Reflection</title>

    <meta name="description"
        content="Explore analytical connections between the Quran and science, prophetic Hadith, Seerah chronology, authentic duas, and para quizzes. Eternal Echo by Asloob ul Hayat, free on Google Play and the web.">
    <meta name="keywords"
        content="Quran quiz, Seerah, Eternal Echo, Asloob ul Hayat, Quran research, scientific miracles, duas, hadith, Islamic learning app">
    <meta name="author" content="The Eternal Echo Research Team">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ route('lens.landing') }}">
    <link rel="icon" href="{{ asset('eternal.png') }}" type="image/png">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('lens.landing') }}">
    <meta property="og:title" content="Eternal Echo | Quran, Seerah & Islamic Reflection Companion">
    <meta property="og:description"
        content="Deepen Quranic memory and Seerah reflection. Quizzes across 30 paras, chronological Seerah, duas, and Quranic Lens research. By Asloob ul Hayat.">
    <meta property="og:image" content="{{ asset('eternal.png') }}">
    <meta property="og:site_name" content="The Eternal Echo">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Eternal Echo | Quran, Seerah & Islamic Reflection Companion">
    <meta name="twitter:description"
        content="Para quizzes, Seerah timelines, duas, and Quranic Lens research. Free on Google Play and the web.">
    <meta name="twitter:image" content="{{ asset('eternal.png') }}">

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebPage",
        "name": "Eternal Echo | Quran, Seerah & Islamic Reflection Companion",
        "description": "Quran quizzes, Seerah timelines, authentic duas, and scholarly connection mapping.",
        "url": "{{ route('lens.landing') }}",
        "publisher": {
            "@@type": "Organization",
            "name": "Asloob ul Hayat",
            "logo": {
                "@@type": "ImageObject",
                "url": "{{ asset('eternal.png') }}"
            }
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "MobileApplication",
        "name": "The Eternal Echo",
        "operatingSystem": "ANDROID",
        "applicationCategory": "EducationalApplication",
        "offers": {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "installUrl": "{{ $playStoreUrl }}",
        "url": "{{ $playStoreUrl }}"
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cinzel:wght@600;700&family=Inter:wght@300;400;500;600;700;800&family=Scheherazade+New:wght@400;700&display=swap" rel="stylesheet">

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
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        .islamic-pattern {
            background-image: radial-gradient(#d4af37 0.75px, transparent 0.75px), radial-gradient(#d4af37 0.75px, #071913 0.75px);
            background-size: 30px 30px;
            background-position: 0 0, 15px 15px;
            opacity: 0.06;
        }

        @keyframes glow-pulse {
            0%, 100% { opacity: 0.35; transform: scale(1); }
            50% { opacity: 0.65; transform: scale(1.05); }
        }
        .animate-glow {
            animation: glow-pulse 7s ease-in-out infinite;
        }

        .glass-panel {
            background: rgba(13, 43, 35, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(212, 175, 55, 0.18);
        }

        .glass-card-hover {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .glass-card-hover:hover {
            border-color: rgba(212, 175, 55, 0.45);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.6), 0 0 20px 0 rgba(212, 175, 55, 0.12);
        }

        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-emerald-950 text-slate-100 font-sans selection:bg-gold-500 selection:text-emerald-950 antialiased relative overflow-x-hidden min-h-screen">

    <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
        <div class="islamic-pattern absolute inset-0"></div>
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[700px] bg-gold-500/10 rounded-full blur-[140px] animate-glow"></div>
        <div class="absolute top-[40%] -left-48 w-96 h-96 bg-emerald-700/20 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-10 -right-48 w-96 h-96 bg-gold-600/10 rounded-full blur-[120px]"></div>
    </div>

    <header class="sticky top-0 z-50 backdrop-blur-md bg-emerald-950/80 border-b border-emerald-800/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            <a href="{{ route('lens.landing') }}" class="flex items-center gap-3 group min-w-0">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 p-[1px] shadow-lg shadow-gold-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
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
                <a href="#features" class="hover:text-gold-400 transition-colors">Features</a>
                <a href="{{ route('welcome') }}" class="hover:text-gold-400 transition-colors">Quran</a>
                <a href="{{ route('duas.index') }}" class="hover:text-gold-400 transition-colors">Duas</a>
                <a href="{{ route('quiz.learning') }}" class="hover:text-gold-400 transition-colors">Quizzes</a>
                <a href="#links" class="hover:text-gold-400 transition-colors">Get the App</a>
            </nav>

            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <div class="relative lg:hidden" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="p-2 text-slate-300 hover:text-gold-400" aria-label="Open menu">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                        class="absolute right-0 mt-2 w-48 rounded-2xl border border-gold-500/20 bg-emerald-950 py-2 shadow-2xl">
                        <a href="#features" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Features</a>
                        <a href="{{ route('welcome') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Quran</a>
                        <a href="{{ route('duas.index') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Duas</a>
                        <a href="{{ route('quiz.learning') }}" class="block px-4 py-2.5 text-sm text-slate-200 hover:bg-white/5">Quizzes</a>
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
                    <i data-lucide="play" class="w-4 h-4 fill-emerald-950"></i>
                    <span class="hidden sm:inline">Get on</span> Play Store
                </a>
            </div>
        </div>
    </header>

    <main>
        <section id="interactive-preview" class="relative pt-12 pb-20 lg:pt-20 lg:pb-28 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-gold-500/30 bg-gold-500/10 text-gold-400 text-xs font-medium tracking-wide">
                        <span class="w-2 h-2 rounded-full bg-gold-400 animate-ping"></span>
                        <span>Now live on Google Play</span>
                        <span class="text-gold-500/50">•</span>
                        <span class="text-slate-300">Ad-free & respectful</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-white leading-[1.15]">
                        Deepen Your Quranic Memory & <span class="text-gold-400">Seerah Reflection.</span>
                    </h1>

                    <div class="py-1">
                        <p class="font-quran text-2xl sm:text-3xl text-gold-300/90 leading-relaxed" dir="rtl">
                            أَفَلَا يَتَدَبَّرُونَ الْقُرْآنَ
                        </p>
                        <p class="text-xs text-slate-400 font-light italic mt-0.5">
                            "Do they not then reflect upon the Quran?" (Surah An-Nisa 4:82)
                        </p>
                    </div>

                    <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-light">
                        A companion from <span class="text-gold-400 font-medium">Asloob ul Hayat</span> for reading, review, and research.
                        Walk all <span class="text-gold-400 font-medium">30 paras</span>, follow the
                        <span class="text-gold-400 font-medium">chronological Seerah</span>, keep authentic duas, and open the
                        <span class="text-gold-400 font-medium">Quranic Lens</span> where verses meet Hadith, history, and science.
                    </p>

                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer"
                            class="w-full sm:w-auto flex items-center justify-center gap-3 px-6 py-3.5 rounded-xl bg-slate-900 border border-gold-500/40 hover:border-gold-400 shadow-xl shadow-black/40 transition-all group">
                            <svg class="w-7 h-7 text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.9 20.18,13.18L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z"/>
                            </svg>
                            <div class="text-left">
                                <p class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Get it on</p>
                                <p class="text-base font-bold text-white group-hover:text-gold-400 transition-colors -mt-0.5">Google Play</p>
                            </div>
                        </a>

                        <a href="{{ route('welcome') }}"
                            class="w-full sm:w-auto flex items-center justify-center gap-3 px-6 py-3.5 rounded-xl glass-panel hover:bg-emerald-800/40 text-slate-200 hover:text-white font-medium text-sm transition-all">
                            <i data-lucide="book-open" class="w-5 h-5 text-gold-400"></i>
                            <span>Read on the Web</span>
                        </a>

                        <button type="button" onclick="toggleQRModal()"
                            class="p-3.5 rounded-xl glass-panel hover:bg-emerald-800/40 text-gold-400 border border-emerald-700/50 transition-colors"
                            title="Scan QR code to download" aria-label="Show download QR code">
                            <i data-lucide="qr-code" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="pt-4 grid grid-cols-3 gap-3 border-t border-emerald-800/40 max-w-xl mx-auto lg:mx-0">
                        <div class="text-left">
                            <div class="text-gold-400 font-bold text-lg">30 Paras</div>
                            <div class="text-xs text-slate-400">Quiz coverage</div>
                        </div>
                        <div class="text-left">
                            <div class="text-gold-400 font-bold text-lg">Ad-free</div>
                            <div class="text-xs text-slate-400">On the web and app</div>
                        </div>
                        <div class="text-left">
                            <div class="text-gold-400 font-bold text-lg">Quranic Lens</div>
                            <div class="text-xs text-slate-400">Verse connections</div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 flex justify-center">
                    <div class="relative w-full max-w-[340px]">
                        <div class="absolute -inset-1.5 bg-gradient-to-tr from-gold-500/30 to-emerald-600/30 rounded-[44px] blur-xl opacity-75"></div>
                        <div class="relative rounded-[40px] border-4 border-emerald-700/60 bg-emerald-950 shadow-2xl overflow-hidden p-2">
                            <div class="h-5 w-32 bg-emerald-900 mx-auto rounded-b-xl flex items-center justify-center gap-2 mb-2">
                                <div class="w-8 h-1 rounded-full bg-emerald-800"></div>
                                <div class="w-2 h-2 rounded-full bg-emerald-800"></div>
                            </div>
                            <div class="bg-emerald-900/90 rounded-[30px] p-4 text-slate-100 space-y-4 border border-emerald-800/50">
                                <div class="flex items-center justify-between border-b border-emerald-800/60 pb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                        <span class="text-[10px] font-semibold tracking-wider text-gold-400">PARA 1 • ALIF LAM MEEM</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-gold-500/20 text-gold-300 font-mono">Q 04/10</span>
                                </div>
                                <div class="bg-emerald-950/70 p-3.5 rounded-xl border border-gold-500/20 text-center space-y-1">
                                    <div class="text-gold-400 text-xs font-medium">Tadabbur & recall</div>
                                    <p class="font-quran text-lg text-slate-100 leading-relaxed" dir="rtl">إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ</p>
                                    <p class="text-[11px] text-slate-400">Surah Al-Fatihah (1:5)</p>
                                </div>
                                <p class="text-xs font-medium text-slate-200">
                                    What principle is established by placing worship before seeking help?
                                </p>
                                <div class="space-y-2 text-xs" id="mockup-quiz-options">
                                    <button type="button" onclick="handleMockupAnswer(this, false)" class="w-full text-left p-2.5 rounded-lg border border-emerald-700/60 bg-emerald-850/60 hover:bg-emerald-800/70 transition-all">
                                        A) Worldly means come before worship
                                    </button>
                                    <button type="button" onclick="handleMockupAnswer(this, true)" class="w-full text-left p-2.5 rounded-lg border border-gold-500/40 bg-emerald-850/60 hover:bg-emerald-800/70 transition-all">
                                        B) Devotion to Allah comes before asking His aid
                                    </button>
                                    <button type="button" onclick="handleMockupAnswer(this, false)" class="w-full text-left p-2.5 rounded-lg border border-emerald-700/60 bg-emerald-850/60 hover:bg-emerald-800/70 transition-all">
                                        C) Dua is only valid after charity
                                    </button>
                                </div>
                                <div class="p-2.5 rounded-lg bg-emerald-950/80 border border-gold-500/30 text-[10px] space-y-1">
                                    <div class="flex items-center gap-1.5 text-gold-400 font-semibold">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                        <span>Context</span>
                                    </div>
                                    <p class="text-slate-300">Worship is the aim. Asking Allah for help is the way that aim is reached.</p>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-emerald-800/50">
                                    <span class="flex items-center gap-1 text-gold-400">
                                        <i data-lucide="flame" class="w-3.5 h-3.5"></i> Daily streak
                                    </span>
                                    <a href="{{ route('quiz.learning') }}" class="text-gold-400 hover:text-gold-300">Try a quiz</a>
                                </div>
                            </div>
                            <div class="w-24 h-1 bg-emerald-700/50 mx-auto rounded-full mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="py-16 bg-emerald-900/20 border-y border-emerald-800/40 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="text-xs uppercase font-mono tracking-widest text-gold-400">Asloob ul Hayat</span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mt-1">What Eternal Echo holds</h2>
                    <p class="text-slate-300 text-sm sm:text-base mt-2">Reading, review, and research in one place, on the web and in the Android app.</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <a href="{{ route('quiz.learning') }}" class="glass-panel p-6 rounded-2xl glass-card-hover space-y-3 block">
                        <div class="w-12 h-12 rounded-xl bg-gold-500/10 border border-gold-500/20 flex items-center justify-center text-gold-400">
                            <i data-lucide="book-open-check" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white">Para-by-para quizzes</h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">All 30 juz, with easier and harder questions for review and for those still learning.</p>
                        <div class="text-[11px] text-gold-400/80 font-mono pt-2">Open quizzes</div>
                    </a>
                    <a href="{{ route('seerah') }}" class="glass-panel p-6 rounded-2xl glass-card-hover space-y-3 block">
                        <div class="w-12 h-12 rounded-xl bg-gold-500/10 border border-gold-500/20 flex items-center justify-center text-gold-400">
                            <i data-lucide="history" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white">Seerah timelines</h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">The life of Prophet Muhammad ﷺ in sequence, from the early years through revelation and Madinah.</p>
                        <div class="text-[11px] text-gold-400/80 font-mono pt-2">Open Seerah</div>
                    </a>
                    <a href="{{ route('duas.index') }}" class="glass-panel p-6 rounded-2xl glass-card-hover space-y-3 block">
                        <div class="w-12 h-12 rounded-xl bg-gold-500/10 border border-gold-500/20 flex items-center justify-center text-gold-400">
                            <i data-lucide="sparkles" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white">Duas with meaning</h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">Authentic supplications with word-by-word meaning, translation, and Quran or Hadith references.</p>
                        <div class="text-[11px] text-gold-400/80 font-mono pt-2">Open duas</div>
                    </a>
                    <a href="{{ route('welcome') }}" class="glass-panel p-6 rounded-2xl glass-card-hover space-y-3 block">
                        <div class="w-12 h-12 rounded-xl bg-gold-500/10 border border-gold-500/20 flex items-center justify-center text-gold-400">
                            <i data-lucide="layers" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white">The Quranic Lens</h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">Verse connections to Hadith, Seerah, history, science, and earlier scripture, reviewed by researchers.</p>
                        <div class="text-[11px] text-gold-400/80 font-mono pt-2">Open research</div>
                    </a>
                </div>

                <div class="mt-10 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 text-center">
                    <div class="rounded-2xl border border-emerald-800/50 bg-emerald-950/40 py-4">
                        <p class="text-2xl font-bold text-gold-400">{{ max($stats['science'], 25) }}+</p>
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 mt-1">Science links</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-800/50 bg-emerald-950/40 py-4">
                        <p class="text-2xl font-bold text-gold-400">{{ max($stats['seerah'], 18) }}+</p>
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 mt-1">Seerah contexts</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-800/50 bg-emerald-950/40 py-4">
                        <p class="text-2xl font-bold text-gold-400">{{ max($stats['hadith'], 35) }}+</p>
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 mt-1">Hadith links</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-800/50 bg-emerald-950/40 py-4">
                        <p class="text-2xl font-bold text-gold-400">{{ max($stats['history'], 15) }}+</p>
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 mt-1">History links</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-800/50 bg-emerald-950/40 py-4">
                        <p class="text-2xl font-bold text-gold-400">{{ max($stats['scripture'], 20) }}+</p>
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 mt-1">Scripture links</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-800/50 bg-emerald-950/40 py-4">
                        <p class="text-2xl font-bold text-white">{{ max($stats['researchers'], 50) }}+</p>
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 mt-1">Researchers</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="links" class="py-16 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass-panel rounded-3xl p-6 sm:p-10 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-52 h-52 bg-gold-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="space-y-6 relative">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-mono uppercase text-gold-400 tracking-wider">Direct access</span>
                            <h2 class="text-2xl sm:text-3xl font-bold text-white">Install and share Eternal Echo</h2>
                        </div>
                        <div class="inline-flex items-center gap-2 self-start sm:self-auto px-3 py-1 rounded-full bg-emerald-800/80 border border-emerald-600/50 text-xs text-emerald-200">
                            <i data-lucide="shield-check" class="w-4 h-4 text-gold-400"></i> Google Play listing
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-medium text-slate-300 flex items-center gap-2">
                            <i data-lucide="play" class="w-3.5 h-3.5 text-gold-400"></i>
                            Google Play
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-emerald-950/90 border border-emerald-800/80 rounded-xl px-4 py-3 text-xs sm:text-sm font-mono text-slate-300 truncate">{{ $playStoreUrl }}</div>
                            <button type="button" onclick="copyToClipboard(playStoreUrl, 'copy-play-btn')" id="copy-play-btn"
                                class="px-4 py-3 rounded-xl bg-gold-500 hover:bg-gold-400 text-emerald-950 font-semibold text-xs sm:text-sm flex items-center gap-2">
                                <i data-lucide="copy" class="w-4 h-4"></i>
                                <span class="btn-text">Copy</span>
                            </button>
                            <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer"
                                class="px-4 py-3 rounded-xl glass-panel hover:bg-emerald-800/50 text-slate-200 text-xs sm:text-sm flex items-center gap-1.5">
                                <i data-lucide="external-link" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">Open</span>
                            </a>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-medium text-slate-300 flex items-center gap-2">
                            <i data-lucide="globe" class="w-3.5 h-3.5 text-gold-400"></i>
                            Asloob ul Hayat
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-emerald-950/90 border border-emerald-800/80 rounded-xl px-4 py-3 text-xs sm:text-sm font-mono text-slate-300 truncate">{{ $projectUrl }}</div>
                            <button type="button" onclick="copyToClipboard(projectUrl, 'copy-web-btn')" id="copy-web-btn"
                                class="px-4 py-3 rounded-xl glass-panel text-gold-400 font-semibold text-xs sm:text-sm flex items-center gap-2">
                                <i data-lucide="copy" class="w-4 h-4"></i>
                                <span class="btn-text">Copy</span>
                            </button>
                            <a href="{{ $projectUrl }}" target="_blank" rel="noopener noreferrer"
                                class="px-4 py-3 rounded-xl glass-panel hover:bg-emerald-800/50 text-slate-200 text-xs sm:text-sm flex items-center gap-1.5">
                                <i data-lucide="external-link" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">Visit</span>
                            </a>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-emerald-800/40 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                        <p>Android app on Google Play • The same library is open on the web</p>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="shareViaWebShare()" class="hover:text-gold-400 flex items-center gap-1">
                                <i data-lucide="share-2" class="w-3.5 h-3.5"></i> Share app
                            </button>
                            <span>•</span>
                            <button type="button" onclick="toggleQRModal()" class="hover:text-gold-400 flex items-center gap-1">
                                <i data-lucide="qr-code" class="w-3.5 h-3.5"></i> Show QR
                            </button>
                            <span>•</span>
                            <a href="{{ route('researchers.onboarding') }}" class="hover:text-gold-400">Join research</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div id="qr-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div class="glass-panel bg-emerald-950 border border-gold-500/40 rounded-2xl max-w-sm w-full p-6 text-center space-y-4 shadow-2xl relative">
            <button type="button" onclick="toggleQRModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white" aria-label="Close QR code">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <div class="w-10 h-10 rounded-full bg-gold-500/10 border border-gold-500/30 text-gold-400 flex items-center justify-center mx-auto">
                <i data-lucide="smartphone" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-white">Scan with your phone</h3>
                <p class="text-xs text-slate-400 mt-1">The camera opens Eternal Echo on Google Play.</p>
            </div>
            <div class="flex justify-center p-4 bg-white rounded-xl shadow-inner mx-auto w-fit">
                <div id="qrcode-target"></div>
            </div>
            <div class="text-[11px] font-mono text-gold-400/80">com.asloobulhayat.eternalecho</div>
        </div>
    </div>

    <div id="toast" class="fixed bottom-6 right-6 z-[70] bg-emerald-800 border border-gold-400/50 text-white px-4 py-2.5 rounded-xl shadow-2xl text-xs flex items-center gap-2 translate-y-16 opacity-0 transition-all duration-300 pointer-events-none">
        <i data-lucide="check" class="w-4 h-4 text-gold-400"></i>
        <span id="toast-message">Link copied.</span>
    </div>

    <footer class="border-t border-emerald-800/40 py-10 text-center text-xs text-slate-400 space-y-4">
        <div class="flex justify-center items-center gap-2">
            <img src="{{ asset('eternal.png') }}" alt="" class="w-7 h-7 object-contain">
            <span class="font-cinzel text-sm text-white tracking-wider">ETERNAL ECHO</span>
        </div>
        <p>© {{ date('Y') }} Asloob ul Hayat. Eternal Echo is for respectful Quran and Seerah study.</p>
        <div class="flex flex-wrap justify-center gap-4 sm:gap-6 uppercase tracking-widest text-[10px] font-bold">
            <a href="{{ route('donate') }}" class="text-gold-400 hover:text-gold-300">Sadaqah Jariyah</a>
            <a href="{{ route('privacy') }}" class="hover:text-gold-400">Privacy</a>
            <a href="{{ route('terms') }}" class="hover:text-gold-400">Terms</a>
            <a href="{{ route('account.delete') }}" class="hover:text-rose-300">Delete account</a>
            <a href="{{ $projectUrl }}" class="hover:text-gold-400" target="_blank" rel="noopener noreferrer">Asloob ul Hayat</a>
        </div>
    </footer>

    @include('partials.app-feedback-modal')

    <script>
        const playStoreUrl = @json($playStoreUrl);
        const projectUrl = @json($projectUrl);

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });

        let qrGenerated = false;
        function initQRCode() {
            if (qrGenerated || typeof QRCode === 'undefined') return;
            new QRCode(document.getElementById('qrcode-target'), {
                text: playStoreUrl,
                width: 160,
                height: 160,
                colorDark: '#061712',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
            qrGenerated = true;
        }

        function toggleQRModal() {
            const modal = document.getElementById('qr-modal');
            const opening = modal.classList.contains('hidden');
            modal.classList.toggle('hidden', !opening);
            modal.classList.toggle('flex', opening);
            if (opening) initQRCode();
        }

        function copyToClipboard(textToCopy, btnId) {
            const done = () => {
                showToast('Copied link.');
                const btn = document.getElementById(btnId);
                const textSpan = btn ? btn.querySelector('.btn-text') : null;
                if (!textSpan) return;
                const original = textSpan.innerText;
                textSpan.innerText = 'Copied';
                setTimeout(() => { textSpan.innerText = original; }, 2000);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textToCopy).then(done).catch(() => fallbackCopy(textToCopy, done));
                return;
            }
            fallbackCopy(textToCopy, done);
        }

        function fallbackCopy(textToCopy, done) {
            const tempInput = document.createElement('input');
            tempInput.value = textToCopy;
            document.body.appendChild(tempInput);
            tempInput.select();
            try {
                document.execCommand('copy');
                done();
            } catch (err) {
                showToast('Copy the link from the box.');
            }
            document.body.removeChild(tempInput);
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-message').innerText = msg;
            toast.classList.remove('translate-y-16', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(() => {
                toast.classList.add('translate-y-16', 'opacity-0');
                toast.classList.remove('translate-y-0', 'opacity-100');
            }, 3000);
        }

        function shareViaWebShare() {
            if (navigator.share) {
                navigator.share({
                    title: 'Eternal Echo: Quran & Seerah',
                    text: 'Quran quizzes, Seerah, duas, and Quranic Lens research from Asloob ul Hayat.',
                    url: playStoreUrl,
                }).catch(() => {});
            } else {
                copyToClipboard(playStoreUrl, 'copy-play-btn');
            }
        }

        function handleMockupAnswer(buttonElement, isCorrect) {
            const parent = document.getElementById('mockup-quiz-options');
            parent.querySelectorAll('button').forEach((btn) => {
                btn.classList.remove('border-emerald-400', 'bg-emerald-600/30', 'border-rose-500', 'bg-rose-900/30');
            });
            if (isCorrect) {
                buttonElement.classList.add('border-emerald-400', 'bg-emerald-600/30');
                showToast('Yes. Worship comes first, then help.');
            } else {
                buttonElement.classList.add('border-rose-500', 'bg-rose-900/30');
                showToast('Look at the order of the verse.');
            }
        }
    </script>
</body>
</html>
