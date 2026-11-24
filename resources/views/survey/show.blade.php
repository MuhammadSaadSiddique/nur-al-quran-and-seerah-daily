<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#061712">
    <title>Help Shape Eternal Echo | 2-Minute Survey</title>
    <meta name="description" content="Tell us what you need from Eternal Echo, discover features you may have missed, and vote on what we build next. Takes about 2 minutes.">
    <meta name="robots" content="index, follow">
    <link rel="icon" href="{{ asset('eternal.png') }}" type="image/png">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('survey.show') }}">
    <meta property="og:title" content="Help shape Eternal Echo: a 2-minute survey">
    <meta property="og:description" content="Vote on what we build next and discover what's already inside.">
    <meta property="og:image" content="{{ asset('eternal.png') }}">

    {{-- Android: open the in-app survey when Eternal Echo is installed. A missing app falls back to this page with ?web=1, so the survey stays in the browser and does not loop. The custom scheme is intentional: an https App Link for /survey bounces back into older builds that do not route it. --}}
    <script>
        (function () {
            try {
                var params = new URLSearchParams(location.search);
                if (params.has('web')) return;
                var ua = navigator.userAgent || '';
                if (!/Android/i.test(ua)) return;
                if (/bot|crawl|spider|slurp|preview|googlebot|bingbot|baiduspider|yandex|duckduckbot|facebookexternalhit|twitterbot|linkedinbot|embedly|pinterest|slackbot|discordbot|telegrambot|applebot|petalbot/i.test(ua)) return;
                if (navigator.webdriver) return;
                try {
                    var saved = JSON.parse(localStorage.getItem(@json('ee-survey:' . \App\Services\RequirementSurvey::KEY)) || 'null');
                    if (saved && saved.screen && saved.screen !== 'welcome' && saved.screen !== 'done' && !saved.done) return;
                } catch (e) {}
                if (sessionStorage.getItem('ee-survey-app-tried') === '1') return;
                sessionStorage.setItem('ee-survey-app-tried', '1');
                var stay = new URL(location.href);
                stay.searchParams.set('web', '1');
                location.replace('intent://survey#Intent;scheme=eternalecho;package=com.asloobulhayat.eternalecho;S.browser_fallback_url=' + encodeURIComponent(stay.toString()) + ';end');
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cinzel:wght@600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], quran: ['Amiri', 'serif'], cinzel: ['Cinzel', 'serif'] },
                    colors: {
                        emerald: { 950: '#061712', 900: '#0a231b', 850: '#0d2b23', 800: '#11382e', 700: '#1b4d40', 600: '#236553' },
                        gold: { 300: '#f6e58d', 400: '#e6ca65', 500: '#d4af37', 600: '#b89428', 700: '#997a1d' }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        html, body { overscroll-behavior-y: none; }
        body { -webkit-tap-highlight-color: transparent; }

        .dots {
            background-image: radial-gradient(#d4af37 0.75px, transparent 0.75px), radial-gradient(#d4af37 0.75px, #071913 0.75px);
            background-size: 30px 30px;
            background-position: 0 0, 15px 15px;
            opacity: 0.05;
        }

        @keyframes in-fwd { from { opacity: 0; transform: translateX(32px); } to { opacity: 1; transform: none; } }
        @keyframes in-back { from { opacity: 0; transform: translateX(-32px); } to { opacity: 1; transform: none; } }
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes pop { 0% { transform: scale(1); } 40% { transform: scale(1.28) rotate(-6deg); } 100% { transform: scale(1); } }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 20%, 60% { transform: translateX(-6px); } 40%, 80% { transform: translateX(6px); } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes spin-slow { to { transform: rotate(360deg); } }
        @keyframes glow { 0%, 100% { opacity: .35; transform: scale(1); } 50% { opacity: .7; transform: scale(1.08); } }
        @keyframes toast-in { from { opacity: 0; transform: translate(-50%, -12px) scale(.96); } to { opacity: 1; transform: translate(-50%, 0) scale(1); } }
        @keyframes bump { 0% { transform: scale(1); } 35% { transform: scale(1.18); } 100% { transform: scale(1); } }
        @keyframes medal { 0% { opacity: 0; transform: scale(.4) rotate(-20deg); } 60% { opacity: 1; transform: scale(1.1) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
        @keyframes shimmer { from { background-position: -200% 0; } to { background-position: 200% 0; } }

        .anim-in { animation: in-fwd .5s cubic-bezier(.16, 1, .3, 1) both; }
        .anim-in-back { animation: in-back .5s cubic-bezier(.16, 1, .3, 1) both; }
        .rise { animation: rise .5s cubic-bezier(.16, 1, .3, 1) both; }
        .pop { animation: pop .38s ease; }
        .shake { animation: shake .4s ease; }
        .float { animation: float 5s ease-in-out infinite; }
        .spin-slow { animation: spin-slow 18s linear infinite; }
        .glow { animation: glow 6s ease-in-out infinite; }
        .toast { animation: toast-in .35s cubic-bezier(.16, 1, .3, 1) both; }
        .bump { animation: bump .45s ease; }
        .medal { animation: medal .9s cubic-bezier(.16, 1, .3, 1) both; }
        .shimmer {
            background: linear-gradient(90deg, #d4af37 0%, #f6e58d 25%, #d4af37 50%, #f6e58d 75%, #d4af37 100%);
            background-size: 200% 100%;
            animation: shimmer 6s linear infinite;
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }

        .deck-card { touch-action: none; user-select: none; -webkit-user-select: none; will-change: transform; }
        .nps-track { background: linear-gradient(90deg, #f43f5e, #f59e0b 50%, #10b981); }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
    </style>
</head>

<body class="bg-emerald-950 text-slate-100 font-sans antialiased overflow-x-hidden selection:bg-gold-500 selection:text-emerald-950">

    <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="dots absolute inset-0"></div>
        <div class="glow absolute -top-40 left-1/2 -translate-x-1/2 w-[620px] h-[620px] bg-gold-500/10 rounded-full blur-[130px]"></div>
        <div class="absolute top-1/2 -left-40 w-80 h-80 bg-emerald-600/25 rounded-full blur-[110px]"></div>
        <div class="absolute -bottom-20 -right-40 w-96 h-96 bg-gold-600/10 rounded-full blur-[120px]"></div>
    </div>

    <script>
        window.__SURVEY__ = {
            config: @json($config),
            user: @json($user),
            alreadyAnswered: @json($alreadyAnswered),
            source: @json($source),
            storeUrl: @json(route('survey.store')),
            homeUrl: @json(route('lens.landing')),
            shareUrl: @json(route('survey.show')),
            playUrl: @json(\App\Services\RequirementSurvey::PLAY_STORE_URL),
            logo: @json(asset('eternal.png')),
        };
    </script>

    @verbatim
    <div x-data="survey(window.__SURVEY__)" x-init="init()" x-cloak @keydown.window="onKey($event)"
        class="relative min-h-[100dvh] flex flex-col">

        <!-- Top bar: back, progress, XP -->
        <header class="sticky top-0 z-30 bg-emerald-950/75 backdrop-blur-md border-b border-white/5">
            <div class="max-w-2xl mx-auto px-4 h-14 flex items-center gap-3">
                <button type="button" x-show="canGoBack" @click="back()"
                    class="w-9 h-9 -ml-1 rounded-full flex items-center justify-center text-slate-300 hover:text-white hover:bg-white/10 transition"
                    aria-label="Previous question">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <a x-show="!canGoBack" :href="opts.homeUrl" class="flex items-center gap-2 shrink-0">
                    <img :src="opts.logo" alt="" class="w-7 h-7 object-contain">
                    <span class="font-cinzel font-bold tracking-wider text-sm text-slate-100 hidden sm:inline">ETERNAL ECHO</span>
                </a>

                <div class="flex-1 min-w-0">
                    <div x-show="inQuestions" class="flex gap-1" role="progressbar" :aria-valuenow="questionNumber" aria-valuemin="1" :aria-valuemax="totalQuestions" :aria-label="`Question ${questionNumber} of ${totalQuestions}`">
                        <template x-for="n in totalQuestions" :key="n">
                            <div class="h-1.5 flex-1 rounded-full overflow-hidden bg-white/10">
                                <div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-gold-300 transition-all duration-500 ease-out"
                                    :style="`width:${n < questionNumber ? 100 : (n === questionNumber ? 50 : 0)}%`"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <div class="flex items-center gap-1 px-2.5 py-1 rounded-full border border-gold-500/30 bg-gold-500/10 text-gold-300 text-xs font-black tabular-nums"
                        :class="xpBump ? 'bump' : ''" title="Points for each answer">
                        <span aria-hidden="true">✦</span><span x-text="xp"></span><span class="text-gold-400/70 font-bold">XP</span>
                    </div>
                    <a x-show="screen !== 'done'" :href="opts.homeUrl" class="w-9 h-9 -mr-1 rounded-full flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition"
                        aria-label="Save and exit" title="Save & exit, your answers are kept on this device">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </a>
                </div>
            </div>
            <div x-show="inQuestions" class="max-w-2xl mx-auto px-4 pb-2 -mt-1 flex justify-between text-[11px] font-semibold text-slate-400">
                <span x-text="`Question ${questionNumber} of ${totalQuestions}`"></span>
                <span x-text="timeLeft"></span>
            </div>
        </header>

        <!-- Toast -->
        <div aria-live="polite" class="fixed top-24 left-1/2 z-40 pointer-events-none">
            <template x-for="t in (toast ? [toast] : [])" :key="t.id">
                <div class="toast -translate-x-1/2 whitespace-nowrap flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-800/95 border border-gold-500/40 shadow-2xl shadow-black/40 text-sm font-bold">
                    <span x-text="t.text"></span>
                    <span x-show="t.xp" class="text-gold-300" x-text="`+${t.xp} XP`"></span>
                </div>
            </template>
        </div>

        <main class="flex-1 flex">
            <div class="w-full max-w-2xl mx-auto px-4 pt-6 sm:pt-10 pb-36 flex flex-col">
                <template x-for="s in [screen]" :key="s">
                    <section :class="dir === 'back' ? 'anim-in-back' : 'anim-in'" class="flex-1 flex flex-col">

                        <!-- WELCOME -->
                        <template x-if="s === 'welcome'">
                            <div class="flex-1 flex flex-col items-center justify-center text-center gap-6 py-4">
                                <div class="relative w-36 h-36 float">
                                    <div class="absolute inset-0 rounded-full border border-dashed border-gold-500/40 spin-slow"></div>
                                    <div class="absolute inset-3 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 p-[2px] shadow-2xl shadow-gold-500/25">
                                        <div class="w-full h-full rounded-full bg-emerald-900 flex items-center justify-center">
                                            <img :src="opts.logo" alt="" class="w-16 h-16 object-contain">
                                        </div>
                                    </div>
                                    <span class="absolute -top-1 right-3 text-xl">✨</span>
                                    <span class="absolute bottom-2 -left-2 text-lg">🌙</span>
                                    <span class="absolute bottom-0 right-0 text-lg">📖</span>
                                </div>

                                <div class="space-y-1">
                                    <p class="font-quran text-2xl text-gold-300/90" dir="rtl" lang="ar">وَلَقَدْ يَسَّرْنَا الْقُرْآنَ لِلذِّكْرِ فَهَلْ مِن مُّدَّكِرٍ</p>
                                    <p class="text-[11px] text-slate-400 italic">"And We have certainly made the Quran easy to remember, so is there anyone who will be mindful?" (Al-Qamar 54:17)</p>
                                </div>

                                <div class="space-y-3 max-w-lg">
                                    <h1 class="text-3xl sm:text-5xl font-black tracking-tight leading-[1.1]">
                                        Customize my <span class="shimmer">app experience</span>
                                    </h1>
                                    <p class="text-slate-300 text-base sm:text-lg leading-relaxed">
                                        Tell us what you need, discover features you may have missed, and vote on what we build next.
                                    </p>
                                </div>

                                <div class="grid grid-cols-3 gap-2 w-full max-w-md">
                                    <div class="rounded-2xl bg-white/[0.04] border border-white/10 px-2 py-3"><div class="text-xl">⏱️</div><div class="text-[11px] font-bold text-slate-300 mt-1">About 2 min</div></div>
                                    <div class="rounded-2xl bg-white/[0.04] border border-white/10 px-2 py-3"><div class="text-xl">🗳️</div><div class="text-[11px] font-bold text-slate-300 mt-1">Vote on the roadmap</div></div>
                                    <div class="rounded-2xl bg-white/[0.04] border border-white/10 px-2 py-3"><div class="text-xl">🏅</div><div class="text-[11px] font-bold text-slate-300 mt-1">Earn a badge</div></div>
                                </div>

                                <div x-show="alreadyAnswered && !resumeAt" class="w-full max-w-md rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200 font-semibold">
                                    ✅ You've already shared your voice. Thank you! You can go through it again to update your answers.
                                </div>

                                <div class="w-full max-w-md space-y-2.5">
                                    <template x-if="resumeAt">
                                        <div class="space-y-2.5">
                                            <button type="button" @click="resume()" class="w-full py-4 rounded-2xl bg-gradient-to-r from-gold-500 to-gold-400 text-emerald-950 font-black text-base shadow-xl shadow-gold-500/20 hover:brightness-105 active:scale-[0.98] transition">
                                                Continue where you left off →
                                            </button>
                                            <button type="button" @click="restart()" class="w-full py-3 rounded-2xl text-sm font-bold text-slate-300 hover:text-white hover:bg-white/5 transition">Start over</button>
                                        </div>
                                    </template>
                                    <template x-if="!resumeAt">
                                        <button type="button" @click="start()" class="w-full py-4 rounded-2xl bg-gradient-to-r from-gold-500 to-gold-400 text-emerald-950 font-black text-base shadow-xl shadow-gold-500/20 hover:brightness-105 active:scale-[0.98] transition">
                                            <span x-text="alreadyAnswered ? 'Update my answers →' : 'Let\'s begin →'"></span>
                                        </button>
                                    </template>
                                    <p class="text-[11px] text-slate-500">No sign-in needed.<span class="hidden sm:inline"> Press <kbd class="px-1.5 py-0.5 rounded bg-white/10 text-slate-300 font-mono">Enter</kbd> to start.</span></p>
                                    <a x-show="isAndroid" :href="appIntent" class="inline-flex items-center justify-center gap-1.5 text-sm font-bold text-gold-300 hover:text-gold-200">Open in the app</a>
                                </div>
                            </div>
                        </template>

                        <!-- CHOICE QUESTIONS (single + multi) -->
                        <template x-if="isChoice(s)">
                            <div class="flex flex-col gap-6">
                                <div class="space-y-2" x-init="$nextTick(() => $el.querySelector('h2')?.focus())">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-gold-400" x-text="q(s).eyebrow"></p>
                                    <h2 tabindex="-1" class="text-2xl sm:text-4xl font-black tracking-tight leading-tight outline-none" x-text="q(s).title"></h2>
                                    <p class="text-sm text-slate-400 font-medium" x-text="q(s).hint"></p>
                                </div>
                                <div class="grid grid-cols-2 gap-2.5 sm:gap-3" role="group" :aria-label="q(s).title">
                                    <template x-for="(opt, i) in q(s).options" :key="opt.value">
                                        <button type="button" data-opt @click="choose(s, opt.value)" :aria-pressed="isSelected(s, opt.value).toString()"
                                            :style="`animation-delay:${i * 35}ms`"
                                            class="rise relative flex flex-col items-start gap-2.5 rounded-2xl border p-3.5 sm:p-4 text-left min-h-[96px] transition-all duration-200 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-400"
                                            :class="[
                                                isSelected(s, opt.value) ? 'border-gold-400 bg-gold-500/15 ring-2 ring-gold-400/25' : (isFull(s) ? 'border-white/5 bg-white/[0.02] opacity-50' : 'border-white/10 bg-white/[0.04] hover:border-gold-500/40 hover:bg-white/[0.07]'),
                                                shakeKey === s + opt.value ? 'shake' : ''
                                            ]">
                                            <span class="text-2xl w-11 h-11 rounded-xl flex items-center justify-center transition-colors"
                                                :class="isSelected(s, opt.value) ? 'bg-gold-500/20 pop' : 'bg-white/5'" x-text="opt.emoji"></span>
                                            <span class="font-bold text-[14px] sm:text-[15px] leading-snug pr-5" x-text="opt.label"></span>
                                            <span class="absolute top-3 right-3 w-5 h-5 rounded-full border flex items-center justify-center transition-all"
                                                :class="isSelected(s, opt.value) ? 'bg-gold-400 border-gold-400 text-emerald-950 scale-100' : 'border-white/20 text-transparent'">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            </span>
                                            <span x-show="i < 9" class="hidden sm:block absolute bottom-3 right-3 text-[10px] font-mono text-slate-500" x-text="i + 1"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- FEATURE DECK -->
                        <template x-if="s === 'features'">
                            <div class="flex flex-col gap-5">
                                <div class="space-y-2" x-init="$nextTick(() => $el.querySelector('h2')?.focus())">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-gold-400">Already inside</p>
                                    <h2 tabindex="-1" class="text-2xl sm:text-4xl font-black tracking-tight leading-tight outline-none">Here's what Eternal Echo can already do</h2>
                                    <p class="text-sm text-slate-400 font-medium">Swipe right if you love it, left if it's not for you, or up if you want to try it.</p>
                                </div>

                                <template x-if="deckIndex < features.length">
                                    <div class="flex flex-col items-center gap-5">
                                        <div class="relative w-full max-w-sm h-[360px] sm:h-[380px]">
                                            <template x-for="card in visibleCards" :key="card.key">
                                                <article class="deck-card absolute inset-0 rounded-[28px] border border-gold-500/20 bg-emerald-900 shadow-2xl shadow-black/50 overflow-hidden flex flex-col"
                                                    :style="cardStyle(card.depth)" :aria-hidden="(card.depth !== 0).toString()"
                                                    @pointerdown="card.depth === 0 && dragStart($event)"
                                                    @pointermove="card.depth === 0 && dragMove($event)"
                                                    @pointerup="card.depth === 0 && dragEnd()"
                                                    @pointercancel="card.depth === 0 && dragEnd()">
                                                    <div class="relative h-40 bg-gradient-to-br flex items-center justify-center" :class="card.gradient">
                                                        <div class="absolute inset-0 dots" style="opacity:.08"></div>
                                                        <span class="text-7xl drop-shadow-xl" x-text="card.emoji"></span>
                                                        <span class="absolute top-4 left-4 px-2.5 py-1 rounded-full bg-emerald-950/60 border border-white/10 text-[10px] font-black uppercase tracking-wider text-slate-200" x-text="card.tag"></span>
                                                        <span class="absolute top-4 right-4 text-[11px] font-black text-slate-200/80 tabular-nums" x-text="`${card.index + 1}/${features.length}`"></span>
                                                    </div>
                                                    <div class="flex-1 p-5 flex flex-col gap-2">
                                                        <h3 class="text-xl font-black tracking-tight" x-text="card.title"></h3>
                                                        <p class="text-sm text-slate-300 leading-relaxed" x-text="card.body"></p>
                                                        <a :href="card.url" target="_blank" rel="noopener" @pointerdown.stop
                                                            class="mt-auto self-start inline-flex items-center gap-1 text-sm font-bold text-gold-400 hover:text-gold-300">
                                                            Take a look <span aria-hidden="true">↗</span>
                                                        </a>
                                                    </div>
                                                    <!-- Swipe stamps -->
                                                    <template x-if="card.depth === 0">
                                                        <div class="pointer-events-none">
                                                            <div class="absolute top-8 left-6 -rotate-12 px-3 py-1.5 rounded-xl border-[3px] border-emerald-400 text-emerald-300 font-black text-lg bg-emerald-950/70" :style="`opacity:${stamp('love')}`">LOVE IT 😍</div>
                                                            <div class="absolute top-8 right-6 rotate-12 px-3 py-1.5 rounded-xl border-[3px] border-rose-400 text-rose-300 font-black text-lg bg-emerald-950/70" :style="`opacity:${stamp('meh')}`">NOT FOR ME</div>
                                                            <div class="absolute bottom-20 left-1/2 -translate-x-1/2 px-3 py-1.5 rounded-xl border-[3px] border-sky-400 text-sky-300 font-black text-lg bg-emerald-950/70 whitespace-nowrap" :style="`opacity:${stamp('try')}`">WANT TO TRY 👀</div>
                                                        </div>
                                                    </template>
                                                </article>
                                            </template>
                                        </div>

                                        <div class="flex items-center justify-center gap-4">
                                            <button type="button" @click="undoCard()" :disabled="deckIndex === 0"
                                                class="w-11 h-11 rounded-full border border-white/10 bg-white/5 text-slate-300 flex items-center justify-center hover:bg-white/10 disabled:opacity-30 transition" aria-label="Undo last card" title="Undo">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v2M3 10l5-5M3 10l5 5" /></svg>
                                            </button>
                                            <button type="button" @click="react('meh')" class="w-16 h-16 rounded-full border-2 border-rose-400/60 bg-rose-500/10 text-2xl flex items-center justify-center hover:bg-rose-500/20 hover:scale-105 active:scale-95 transition" aria-label="Not for me" title="Not for me (←)">🙅</button>
                                            <button type="button" @click="react('try')" class="w-14 h-14 rounded-full border-2 border-sky-400/60 bg-sky-500/10 text-xl flex items-center justify-center hover:bg-sky-500/20 hover:scale-105 active:scale-95 transition" aria-label="Want to try" title="Want to try (↑)">👀</button>
                                            <button type="button" @click="react('love')" class="w-16 h-16 rounded-full border-2 border-emerald-400/60 bg-emerald-500/10 text-2xl flex items-center justify-center hover:bg-emerald-500/20 hover:scale-105 active:scale-95 transition" aria-label="Love it" title="Love it (→)">😍</button>
                                            <button type="button" @click="skipDeck()" class="w-11 h-11 rounded-full text-[11px] font-bold text-slate-400 hover:text-white hover:bg-white/5 transition" title="Skip the rest">Skip</button>
                                        </div>
                                        <p class="hidden sm:block text-[11px] text-slate-500">Keyboard: ← not for me · ↑ want to try · → love it</p>
                                    </div>
                                </template>

                                <template x-if="deckIndex >= features.length">
                                    <div class="rise flex flex-col items-center text-center gap-4 py-6">
                                        <div class="text-6xl pop">🎉</div>
                                        <h3 class="text-2xl font-black" x-text="deckSummaryTitle"></h3>
                                        <div class="flex flex-wrap justify-center gap-2">
                                            <template x-for="r in ['love', 'try', 'meh']" :key="r">
                                                <span class="px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-sm font-bold"
                                                    x-text="`${opts.config.reactions[r].emoji} ${reactionCount(r)} ${{ love: 'loved', try: 'to try', meh: 'not for me' }[r]}`"></span>
                                            </template>
                                        </div>
                                        <button type="button" @click="deckIndex = 0" class="text-xs font-bold text-slate-400 hover:text-white underline underline-offset-4">Go through the cards again</button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- NPS -->
                        <template x-if="s === 'nps'">
                            <div class="flex flex-col gap-8">
                                <div class="space-y-2" x-init="$nextTick(() => $el.querySelector('h2')?.focus())">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-gold-400">Honest answer</p>
                                    <h2 tabindex="-1" class="text-2xl sm:text-4xl font-black tracking-tight leading-tight outline-none">How likely are you to recommend Eternal Echo to a friend or family member?</h2>
                                </div>
                                <div class="flex flex-col items-center gap-2">
                                    <div class="text-7xl transition-transform duration-200" :class="npsPop ? 'pop' : ''" x-text="npsFace(npsHover ?? answers.nps)"></div>
                                    <p class="h-5 text-sm font-bold text-slate-300" x-text="npsLabel(npsHover ?? answers.nps)"></p>
                                </div>
                                <div>
                                    <div class="nps-track h-1.5 rounded-full opacity-60 mb-3 hidden sm:block"></div>
                                    <div class="grid grid-cols-6 sm:grid-cols-11 gap-2 sm:gap-1.5" role="radiogroup" aria-label="Score from 0 to 10">
                                        <template x-for="n in 11" :key="n">
                                            <button type="button" role="radio" :aria-checked="(answers.nps === n - 1).toString()"
                                                @click="setNps(n - 1)" @mouseenter="npsHover = n - 1" @mouseleave="npsHover = null"
                                                class="aspect-square rounded-xl border text-sm sm:text-base font-black tabular-nums transition-all active:scale-90"
                                                :class="answers.nps === n - 1 ? 'bg-gold-400 border-gold-400 text-emerald-950 scale-110 shadow-lg shadow-gold-500/30' : 'bg-white/[0.04] border-white/10 hover:border-gold-500/50 hover:bg-white/10'"
                                                x-text="n - 1"></button>
                                        </template>
                                    </div>
                                    <div class="flex justify-between mt-2 text-[11px] font-semibold text-slate-500">
                                        <span>Not likely</span><span>Very likely</span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- ONE WISH -->
                        <template x-if="s === 'wish'">
                            <div class="flex flex-col gap-5">
                                <div class="space-y-2">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-gold-400">Dream big</p>
                                    <h2 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight">If you could add one thing to the app, what would it be?</h2>
                                    <p class="text-sm text-slate-400 font-medium">Optional, but this is where the best ideas come from.</p>
                                </div>
                                <div class="relative">
                                    <textarea x-model="answers.wish" x-init="$nextTick(() => $el.focus())" maxlength="1000" rows="5"
                                        placeholder="I wish Eternal Echo could…"
                                        class="w-full rounded-2xl bg-white/[0.05] border border-white/10 focus:border-gold-400 focus:ring-2 focus:ring-gold-400/20 focus:outline-none p-4 text-base text-slate-100 placeholder:text-slate-500 resize-none transition"></textarea>
                                    <span class="absolute bottom-3 right-4 text-[11px] font-semibold tabular-nums" :class="answers.wish.length > 900 ? 'text-amber-400' : 'text-slate-500'" x-text="`${answers.wish.length}/1000`"></span>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-xs font-bold text-slate-400">Need inspiration? Tap to add:</p>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="(idea, i) in opts.config.wishIdeas" :key="idea">
                                            <button type="button" @click="addIdea(idea)" :style="`animation-delay:${i * 40}ms`"
                                                class="rise px-3 py-1.5 rounded-full border border-white/10 bg-white/[0.04] text-xs font-semibold text-slate-200 hover:border-gold-500/50 hover:text-gold-300 transition"
                                                x-text="`+ ${idea}`"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- CONTACT -->
                        <template x-if="s === 'contact'">
                            <div class="flex flex-col gap-5">
                                <div class="space-y-2">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-gold-400">Last step</p>
                                    <h2 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight">Want to hear when your ideas ship?</h2>
                                    <p class="text-sm text-slate-400 font-medium">Name and email are required. Add WhatsApp if you'd like updates there too.</p>
                                </div>
                                <div x-show="opts.user" class="flex items-center gap-3 rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3">
                                    <span class="text-xl">👤</span>
                                    <p class="text-sm text-emerald-100">You're signed in, so your answers are saved to your account and you can update them any time.</p>
                                </div>
                                <label class="block space-y-1.5">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Your name <span class="text-gold-400" aria-hidden="true">*</span></span>
                                    <input type="text" x-model="answers.name" maxlength="100" autocomplete="name" placeholder="e.g. Aisha" required
                                        class="w-full rounded-2xl bg-white/[0.05] border border-white/10 focus:border-gold-400 focus:ring-2 focus:ring-gold-400/20 focus:outline-none px-4 py-3.5 text-base text-slate-100 placeholder:text-slate-500 transition">
                                </label>
                                <label class="block space-y-1.5">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Email <span class="text-gold-400" aria-hidden="true">*</span></span>
                                    <input type="email" x-model="answers.email" maxlength="150" autocomplete="email" inputmode="email" placeholder="you@example.com" required
                                        class="w-full rounded-2xl bg-white/[0.05] border focus:ring-2 focus:outline-none px-4 py-3.5 text-base text-slate-100 placeholder:text-slate-500 transition"
                                        :class="emailInvalid ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/20' : 'border-white/10 focus:border-gold-400 focus:ring-gold-400/20'">
                                    <span x-show="emailInvalid" class="text-xs font-semibold text-rose-300">That email doesn't look right.</span>
                                </label>
                                <label class="block space-y-1.5">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">WhatsApp number <span class="normal-case tracking-normal font-semibold text-slate-500">(optional)</span></span>
                                    <input type="tel" x-model="answers.whatsapp" maxlength="20" autocomplete="tel" inputmode="tel" placeholder="+92 300 1234567"
                                        class="w-full rounded-2xl bg-white/[0.05] border focus:ring-2 focus:outline-none px-4 py-3.5 text-base text-slate-100 placeholder:text-slate-500 transition"
                                        :class="whatsappInvalid ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-400/20' : 'border-white/10 focus:border-gold-400 focus:ring-gold-400/20'">
                                    <span x-show="whatsappInvalid" class="text-xs font-semibold text-rose-300">Include your country code, e.g. +92 300 1234567.</span>
                                    <span x-show="!whatsappInvalid" class="text-xs text-slate-500">💬 Get updates on WhatsApp.</span>
                                </label>
                                <button type="button" @click="answers.notify = !answers.notify" role="switch" :aria-checked="answers.notify.toString()"
                                    class="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3.5 text-left hover:bg-white/[0.07] transition">
                                    <span>
                                        <span class="block text-sm font-bold">🔔 Tell me when my wish is built</span>
                                        <span class="block text-xs text-slate-400 mt-0.5">One email when it happens, nothing else.</span>
                                    </span>
                                    <span class="relative w-12 h-7 rounded-full transition-colors shrink-0" :class="answers.notify ? 'bg-gold-400' : 'bg-white/15'">
                                        <span class="absolute top-1 left-1 w-5 h-5 rounded-full bg-white shadow transition-transform" :class="answers.notify ? 'translate-x-5' : ''"></span>
                                    </span>
                                </button>
                                <p x-show="error" class="rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm font-semibold text-rose-200" x-text="error"></p>
                            </div>
                        </template>

                        <!-- DONE -->
                        <template x-if="s === 'done'">
                            <div class="flex flex-col items-center text-center gap-6 py-2">
                                <div class="relative medal">
                                    <div class="absolute inset-0 rounded-full bg-gold-500/30 blur-2xl glow"></div>
                                    <div class="relative w-32 h-32 rounded-full bg-gradient-to-br from-gold-300 via-gold-500 to-gold-700 p-1 shadow-2xl shadow-gold-500/40">
                                        <div class="w-full h-full rounded-full bg-emerald-900 border-2 border-gold-300/40 flex flex-col items-center justify-center">
                                            <span class="text-4xl">🏅</span>
                                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-gold-300 mt-1">Founding</span>
                                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-gold-300">Voice</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <h2 class="text-3xl sm:text-4xl font-black tracking-tight">
                                        JazakAllahu Khairan<span x-show="firstName" x-text="`, ${firstName}`"></span>!
                                    </h2>
                                    <p class="text-slate-300 max-w-md">Your answers are now part of our roadmap. You earned the <span class="font-bold text-gold-300">Founding Voice</span> badge and <span class="font-bold text-gold-300 tabular-nums" x-text="`${xp} XP`"></span>.</p>
                                </div>

                                <div x-show="picks.length" class="w-full text-left space-y-3">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-gold-400 text-center">Picked for you, based on your answers</p>
                                    <div class="grid grid-cols-1 gap-2.5">
                                        <template x-for="(f, i) in picks" :key="f.key">
                                            <a :href="f.url" target="_blank" rel="noopener" :style="`animation-delay:${300 + i * 120}ms`"
                                                class="rise group flex items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-4 hover:border-gold-500/40 hover:bg-white/[0.07] transition">
                                                <span class="w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center text-2xl shrink-0" :class="f.gradient" x-text="f.emoji"></span>
                                                <span class="flex-1 min-w-0">
                                                    <span class="block font-bold" x-text="f.title"></span>
                                                    <span class="block text-xs text-slate-400 truncate" x-text="f.body"></span>
                                                </span>
                                                <span class="text-gold-400 group-hover:translate-x-1 transition-transform" aria-hidden="true">→</span>
                                            </a>
                                        </template>
                                    </div>
                                </div>

                                <div class="w-full grid gap-2.5 sm:grid-cols-2">
                                    <button type="button" @click="share()" class="py-3.5 rounded-2xl bg-gradient-to-r from-gold-500 to-gold-400 text-emerald-950 font-black shadow-xl shadow-gold-500/20 hover:brightness-105 active:scale-[0.98] transition">
                                        📣 Invite a friend to vote
                                    </button>
                                    <a x-show="opts.source !== 'android'" :href="opts.playUrl" target="_blank" rel="noopener" class="py-3.5 rounded-2xl border border-white/15 bg-white/5 font-bold hover:bg-white/10 transition">
                                        ▶ Get the Android app
                                    </a>
                                    <a x-show="opts.source === 'android'" :href="opts.homeUrl" class="py-3.5 rounded-2xl border border-white/15 bg-white/5 font-bold hover:bg-white/10 transition">
                                        Explore Eternal Echo
                                    </a>
                                </div>
                                <a :href="opts.homeUrl" class="text-sm font-semibold text-slate-400 hover:text-white">Back to Eternal Echo</a>
                            </div>
                        </template>
                    </section>
                </template>
            </div>
        </main>

        <!-- Bottom action bar -->
        <div x-show="showFooter" x-transition.opacity
            class="fixed bottom-0 inset-x-0 z-30 bg-gradient-to-t from-emerald-950 via-emerald-950/95 to-transparent pt-8"
            style="padding-bottom: max(16px, env(safe-area-inset-bottom));">
            <div class="max-w-2xl mx-auto px-4 flex items-center gap-3">
                <p x-show="isMulti(screen)" class="text-xs font-bold text-slate-400 tabular-nums shrink-0"
                    x-text="`${selectedCount(screen)} of ${q(screen)?.max} picked`"></p>
                <button x-show="screen === 'wish' && !answers.wish.trim()" type="button" @click="next()" class="px-4 py-4 rounded-2xl text-sm font-bold text-slate-400 hover:text-white transition shrink-0">Skip</button>
                <button type="button" @click="screen === 'contact' ? submit() : next()" :disabled="!canContinue || submitting"
                    class="flex-1 py-4 rounded-2xl font-black text-base transition active:scale-[0.98] disabled:active:scale-100"
                    :class="canContinue && !submitting ? 'bg-gradient-to-r from-gold-500 to-gold-400 text-emerald-950 shadow-xl shadow-gold-500/20 hover:brightness-105' : 'bg-white/10 text-slate-500 cursor-not-allowed'">
                    <span x-show="!submitting" x-text="ctaLabel"></span>
                    <span x-show="submitting" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" /><path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                        Sending…
                    </span>
                </button>
            </div>
        </div>

        <canvas x-ref="confetti" class="fixed inset-0 w-full h-full pointer-events-none z-50" aria-hidden="true"></canvas>
    </div>

    <script>
        function survey(opts) {
            const FLOW = ['welcome', 'goals', 'role', 'daily_time', 'features', 'wishlist', 'languages', 'platform', 'nps', 'wish', 'contact', 'done'];
            const STORE = 'ee-survey:' + opts.config.key;
            const CHEERS = ['MashaAllah! ✨', 'Great pick 🌟', 'Love that 💚', 'Noted! 📝', 'Barakallahu feek 🤲', 'Nice one 🙌'];
            const GOAL_TO_FEATURE = {
                quran_understanding: 'quranic_lens', seerah: 'seerah', quizzes: 'para_quizzes', duas: 'duas',
                research: 'research_community', salah: 'salah', family: 'para_quizzes', habit: 'streaks',
            };
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            const blankAnswers = () => ({
                goals: [], role: null, daily_time: null, wishlist: [], languages: [], platform: null,
                features: {}, nps: null, wish: '',
                name: opts.user ? opts.user.name : '', email: opts.user ? opts.user.email : '', whatsapp: '', notify: true,
            });

            return {
                opts,
                isAndroid: /Android/i.test(navigator.userAgent || ''),
                appIntent: 'intent://survey#Intent;scheme=eternalecho;package=com.asloobulhayat.eternalecho;S.browser_fallback_url=' + encodeURIComponent(opts.playUrl) + ';end',
                features: opts.config.features,
                totalQuestions: FLOW.length - 2,
                screen: 'welcome',
                dir: 'fwd',
                answers: blankAnswers(),
                xp: 0,
                awarded: {},
                deckIndex: 0,
                drag: { active: false, x: 0, y: 0, sx: 0, sy: 0 },
                leaving: null,
                npsHover: null,
                npsPop: false,
                xpBump: false,
                toast: null,
                shakeKey: null,
                submitting: false,
                error: null,
                startedAt: null,
                resumeAt: null,
                alreadyAnswered: opts.alreadyAnswered,
                cheer: 0,

                init() {
                    const saved = this.load();
                    if (saved) {
                        if (saved.done) {
                            this.alreadyAnswered = true;
                        } else if (saved.screen && FLOW.includes(saved.screen) && !['welcome', 'done'].includes(saved.screen)) {
                            Object.assign(this.answers, saved.answers || {});
                            if (opts.user) { this.answers.name ||= opts.user.name; this.answers.email ||= opts.user.email; }
                            this.xp = saved.xp || 0;
                            this.awarded = saved.awarded || {};
                            this.deckIndex = Math.min(saved.deckIndex || 0, this.features.length);
                            this.startedAt = saved.startedAt || Date.now();
                            this.resumeAt = saved.screen;
                        }
                    }
                    this.$watch('answers', () => this.save());
                    this.$watch('deckIndex', () => this.save());
                },

                // ---------- persistence (per device, best effort) ----------
                load() {
                    try { return JSON.parse(localStorage.getItem(STORE) || 'null'); } catch (e) { return null; }
                },
                save(extra = {}) {
                    if (this.screen === 'welcome' && !extra.done) return;
                    try {
                        localStorage.setItem(STORE, JSON.stringify(Object.assign({
                            screen: this.screen, answers: this.answers, xp: this.xp, awarded: this.awarded,
                            deckIndex: this.deckIndex, startedAt: this.startedAt,
                        }, extra)));
                    } catch (e) { /* storage unavailable: the survey still works, it just won't resume */ }
                },

                // ---------- navigation ----------
                get index() { return FLOW.indexOf(this.screen); },
                get questionNumber() { return Math.min(this.index, this.totalQuestions); },
                get inQuestions() { return this.index > 0 && this.screen !== 'done'; },
                get canGoBack() { return this.inQuestions && !this.submitting; },
                get timeLeft() {
                    const secs = (this.totalQuestions - this.questionNumber + 1) * 12;
                    return secs <= 20 ? 'Almost done' : `~${Math.max(1, Math.round(secs / 60))} min left`;
                },
                go(target, dir = 'fwd') {
                    this.dir = dir;
                    this.screen = target;
                    this.error = null;
                    window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
                    this.save();
                },
                start() {
                    // Going through it again after finishing? Start from the previous answers.
                    const saved = this.load();
                    this.answers = Object.assign(blankAnswers(), saved && saved.done ? saved.answers : {});
                    if (opts.user) { this.answers.name ||= opts.user.name; this.answers.email ||= opts.user.email; }
                    this.xp = 0; this.awarded = {}; this.deckIndex = 0;
                    this.startedAt = Date.now();
                    this.go('goals');
                },
                resume() { const target = this.resumeAt; this.resumeAt = null; this.go(target); },
                restart() { this.resumeAt = null; try { localStorage.removeItem(STORE); } catch (e) {} this.start(); },
                back() { if (this.index > 1) this.go(FLOW[this.index - 1], 'back'); },
                next() {
                    if (!this.canContinue) return;
                    this.award(this.screen);
                    this.go(FLOW[this.index + 1]);
                },

                // ---------- questions ----------
                q(key) { return opts.config.questions[key]; },
                isChoice(key) { return !!opts.config.questions[key]; },
                isMulti(key) { return this.q(key)?.type === 'multi'; },
                selectedCount(key) { return (this.answers[key] || []).length; },
                isSelected(key, value) {
                    const a = this.answers[key];
                    return Array.isArray(a) ? a.includes(value) : a === value;
                },
                isFull(key) { return this.isMulti(key) && this.selectedCount(key) >= this.q(key).max; },
                choose(key, value) {
                    if (this.isMulti(key)) {
                        const list = this.answers[key];
                        if (list.includes(value)) {
                            this.answers[key] = list.filter(v => v !== value);
                        } else if (list.length >= this.q(key).max) {
                            this.shakeKey = key + value;
                            setTimeout(() => { this.shakeKey = null; }, 420);
                            this.say(`Pick up to ${this.q(key).max}. Tap one to swap it out`);
                        } else {
                            this.answers[key] = [...list, value];
                            this.buzz();
                        }
                        return;
                    }
                    this.answers[key] = value;
                    this.buzz();
                    clearTimeout(this._auto);
                    this._auto = setTimeout(() => { if (this.screen === key) this.next(); }, reduceMotion ? 120 : 420);
                },

                // ---------- feature deck ----------
                get visibleCards() {
                    const cards = [];
                    for (let d = 2; d >= 0; d--) {
                        const i = this.deckIndex + d;
                        if (i < this.features.length) cards.push(Object.assign({}, this.features[i], { depth: d, index: i }));
                    }
                    return cards;
                },
                cardStyle(depth) {
                    if (depth > 0) {
                        return `transform: translateY(${depth * 14}px) scale(${1 - depth * 0.05}); opacity:${1 - depth * 0.3}; transition: transform .35s ease, opacity .35s ease; z-index:${10 - depth}`;
                    }
                    if (this.leaving) {
                        const [ex, ey] = { love: [1, 0], meh: [-1, 0], try: [0, -1] }[this.leaving];
                        return `transform: translate(${ex * 150}%, ${ey * 130}%) rotate(${ex * 22}deg); opacity:0; transition: transform .38s ease-in, opacity .38s ease-in; z-index:10`;
                    }
                    const { x, y, active } = this.drag;
                    return `transform: translate(${x}px, ${Math.min(y, 40)}px) rotate(${x / 16}deg); transition: ${active ? 'none' : 'transform .35s cubic-bezier(.2,.9,.3,1.25)'}; z-index:10; cursor:${active ? 'grabbing' : 'grab'}`;
                },
                stamp(kind) {
                    const { x, y } = this.drag;
                    if (this.leaving) return this.leaving === kind ? 1 : 0;
                    if (kind === 'love') return Math.max(0, Math.min(1, x / 110));
                    if (kind === 'meh') return Math.max(0, Math.min(1, -x / 110));
                    return Math.abs(x) < 70 ? Math.max(0, Math.min(1, -y / 110)) : 0;
                },
                dragStart(e) {
                    if (this.leaving || e.button > 0) return;
                    this.drag = { active: true, x: 0, y: 0, sx: e.clientX, sy: e.clientY };
                    e.currentTarget.setPointerCapture?.(e.pointerId);
                },
                dragMove(e) {
                    if (!this.drag.active) return;
                    this.drag.x = e.clientX - this.drag.sx;
                    this.drag.y = e.clientY - this.drag.sy;
                },
                dragEnd() {
                    if (!this.drag.active) return;
                    const { x, y } = this.drag;
                    this.drag.active = false;
                    if (x > 110) this.react('love');
                    else if (x < -110) this.react('meh');
                    else if (y < -110 && Math.abs(x) < 90) this.react('try');
                    else { this.drag.x = 0; this.drag.y = 0; }
                },
                react(kind) {
                    if (this.leaving || this.deckIndex >= this.features.length) return;
                    const card = this.features[this.deckIndex];
                    this.answers.features = Object.assign({}, this.answers.features, { [card.key]: kind });
                    if (!this.awarded['card:' + card.key]) { this.awarded['card:' + card.key] = true; this.addXp(5); }
                    this.leaving = kind;
                    setTimeout(() => {
                        this.leaving = null;
                        this.drag = { active: false, x: 0, y: 0, sx: 0, sy: 0 };
                        this.deckIndex++;
                        if (this.deckIndex === this.features.length) this.say(this.deckSummaryTitle, 0);
                    }, reduceMotion ? 60 : 340);
                },
                undoCard() {
                    if (this.deckIndex === 0 || this.leaving) return;
                    this.deckIndex--;
                    const copy = Object.assign({}, this.answers.features);
                    delete copy[this.features[this.deckIndex].key];
                    this.answers.features = copy;
                },
                skipDeck() { this.deckIndex = this.features.length; },
                reactionCount(kind) { return Object.values(this.answers.features).filter(v => v === kind).length; },
                get deckSummaryTitle() {
                    const loved = this.reactionCount('love');
                    if (loved === 0) return 'Thanks, that helps us focus 🙏';
                    return loved === 1 ? 'You loved 1 feature ✨' : `You loved ${loved} features ✨`;
                },

                // ---------- NPS ----------
                setNps(n) {
                    this.answers.nps = n;
                    this.npsPop = false;
                    this.$nextTick(() => { this.npsPop = true; });
                    this.buzz();
                    clearTimeout(this._auto);
                    this._auto = setTimeout(() => { if (this.screen === 'nps') this.next(); }, reduceMotion ? 150 : 750);
                },
                npsFace(n) {
                    if (n === null || n === undefined) return '🤔';
                    return n <= 2 ? '😞' : n <= 4 ? '😕' : n <= 6 ? '😐' : n <= 8 ? '🙂' : n === 9 ? '😊' : '😍';
                },
                npsLabel(n) {
                    if (n === null || n === undefined) return 'Tap a number';
                    return ['Not at all', 'Very unlikely', 'Unlikely', 'Probably not', 'Maybe not', 'Not sure', 'Maybe', 'Likely', 'Very likely', 'Definitely', 'Already have! 💚'][n];
                },

                // ---------- wish / contact ----------
                addIdea(idea) {
                    const current = this.answers.wish.trim();
                    if (current.includes(idea)) return;
                    this.answers.wish = (current ? current + (/[.!?]$/.test(current) ? ' ' : '. ') : '') + idea;
                },
                get emailInvalid() {
                    const e = (this.answers.email || '').trim();
                    return e.length > 0 && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(e);
                },
                get whatsappInvalid() {
                    const w = (this.answers.whatsapp || '').trim();
                    return w.length > 0 && !/^\+?[0-9][0-9\s\-()]{6,19}$/.test(w);
                },
                get contactComplete() {
                    return !!(this.answers.name || '').trim() && !!(this.answers.email || '').trim() && !this.emailInvalid && !this.whatsappInvalid;
                },
                get firstName() { return (this.answers.name || '').trim().split(/\s+/)[0] || ''; },

                // ---------- footer CTA ----------
                get canContinue() {
                    const s = this.screen;
                    if (this.isChoice(s)) return this.isMulti(s) ? this.selectedCount(s) > 0 : !!this.answers[s];
                    if (s === 'features') return this.deckIndex >= this.features.length;
                    if (s === 'nps') return this.answers.nps !== null;
                    if (s === 'wish') return true;
                    if (s === 'contact') return this.contactComplete;
                    return false;
                },
                get showFooter() {
                    const s = this.screen;
                    if (this.isChoice(s)) return this.isMulti(s) || !!this.answers[s];
                    if (s === 'features') return this.deckIndex >= this.features.length;
                    if (s === 'nps') return this.answers.nps !== null;
                    return s === 'wish' || s === 'contact';
                },
                get ctaLabel() {
                    if (this.screen === 'contact') return 'Send my answers ✨';
                    if (this.screen === 'wish' && this.answers.wish.trim()) return 'Add my wish →';
                    return 'Continue →';
                },

                // ---------- rewards & feedback ----------
                addXp(n) {
                    this.xp += n;
                    this.xpBump = false;
                    this.$nextTick(() => { this.xpBump = true; setTimeout(() => { this.xpBump = false; }, 460); });
                },
                award(screen) {
                    if (this.awarded[screen] || screen === 'features') return;
                    let points = 10;
                    if (screen === 'wish') { if (!this.answers.wish.trim()) return; points = 20; }
                    this.awarded[screen] = true;
                    this.addXp(points);
                    const n = this.questionNumber;
                    const text = screen === 'contact' ? 'Sent! 🎉'
                        : n === Math.ceil(this.totalQuestions / 2) ? 'Halfway there! 🎉'
                        : n === this.totalQuestions - 2 ? 'Almost done 🙌'
                        : screen === 'wish' ? 'What a wish! 💫'
                        : CHEERS[this.cheer++ % CHEERS.length];
                    this.say(text, points);
                },
                say(text, xp = 0) {
                    clearTimeout(this._toast);
                    this.toast = { id: Date.now(), text, xp };
                    this._toast = setTimeout(() => { this.toast = null; }, 1700);
                },
                buzz() { try { navigator.vibrate?.(12); } catch (e) {} },

                // ---------- keyboard ----------
                onKey(e) {
                    const tag = (e.target.tagName || '').toLowerCase();
                    const typing = tag === 'input' || tag === 'textarea';
                    if (e.key === 'Enter' && (!typing || e.ctrlKey || e.metaKey || tag === 'input')) {
                        if (this.screen === 'welcome') { e.preventDefault(); this.resumeAt ? this.resume() : this.start(); return; }
                        if (this.showFooter && this.canContinue) {
                            // Focused buttons keep their own Enter, except answer tiles, where Enter means "continue".
                            if (tag === 'button' && e.target.dataset.opt === undefined) return;
                            e.preventDefault();
                            this.screen === 'contact' ? this.submit() : this.next();
                        }
                        return;
                    }
                    if (typing || e.ctrlKey || e.metaKey || e.altKey) return;
                    if (this.screen === 'features' && this.deckIndex < this.features.length) {
                        if (e.key === 'ArrowRight') { e.preventDefault(); this.react('love'); }
                        if (e.key === 'ArrowLeft') { e.preventDefault(); this.react('meh'); }
                        if (e.key === 'ArrowUp') { e.preventDefault(); this.react('try'); }
                        return;
                    }
                    if (this.isChoice(this.screen) && /^[1-9]$/.test(e.key)) {
                        const opt = this.q(this.screen).options[Number(e.key) - 1];
                        if (opt) this.choose(this.screen, opt.value);
                    }
                    if (this.screen === 'nps' && /^[0-9]$/.test(e.key)) this.setNps(Number(e.key));
                },

                // ---------- submit ----------
                async submit() {
                    if (this.submitting || !this.canContinue) return;
                    this.submitting = true;
                    this.error = null;
                    const a = this.answers;
                    const payload = {
                        goals: a.goals, role: a.role, daily_time: a.daily_time, wishlist: a.wishlist,
                        languages: a.languages, platform: a.platform, features: a.features, nps: a.nps,
                        wish: a.wish.trim() || null, name: a.name.trim() || null, email: a.email.trim() || null,
                        whatsapp: (a.whatsapp || '').trim() || null,
                        notify: !!(a.notify && a.email.trim()), source: opts.source,
                        duration_seconds: this.startedAt ? Math.round((Date.now() - this.startedAt) / 1000) : null,
                    };
                    try {
                        const res = await fetch(opts.storeUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(payload),
                        });
                        if (res.ok) {
                            this.award('contact');
                            this.alreadyAnswered = true;
                            this.go('done');
                            this.save({ done: true });
                            this.$nextTick(() => this.celebrate());
                            return;
                        }
                        if (res.status === 422) {
                            const data = await res.json().catch(() => ({}));
                            const field = Object.keys(data.errors || {})[0] || '';
                            const screen = FLOW.find(f => field === f || field.startsWith(f + '.'));
                            if (screen && screen !== 'contact') {
                                this.go(screen, 'back');
                                this.say('Please check this answer once more');
                            } else {
                                this.error = (data.errors && data.errors[field] && data.errors[field][0]) || 'Something in your answers needs another look.';
                            }
                        } else if (res.status === 419) {
                            this.error = 'Your session expired. Refresh the page and your answers will still be here.';
                        } else if (res.status === 429) {
                            this.error = 'Too many tries in a row. Please wait a minute and send again.';
                        } else {
                            this.error = 'We could not save your answers just now. Please try again.';
                        }
                    } catch (e) {
                        this.error = 'You seem to be offline. Your answers are saved here, so try again when you are connected.';
                    } finally {
                        this.submitting = false;
                    }
                },

                // ---------- done screen ----------
                get picks() {
                    const reactions = this.answers.features;
                    const order = [];
                    this.features.forEach(f => { if (reactions[f.key] === 'try') order.push(f.key); });
                    this.answers.goals.forEach(g => { const k = GOAL_TO_FEATURE[g]; if (k) order.push(k); });
                    this.features.forEach(f => { if (reactions[f.key] === 'love') order.push(f.key); });
                    const seen = new Set();
                    return order
                        .filter(k => reactions[k] !== 'meh' && !seen.has(k) && seen.add(k))
                        .slice(0, 3)
                        .map(k => this.features.find(f => f.key === k))
                        .filter(Boolean);
                },
                async share() {
                    const data = { title: 'Eternal Echo', text: 'Help shape Eternal Echo, a Quran & Seerah learning app. 2-minute survey:', url: opts.shareUrl };
                    try {
                        if (navigator.share) { await navigator.share(data); return; }
                        await navigator.clipboard.writeText(`${data.text} ${data.url}`);
                        this.say('Link copied. Share it anywhere 📋');
                    } catch (e) {
                        if (e && e.name === 'AbortError') return;
                        this.say(opts.shareUrl);
                    }
                },
                celebrate() {
                    if (reduceMotion) return;
                    const canvas = this.$refs.confetti;
                    const ctx = canvas.getContext('2d');
                    const dpr = window.devicePixelRatio || 1;
                    canvas.width = innerWidth * dpr; canvas.height = innerHeight * dpr;
                    ctx.scale(dpr, dpr);
                    const colors = ['#d4af37', '#f6e58d', '#10b981', '#34d399', '#ffffff', '#e6ca65'];
                    const pieces = Array.from({ length: 140 }, () => ({
                        x: innerWidth / 2 + (Math.random() - 0.5) * 120,
                        y: innerHeight * 0.32,
                        vx: (Math.random() - 0.5) * 14,
                        vy: -Math.random() * 13 - 4,
                        size: Math.random() * 7 + 4,
                        rot: Math.random() * Math.PI,
                        vr: (Math.random() - 0.5) * 0.3,
                        color: colors[Math.floor(Math.random() * colors.length)],
                        shape: Math.random() > 0.55 ? 'star' : 'rect',
                    }));
                    const star = (x, y, r) => {
                        ctx.beginPath();
                        for (let i = 0; i < 8; i++) {
                            const rad = i % 2 === 0 ? r : r * 0.45;
                            const ang = (Math.PI / 4) * i;
                            ctx.lineTo(x + rad * Math.cos(ang), y + rad * Math.sin(ang));
                        }
                        ctx.closePath(); ctx.fill();
                    };
                    const started = performance.now();
                    const frame = (t) => {
                        const elapsed = t - started;
                        ctx.clearRect(0, 0, innerWidth, innerHeight);
                        pieces.forEach(p => {
                            p.vy += 0.32; p.vx *= 0.99; p.x += p.vx; p.y += p.vy; p.rot += p.vr;
                            ctx.save();
                            ctx.globalAlpha = Math.max(0, 1 - elapsed / 3200);
                            ctx.translate(p.x, p.y); ctx.rotate(p.rot); ctx.fillStyle = p.color;
                            p.shape === 'star' ? star(0, 0, p.size * 0.8) : ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
                            ctx.restore();
                        });
                        if (elapsed < 3300) requestAnimationFrame(frame);
                        else ctx.clearRect(0, 0, innerWidth, innerHeight);
                    };
                    requestAnimationFrame(frame);
                },
            };
        }
    </script>
    @endverbatim
</body>

</html>
