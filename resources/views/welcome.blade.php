<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Eternal Echo — AI-Powered Islamic Learning Platform</title>
    <meta name="description"
        content="Embark on an interactive journey through the 30 Paras of the Quran and the beautiful Seerah of Prophet Muhammad ﷺ. AI-powered personalized quizzes, knowledge tracking, and spiritual growth.">
    <meta name="keywords"
        content="Quran quiz, Islamic learning, Seerah, Prophet Muhammad, AI education, Islamic knowledge, Quran paras, Islamic quiz app, learn Quran online">
    <meta name="author" content="The Eternal Echo">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="icon" href="{{ asset('eternal.png') }}" type="image/png">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="The Eternal Echo — AI-Powered Islamic Learning">
    <meta property="og:description"
        content="Interactive Quran Para quizzes, Seerah knowledge challenges, and AI-powered personalized insights. Free Islamic learning platform.">
    <meta property="og:site_name" content="The Eternal Echo">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="The Eternal Echo — AI-Powered Islamic Learning">
    <meta name="twitter:description"
        content="Interactive Quran Para quizzes, Seerah knowledge challenges, and AI-powered personalized insights.">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebApplication",
        "name": "The Eternal Echo",
        "description": "AI-powered Islamic learning platform with personalized Quran quizzes, Seerah knowledge challenges, and spiritual growth tracking",
        "url": "{{ url('/') }}",
        "applicationCategory": "EducationalApplication",
        "operatingSystem": "Web",
        "offers": {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "educationalAlignment": {
            "@@type": "AlignmentObject",
            "educationalFramework": "Islamic Studies",
            "targetName": "Quran & Seerah Knowledge"
        }
    }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600;700;800;900&family=Scheherazade+New:wght@400;700&display=swap"
        rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
    </style>
</head>

<body class="bg-slate-900 text-white antialiased selection:bg-emerald-500 selection:text-white overflow-x-hidden">

    <!-- Navigation -->
    <nav class="fixed w-full z-50 transition-all duration-300 bg-slate-900/80 backdrop-blur-md border-b border-white/10"
        id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <a href="{{ route('lens.landing') }}" class="flex items-center space-x-3 hover:opacity-90 transition-opacity">
                    <div class="w-12 h-12 rounded-3xl flex items-center justify-center">
                        <img src="{{ asset('eternal.png') }}" alt="The Eternal Echo"
                            class="w-full h-full object-contain">
                    </div>
                    <span class="font-extrabold text-xl tracking-tight text-white">The Eternal Echo</span>
                </a>
                <div class="flex items-center space-x-6">
                    @auth
                        <a href="{{ route('home') }}"
                            class="text-sm font-bold text-slate-300 hover:text-white transition-colors">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-full shadow-lg shadow-emerald-600/30 transition-all hover:-translate-y-0.5">Get
                            Started</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center pt-20 overflow-hidden">
        <!-- Background Hero Image -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/hero_banner.png') }}" alt="Islamic Geometric Pattern"
                class="w-full h-full object-cover opacity-40">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-transparent to-transparent opacity-80">
            </div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center animate-slideUp">
            <div
                class="inline-flex items-center space-x-2 bg-white/10 backdrop-blur-md rounded-full px-4 py-1.5 mb-8 border border-white/10">
                <span class="flex w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-xs font-bold tracking-widest uppercase text-emerald-100">AI-Powered Islamic
                    Learning</span>
            </div>

            <h1 class="text-5xl md:text-7xl font-black text-white tracking-tight leading-tight mb-6">
                Illuminating the <br class="hidden md:block" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-200">Path of
                    Knowledge.</span>
            </h1>

            <p class="mt-4 max-w-2xl mx-auto text-lg md:text-xl text-slate-300 mb-10 leading-relaxed font-medium">
                Embark on an interactive journey through the 30 Paras of the Quran and the beautiful Seerah of Prophet
                Muhammad ﷺ, guided by advanced personalized AI insights.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-8 animate-slideUp"
                style="animation-delay: 0.4s">
                <a href="{{ route('login') }}"
                    class="w-full sm:w-auto px-10 py-5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-black text-lg transition-all shadow-2xl shadow-emerald-600/40 hover:-translate-y-1 flex items-center justify-center space-x-3">
                    <span>Start Your Journey</span>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <a href="{{ route('themes.index') }}"
                    class="w-full sm:w-auto px-10 py-5 bg-white/5 hover:bg-white/10 text-white border border-white/20 rounded-2xl font-black text-lg transition-all backdrop-blur-sm hover:-translate-y-1 flex items-center justify-center">
                    Explore Themes
                </a>
                <a href="{{ route('leaderboard') }}"
                    class="w-full sm:w-auto px-10 py-5 bg-white/5 hover:bg-white/10 text-white border border-white/20 rounded-2xl font-black text-lg transition-all backdrop-blur-sm hover:-translate-y-1 flex items-center justify-center">
                    Learners Rankings
                </a>
            </div>

            <!-- Floating Data points -->
            <div
                class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto text-center border-t border-white/10 pt-10">
                <div>
                    <div class="text-3xl font-black text-emerald-400">30</div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Quran Paras</div>
                </div>
                <div>
                    <div class="text-3xl font-black text-emerald-400">100+</div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Seerah Themes</div>
                </div>
                <div>
                    <div class="text-3xl font-black text-emerald-400">AI</div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Smart Generation</div>
                </div>
                <div>
                    <div class="text-3xl font-black text-emerald-400">24/7</div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Personal Access</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-slate-900 border-t border-white/5 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">Deepen Your Foundation</h2>
                <p class="text-slate-400 text-lg">Experience a revolutionary approach to Islamic education tailored
                    dynamically to your progress.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div
                    class="bg-gradient-to-b from-slate-800 to-slate-800/50 p-10 rounded-3xl border border-white/10 hover:border-emerald-500/50 transition-all duration-300 group hover:-translate-y-2">
                    <div
                        class="w-16 h-16 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-6 border border-emerald-500/20 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-3">Quranic Mastery</h3>
                    <p class="text-slate-400 leading-relaxed text-sm">Systematic chapter-by-chapter quizzes covering all
                        30 Paras. Track your mastery and unlock deep theological insights.</p>
                </div>

                <!-- Feature 2 -->
                <div
                    class="bg-gradient-to-b from-slate-800 to-slate-800/50 p-10 rounded-3xl border border-white/10 hover:border-teal-500/50 transition-all duration-300 group hover:-translate-y-2">
                    <div
                        class="w-16 h-16 bg-teal-500/10 rounded-2xl flex items-center justify-center mb-6 border border-teal-500/20 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-3">Seerah Timelines</h3>
                    <p class="text-slate-400 leading-relaxed text-sm">Journey through the chronological history of the
                        Prophet ﷺ. Test your historical knowledge and strengthen your spiritual bond.</p>
                </div>

                <!-- Feature 3 -->
                <div
                    class="bg-gradient-to-b from-slate-800 to-slate-800/50 p-10 rounded-3xl border border-white/10 hover:border-emerald-500/50 transition-all duration-300 group hover:-translate-y-2">
                    <div
                        class="w-16 h-16 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-6 border border-emerald-500/20 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-3">AI Engine</h3>
                    <p class="text-slate-400 leading-relaxed text-sm">Utilizing Google's Gemini models to generate
                        personalized, highly contextual explanations for every question you answer.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Future Roadmap Section -->
    <section id="roadmap" class="py-24 bg-slate-950 border-t border-white/5 relative z-10 overflow-hidden">
        <div class="absolute inset-0 opacity-5 pointer-events-none">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-emerald-500 rounded-full blur-[120px]"></div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <div class="inline-flex items-center space-x-2 bg-emerald-500/10 backdrop-blur-md rounded-full px-4 py-1.5 mb-4 border border-emerald-500/20">
                    <span class="text-xs font-bold tracking-widest uppercase text-emerald-400">Future Vision</span>
                </div>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">The Journey Ahead</h2>
                <p class="text-slate-400 text-lg">Our roadmap is designed to build the ultimate digital gateway for comprehensive Islamic learning and interactive scholarship.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Roadmap Card 1 -->
                <div class="bg-gradient-to-b from-slate-900 to-slate-900/40 p-8 rounded-3xl border border-white/10 hover:border-emerald-500/50 transition-all duration-300 group hover:-translate-y-2 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-bl-full group-hover:bg-emerald-500/10 transition-colors"></div>
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-6 border border-emerald-500/20 text-emerald-400 font-bold group-hover:scale-110 transition-transform">
                        👥
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Study Clubs & Halaqas</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Form collaborative study circles and learn alongside peers. Participate in group challenges, share notes, and climb the team leaderboards together.</p>
                </div>

                <!-- Roadmap Card 2 -->
                <div class="bg-gradient-to-b from-slate-900 to-slate-900/40 p-8 rounded-3xl border border-white/10 hover:border-teal-500/50 transition-all duration-300 group hover:-translate-y-2 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-teal-500/5 rounded-bl-full group-hover:bg-teal-500/10 transition-colors"></div>
                    <div class="w-12 h-12 bg-teal-500/10 rounded-2xl flex items-center justify-center mb-6 border border-teal-500/20 text-teal-400 font-bold group-hover:scale-110 transition-transform">
                        📚
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Scholarly Literature</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Dynamically generate interactive tests directly from famous works like Hadith collections, *Ar-Raheeq Al-Makhtum*, and commentaries of classic scholars.</p>
                </div>

                <!-- Roadmap Card 3 -->
                <div class="bg-gradient-to-b from-slate-900 to-slate-900/40 p-8 rounded-3xl border border-white/10 hover:border-emerald-500/50 transition-all duration-300 group hover:-translate-y-2 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-bl-full group-hover:bg-emerald-500/10 transition-colors"></div>
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-6 border border-emerald-500/20 text-emerald-400 font-bold group-hover:scale-110 transition-transform">
                        🎓
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Literature of Every Scholar</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Explore structured learning paths featuring custom quizzes derived from the comprehensive books, works, and fatwas of historical and modern scholars.</p>
                </div>

                <!-- Roadmap Card 4 -->
                <div class="bg-gradient-to-b from-slate-900 to-slate-900/40 p-8 rounded-3xl border border-white/10 hover:border-teal-500/50 transition-all duration-300 group hover:-translate-y-2 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-teal-500/5 rounded-bl-full group-hover:bg-teal-500/10 transition-colors"></div>
                    <div class="w-12 h-12 bg-teal-500/10 rounded-2xl flex items-center justify-center mb-6 border border-teal-500/20 text-teal-400 font-bold group-hover:scale-110 transition-transform">
                        🤖
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">AI Virtual Tutors</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Interact with specialized AI study companions that answer your scriptural and historical questions, identify learning gaps, and guide your daily reading plans.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    @if($testimonials->count() > 0)
        <section id="testimonials" class="py-24 bg-slate-900 border-t border-white/5 relative z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl md:text-5xl font-black text-white mb-4">Voices of Enlightenment</h2>
                    <p class="text-slate-400 text-lg">Join thousands of students deepening their spiritual foundation.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach($testimonials as $testimonial)
                        <div
                            class="bg-gradient-to-br from-slate-800 to-slate-900 p-8 rounded-3xl border border-white/10 relative group">
                            <div
                                class="absolute -top-4 -left-4 w-12 h-12 bg-emerald-500 rounded-2xl flex items-center justify-center text-white shadow-xl rotate-12 group-hover:rotate-0 transition-transform duration-300">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C20.1216 16 21.017 16.8954 21.017 18V21C21.017 22.1046 20.1216 23 19.017 23H14.017C12.9124 23 12.017 22.1046 12.017 21ZM14.017 21V10C14.017 8.89543 14.9124 8 16.017 8H21.017V21M5.011 21L5.011 18C5.011 16.8954 5.9064 16 7.011 16H10.011C11.1156 16 12.011 16.8954 12.011 18V21C12.011 22.1046 11.1156 23 10.011 23H5.011C3.9064 23 3.011 22.1046 3.011 21ZM5.011 21V10C5.011 8.89543 5.9064 8 7.011 8H12.011V21" />
                                </svg>
                            </div>
                            <div class="space-y-4">
                                <p class="text-slate-300 italic leading-relaxed text-lg">"{{ $testimonial->feedback }}"</p>
                                <div class="flex items-center space-x-3 pt-4 border-t border-white/5">
                                    <div
                                        class="w-10 h-10 bg-emerald-500/20 rounded-full flex items-center justify-center text-emerald-400 font-black">
                                        {{ substr($testimonial->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="text-white font-bold">{{ $testimonial->name }}</h4>
                                        <p class="text-slate-500 text-xs font-black uppercase tracking-widest">Spiritual Learner
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Bottom CTA -->
    <section class="py-24 bg-gradient-to-b from-slate-900 to-black relative z-10">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="font-arabic text-5xl md:text-6xl text-emerald-400 mb-6 font-bold tracking-wider">بِسْمِ اللَّهِ
                الرَّحْمَنِ الرَّحِيم</h2>
            <h3 class="text-2xl md:text-4xl font-black text-white mb-8">Begin Your Free Journey Today</h3>
            <p class="text-slate-400 mb-10 text-lg">Join the growing community of spiritual learners. No credit card
                required.</p>
            <a href="{{ route('login') }}"
                class="inline-flex px-10 py-5 bg-white text-slate-900 font-black rounded-full shadow-2xl hover:bg-slate-200 transition-all transform hover:scale-105 items-center space-x-3 text-lg">
                <span>Enter The Platform</span>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black py-12 border-t border-white/5 relative z-10">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-slate-600 font-bold text-sm tracking-widest uppercase">&copy; {{ date('Y') }} The Eternal
                Echo. <a href="https://asloobulhayat.com/">Asloob ul Hayat Project.</a></p>
        </div>
    </footer>

</body>

</html>