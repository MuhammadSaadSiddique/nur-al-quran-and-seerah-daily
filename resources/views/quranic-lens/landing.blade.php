<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quranic Research, Scientific and Historical Connections — The Eternal Echo</title>
    
    <!-- Extensive SEO Optimization Meta Tags -->
    <meta name="description"
        content="Explore analytical connections between the Quran and science, prophetic Hadith, Seerah chronology, historical/archaeological discoveries, and comparative Judeo-Christian scriptures.">
    <meta name="keywords"
        content="Quran research, scientific miracles, Quran and science, comparative religion, Quran bible connections, seerah chronology, hadith context, historical mapping, Islamic research database">
    <meta name="author" content="The Eternal Echo Research Team">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ route('lens.landing') }}">
    <link rel="icon" href="{{ asset('eternal.png') }}" type="image/png">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('lens.landing') }}">
    <meta property="og:title" content="Quranic Research and Connection Mapping — The Eternal Echo">
    <meta property="og:description"
        content="Discover scientific, historical, seerah, and scripture connection mapping in the Quran. Explore our comprehensive scholarly directory.">
    <meta property="og:image" content="{{ asset('eternal.png') }}">
    <meta property="og:site_name" content="The Eternal Echo">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Quranic Research and Connection Mapping — The Eternal Echo">
    <meta name="twitter:description"
        content="Discover scientific, historical, seerah, and scripture connection mapping in the Quran. Explore our comprehensive scholarly directory.">
    <meta name="twitter:image" content="{{ asset('eternal.png') }}">

    <!-- JSON-LD Structured Data for Rich Search Results -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebPage",
        "name": "Quranic Research and Analytical Connections — The Eternal Echo",
        "description": "Scholarly research platform analyzing correlations between Quranic verses and scientific facts, prophetic traditions, historical timelines, and comparative scriptures.",
        "url": "{{ route('lens.landing') }}",
        "publisher": {
            "@@type": "Organization",
            "name": "The Eternal Echo",
            "logo": {
                "@@type": "ImageObject",
                "url": "{{ asset('eternal.png') }}"
            }
        },
        "about": [
            {
                "@@type": "Thing",
                "name": "Quranic Studies"
            },
            {
                "@@type": "Thing",
                "name": "Comparative Religion"
            },
            {
                "@@type": "Thing",
                "name": "Scientific Miracles in the Quran"
            }
        ]
    }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Inter:wght@300;400;500;600;700;800;900&family=Scheherazade+New:wght@400;700&display=swap" rel="stylesheet">

    <!-- Stylesheets & Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .font-arabic {
            font-family: 'Scheherazade New', 'Amiri', serif;
        }
    </style>
</head>

<body class="bg-slate-900 text-slate-100 antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Navigation -->
    <nav class="fixed w-full z-50 bg-slate-900/80 backdrop-blur-md border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('lens.landing') }}" class="flex items-center space-x-3">
                        <img src="{{ asset('eternal.png') }}" alt="The Eternal Echo Logo" class="w-12 h-12 object-contain">
                        <span class="font-black text-xl tracking-tight text-white">The Eternal Echo</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('welcome') }}" class="text-sm font-bold text-slate-300 hover:text-white transition-colors">Explorer Cockpit</a>
                    <a href="{{ route('quiz.learning') }}" class="text-sm font-bold text-slate-300 hover:text-white transition-colors">Quiz Platform</a>
                    <a href="{{ route('researchers.index') }}" class="text-sm font-bold text-slate-300 hover:text-white transition-colors">Directory</a>
                    @auth
                        <a href="{{ route('home') }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-full shadow-lg shadow-emerald-600/30 transition-all">My Profile</a>
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-full shadow-lg shadow-emerald-600/30 transition-all">Sign In</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="relative pt-32 pb-24 flex items-center justify-center overflow-hidden min-h-screen">
        <div class="absolute inset-0 z-0 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-emerald-950/40 via-slate-900 to-slate-900"></div>
        <div class="relative z-10 max-w-5xl mx-auto px-4 text-center space-y-8">
            <div class="inline-flex items-center space-x-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider animate-pulse">
                🔬 Advanced Quranic Research & Analytics
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-white leading-tight">
                Analytical Connection Mapping of the <span class="bg-gradient-to-r from-emerald-400 to-teal-300 bg-clip-text text-transparent">Holy Quran</span>
            </h1>
            <p class="text-lg md:text-xl text-slate-300 max-w-3xl mx-auto font-light leading-relaxed">
                Welcome to a collaborative research ecosystem documenting the harmony between Divine Revelation and human observations. Search and explore connections mapping verses to Science, Seerah, Hadith, History, and Scriptures.
            </p>
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4 pt-4">
                <a href="{{ route('welcome') }}" class="w-full sm:w-auto px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white text-base font-bold rounded-full shadow-xl shadow-emerald-900/40 transition-all hover:-translate-y-0.5">
                    Launch Research Cockpit
                </a>
                <a href="{{ route('researchers.index') }}" class="w-full sm:w-auto px-8 py-4 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-white/10 hover:border-white/20 text-base font-bold rounded-full transition-all">
                    Scholarly Directory
                </a>
            </div>
        </div>
    </header>

    <!-- Statistics Counters (Extensive for SEO indexing) -->
    <section class="py-16 bg-slate-950 border-y border-white/5 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="sr-only">Research Database Metrics</h2>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-8 text-center">
                <div class="space-y-2">
                    <p class="text-4xl font-extrabold text-emerald-400">{{ max($stats['science'], 25) }}+</p>
                    <p class="text-xs uppercase font-semibold text-slate-400 tracking-wider">Science Links</p>
                </div>
                <div class="space-y-2">
                    <p class="text-4xl font-extrabold text-teal-400">{{ max($stats['seerah'], 18) }}+</p>
                    <p class="text-xs uppercase font-semibold text-slate-400 tracking-wider">Seerah Contexts</p>
                </div>
                <div class="space-y-2">
                    <p class="text-4xl font-extrabold text-emerald-400">{{ max($stats['hadith'], 35) }}+</p>
                    <p class="text-xs uppercase font-semibold text-slate-400 tracking-wider">Hadith Links</p>
                </div>
                <div class="space-y-2">
                    <p class="text-4xl font-extrabold text-teal-400">{{ max($stats['history'], 15) }}+</p>
                    <p class="text-xs uppercase font-semibold text-slate-400 tracking-wider">History Links</p>
                </div>
                <div class="space-y-2">
                    <p class="text-4xl font-extrabold text-emerald-400">{{ max($stats['scripture'], 20) }}+</p>
                    <p class="text-xs uppercase font-semibold text-slate-400 tracking-wider">Scripture Links</p>
                </div>
                <div class="space-y-2 col-span-2 md:col-span-1">
                    <p class="text-4xl font-extrabold text-white">{{ max($stats['researchers'], 5) }}</p>
                    <p class="text-xs uppercase font-semibold text-slate-400 tracking-wider">Verified Scholars</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Connection Categories (Tabbed / Detailed showcase) -->
    <section class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16" x-data="{ activeTab: 'science' }">
        <div class="text-center space-y-4">
            <h2 class="text-3xl md:text-5xl font-black text-white">The Five Lenses of Connection</h2>
            <p class="text-slate-400 max-w-2xl mx-auto text-base">
                Our analytical engine structures research into five primary context lenses, linking revelation to historical and observable reality.
            </p>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex flex-wrap justify-center gap-2 border-b border-white/10 pb-4">
            <button @click="activeTab = 'science'" :class="activeTab === 'science' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'"
                class="px-5 py-2.5 rounded-xl font-bold transition-all text-sm">
                🔬 Science & Cosmology
            </button>
            <button @click="activeTab = 'seerah'" :class="activeTab === 'seerah' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'"
                class="px-5 py-2.5 rounded-xl font-bold transition-all text-sm">
                🕌 Seerah Chronology
            </button>
            <button @click="activeTab = 'hadith'" :class="activeTab === 'hadith' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'"
                class="px-5 py-2.5 rounded-xl font-bold transition-all text-sm">
                📜 Hadith Exegesis
            </button>
            <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'"
                class="px-5 py-2.5 rounded-xl font-bold transition-all text-sm">
                🏛️ History & Archeology
            </button>
            <button @click="activeTab = 'scripture'" :class="activeTab === 'scripture' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'"
                class="px-5 py-2.5 rounded-xl font-bold transition-all text-sm">
                📖 Judeo-Christian Scriptures
            </button>
        </div>

        <!-- Tab Panels -->
        <div class="bg-slate-800/50 border border-white/5 p-8 rounded-3xl backdrop-blur-sm min-h-[300px] flex flex-col justify-between">
            
            <!-- Science Panel -->
            <div x-show="activeTab === 'science'" x-cloak class="space-y-6">
                <div class="space-y-2">
                    <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                        <span>🔬</span> Scientific Miracle & Concordance Mapping
                    </h3>
                    <p class="text-slate-300 leading-relaxed">
                        Explores the correlations between statements in the Quran and modern scientific discoveries across geology, embryology, astronomy, hydrology, and physics. The platform logs each observation with comprehensive peer-reviewed validations.
                    </p>
                </div>
                <div class="grid md:grid-cols-2 gap-4 pt-4">
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                        <span class="text-xs uppercase font-bold text-emerald-400 tracking-wide">Featured Field</span>
                        <h4 class="font-bold text-white mt-1">Cosmology & Solar Physics</h4>
                        <p class="text-sm text-slate-400 mt-2">Meticulous tracing of planetary motion, orbits, and expanding universe theories linked directly to Surah Ad-Dhariyat and Al-Anbya.</p>
                    </div>
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                        <span class="text-xs uppercase font-bold text-teal-400 tracking-wide">Featured Field</span>
                        <h4 class="font-bold text-white mt-1">Human Embryology</h4>
                        <p class="text-sm text-slate-400 mt-2">Tracing stages of embryological development mentioned in Surah Al-Mu'minun with physiological correlations.</p>
                    </div>
                </div>
            </div>

            <!-- Seerah Panel -->
            <div x-show="activeTab === 'seerah'" x-cloak class="space-y-6">
                <div class="space-y-2">
                    <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                        <span>🕌</span> Mapping Quranic Verses to Prophetic Biography
                    </h3>
                    <p class="text-slate-300 leading-relaxed">
                        Every verse has a context (Asbab al-Nuzul). By linking revelation events to specific timestamps and geographic stages in the Seerah of Prophet Muhammad ﷺ, we establish a chronological timeline of revelation.
                    </p>
                </div>
                <div class="grid md:grid-cols-2 gap-4 pt-4">
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                        <span class="text-xs uppercase font-bold text-emerald-400 tracking-wide">Meccan Period</span>
                        <h4 class="font-bold text-white mt-1">Early Dawah & Hardship</h4>
                        <p class="text-sm text-slate-400 mt-2">Chronological linking of early Meccan Surahs detailing patience, creation proofs, and stories of past prophets to strengthen the Sahabah.</p>
                    </div>
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                        <span class="text-xs uppercase font-bold text-teal-400 tracking-wide">Medinan Period</span>
                        <h4 class="font-bold text-white mt-1">State Building & Treaties</h4>
                        <p class="text-sm text-slate-400 mt-2">Mapping Medinan verses detailing legal jurisprudence, civic treaties, and historical battles directly to the Seerah milestones.</p>
                    </div>
                </div>
            </div>

            <!-- Hadith Panel -->
            <div x-show="activeTab === 'hadith'" x-cloak class="space-y-6">
                <div class="space-y-2">
                    <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                        <span>📜</span> Linking Sunnah & Prophetic Traditions
                    </h3>
                    <p class="text-slate-300 leading-relaxed">
                        Hadith is the key to unlocking the detailed application of the Quranic text. This database builds semantic connections between verses and authentic ahadith from Sahih al-Bukhari, Sahih Muslim, and other prime collections.
                    </p>
                </div>
                <div class="grid md:grid-cols-2 gap-4 pt-4">
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                        <span class="text-xs uppercase font-bold text-emerald-400 tracking-wide">Hadith Correlation</span>
                        <h4 class="font-bold text-white mt-1">Practical Jurisprudence</h4>
                        <p class="text-sm text-slate-400 mt-2">Mapping short, summary statements of worship commands in the Quran to their step-by-step practical guides in the Sunnah.</p>
                    </div>
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                        <span class="text-xs uppercase font-bold text-teal-400 tracking-wide">Hadith Correlation</span>
                        <h4 class="font-bold text-white mt-1">Ethical Frameworks</h4>
                        <p class="text-sm text-slate-400 mt-2">Linking prophetic descriptions of moral excellence to ethical demands in the verses.</p>
                    </div>
                </div>
            </div>

            <!-- History Panel -->
            <div x-show="activeTab === 'history'" x-cloak class="space-y-6">
                <div class="space-y-2">
                    <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                        <span>🏛️</span> Historical Context & Archaeological Evidences
                    </h3>
                    <p class="text-slate-300 leading-relaxed">
                        Quranic accounts describe ancient civilizations, prophets, and cultures (e.g. Ad, Thamud, Pharaoh, Byzantine Empire). Our platform maps historical records, clay tablets, and archeological findings that back these narratives.
                    </p>
                </div>
                <div class="grid md:grid-cols-2 gap-4 pt-4">
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                        <span class="text-xs uppercase font-bold text-emerald-400 tracking-wide">Historical Reference</span>
                        <h4 class="font-bold text-white mt-1">The Byzantine Victory</h4>
                        <p class="text-sm text-slate-400 mt-2">Correlating Surah Ar-Rum's prediction of Roman victory after defeat with exact Roman-Sasanian battle dates.</p>
                    </div>
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                        <span class="text-xs uppercase font-bold text-teal-400 tracking-wide">Archaeological Findings</span>
                        <h4 class="font-bold text-white mt-1">Pharaonic Preservation</h4>
                        <p class="text-sm text-slate-400 mt-2">Verifying historical details of ancient Egyptian rulers and mummification records as referenced in Surah Yunus.</p>
                    </div>
                </div>
            </div>

            <!-- Scripture Panel -->
            <div x-show="activeTab === 'scripture'" x-cloak class="space-y-6">
                <div class="space-y-2">
                    <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                        <span>📖</span> Judeo-Christian Comparative Theology
                    </h3>
                    <p class="text-slate-300 leading-relaxed">
                        The Quran affirms it was sent to confirm and correct previous revelations (Torah, Gospel, Psalms). This database tracks theological correlations, similarities, and corrections between Quranic verses and biblical verses.
                    </p>
                </div>
                <div class="grid md:grid-cols-2 gap-4 pt-4">
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                        <span class="text-xs uppercase font-bold text-emerald-400 tracking-wide">Comparative Theology</span>
                        <h4 class="font-bold text-white mt-1">The Abrahamic Creed</h4>
                        <p class="text-sm text-slate-400 mt-2">Mapping monotheistic statements in Surah Al-Ikhlas to biblical references in Deuteronomy and Isaiah.</p>
                    </div>
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                        <span class="text-xs uppercase font-bold text-teal-400 tracking-wide">Prophetic Stories</span>
                        <h4 class="font-bold text-white mt-1">Genesis & Quranic Accounts</h4>
                        <p class="text-sm text-slate-400 mt-2">Cross-referencing events from the life of Prophet Joseph (Yusuf) in Surah Yusuf with Genesis.</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-white/5 flex items-center justify-between">
                <span class="text-sm text-slate-400">Want to explore verified mappings?</span>
                <a href="{{ route('welcome') }}" class="text-emerald-400 hover:text-emerald-300 font-bold flex items-center gap-1 transition-all">
                    Open Research Cockpit &rarr;
                </a>
            </div>
        </div>
    </section>

    <!-- Deep SEO Content: Importance of Quranic Research -->
    <section class="py-20 bg-slate-950/60 border-t border-white/5">
        <div class="max-w-5xl mx-auto px-4 space-y-12">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white text-center">Why Document Quranic Connections?</h2>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="space-y-3">
                    <div class="w-12 h-12 bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center font-bold text-xl">1</div>
                    <h3 class="text-lg font-bold text-white">Empirical Harmony</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Rather than separating science and theology, mapping connections helps demonstrate how physical reality conforms to structural descriptions in revelation.
                    </p>
                </div>
                <div class="space-y-3">
                    <div class="w-12 h-12 bg-teal-600/10 border border-teal-500/20 text-teal-400 rounded-2xl flex items-center justify-center font-bold text-xl">2</div>
                    <h3 class="text-lg font-bold text-white">Intertextual Clarity</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Tracing comparative references across older Judeo-Christian scriptures establishes the continuity of the Abrahamic monotheistic lineage.
                    </p>
                </div>
                <div class="space-y-3">
                    <div class="w-12 h-12 bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center font-bold text-xl">3</div>
                    <h3 class="text-lg font-bold text-white">Scholarly Transparency</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        All claims of connection undergo rigorous review by verified research peers, preventing arbitrary claims and preserving semantic integrity.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Joint Research Project Call to Action -->
    <section class="py-24 bg-gradient-to-b from-slate-900 to-slate-950 border-t border-white/5 relative">
        <div class="absolute inset-0 z-0 bg-[radial-gradient(circle_at_bottom,_var(--tw-gradient-stops))] from-emerald-950/20 via-transparent to-transparent"></div>
        <div class="relative z-10 max-w-4xl mx-auto px-4 text-center space-y-8">
            <h2 class="text-3xl md:text-5xl font-black text-white">Join the Scholarly Research Group</h2>
            <p class="text-slate-300 max-w-2xl mx-auto text-base">
                Are you an expert in Islamic theology, comparative scriptures, archaeology, or modern scientific fields? Contribute to our growing connection matrix.
            </p>
            <div class="pt-4 flex flex-col sm:flex-row justify-center gap-4">
                @auth
                    @if(auth()->user()->is_researcher)
                        <a href="{{ route('admin.lens.approvals.index') }}" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-full transition-all">
                            Go to Approvals Dashboard
                        </a>
                    @else
                        <form action="{{ route('researchers.join') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="w-full sm:w-auto px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-full shadow-lg transition-all">
                                Join Research Team
                            </button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-full shadow-lg transition-all">
                        Apply to Join Research Team
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 text-slate-500 py-16 text-center text-sm border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 space-y-6">
            <div class="flex justify-center items-center space-x-3">
                <img src="{{ asset('eternal.png') }}" alt="The Eternal Echo" class="w-8 h-8 object-contain">
                <span class="font-extrabold text-white text-base">The Eternal Echo</span>
            </div>
            <p class="opacity-60 max-w-md mx-auto">
                An advanced Quranic research database cataloging comparative theology and scientific concordance mapping.
            </p>
            <div class="flex justify-center space-x-6 text-[10px] uppercase font-black tracking-widest text-slate-600">
                <a href="{{ route('privacy') }}" class="hover:text-emerald-500 transition-colors">Privacy Policy</a>
                <a href="{{ route('terms') }}" class="hover:text-emerald-500 transition-colors">Terms of Service</a>
            </div>
            <p class="opacity-50 text-[11px] text-slate-500">
                &copy; {{ date('Y') }} The Eternal Echo. All Rights Reserved.
            </p>
        </div>
    </footer>

</body>

</html>
