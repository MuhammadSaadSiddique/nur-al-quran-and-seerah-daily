@extends('layouts.app')

@section('title', 'Support The Eternal Echo — Sadaqah Jariyah & Project Funding')
@section('meta_description', 'Support the continuity of The Eternal Echo. Help fund our high-availability servers, AI research models, and ad-free Islamic educational platform as a Sadaqah Jariyah.')

@section('content')
<div class="max-w-5xl mx-auto py-6 sm:py-10 animate-fadeIn" x-data="donationPage()">

    {{-- Hero Banner --}}
    <div class="relative bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 border border-emerald-500/30 rounded-3xl p-8 sm:p-12 text-white shadow-2xl overflow-hidden mb-12">
        {{-- Background Glow & Geometric Pattern --}}
        <div class="absolute -top-24 -right-24 w-80 h-80 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-teal-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 text-center max-w-3xl mx-auto space-y-6">
            {{-- Islamic Arabic Calligraphy Greeting --}}
            <div class="inline-flex items-center space-x-2 px-4 py-1.5 rounded-full bg-emerald-500/15 border border-emerald-400/30 text-emerald-300 text-xs font-bold uppercase tracking-widest">
                <span>بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</span>
            </div>

            <h1 class="text-3xl sm:text-5xl font-black tracking-tight leading-tight">
                Keep the Light of <br class="hidden sm:inline">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-200">Sacred Knowledge Burning.</span>
            </h1>

            <p class="text-slate-300 text-sm sm:text-base leading-relaxed font-medium">
                <strong class="text-white">The Eternal Echo</strong> is completely free, non-profit, and 100% ad-free. 
                Your contribution directly covers cloud infrastructure, AI analytical models, and scholarly verification so seekers worldwide can learn the Quran and Seerah with zero commercial distractions.
            </p>

            {{-- Ayah & Hadith Cards --}}
            <div class="grid sm:grid-cols-2 gap-4 text-left pt-4">
                <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-5 space-y-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400 block">Surah Al-Baqarah (2:261)</span>
                    <p class="font-arabic text-right text-lg text-emerald-200 leading-relaxed">
                        مَّثَلُ الَّذِينَ يُنفِقُونَ أَمْوَالَهُمْ فِي سَبِيلِ اللَّهِ كَمَثَلِ حَبَّةٍ أَنبَتَتْ سَبْعَ سَنَابِلَ
                    </p>
                    <p class="text-xs text-slate-300 italic leading-relaxed">
                        "The example of those who spend their wealth in the way of Allah is like a seed of grain which grows seven spikes; in each spike is a hundred grains."
                    </p>
                </div>

                <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-5 space-y-2 flex flex-col justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-amber-400 block">Continuous Charity (Sadaqah Jariyah)</span>
                        <p class="text-xs text-slate-300 italic leading-relaxed mt-2">
                            "When a person dies, all their deeds end except three: a continuing charity (Sadaqah Jariyah), beneficial knowledge, or a righteous child who prays for them."
                        </p>
                    </div>
                    <span class="text-[11px] text-slate-400 font-semibold">— Prophet Muhammad ﷺ (Sahih Muslim 1631)</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Transparency: Where Your Support Goes --}}
    <div class="mb-14">
        <div class="text-center mb-8">
            <h2 class="text-2xl sm:text-3xl font-black text-slate-800 tracking-tight">How Your Support Keeps Us Online</h2>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">Every penny goes directly into the operational resilience of this educational platform.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-4">
                    🖥️
                </div>
                <h3 class="font-black text-slate-900 text-sm mb-1.5">24/7 Cloud Servers</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    High-speed web nodes, MySQL databases, and Redis caching that guarantee rapid responses for learners across all 6 continents.
                </p>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-2xl mb-4">
                    🤖
                </div>
                <h3 class="font-black text-slate-900 text-sm mb-1.5">AI Insights & Models</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    API usage costs for deep analytical insights, scientific cross-references, and contextual research assistants without paywalls.
                </p>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mb-4">
                    📖
                </div>
                <h3 class="font-black text-slate-900 text-sm mb-1.5">Scholarly Vetting</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Continuous verification of Hadith gradings, Quranic research links, Duas, and peer-reviewed scholarly contributions.
                </p>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl mb-4">
                    🛡️
                </div>
                <h3 class="font-black text-slate-900 text-sm mb-1.5">Zero Ads & Privacy</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    We pledge to never sell user data, track for advertisers, or clutter holy Quranic text with commercial popups.
                </p>
            </div>
        </div>
    </div>

    {{-- Suggested Contribution Tiers --}}
    <div class="mb-14">
        <div class="text-center mb-8">
            <h2 class="text-2xl sm:text-3xl font-black text-slate-800 tracking-tight">Choose Your Impact</h2>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">Select a suggested tier or give any amount your heart desires.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Tier 1 --}}
            <div @click="selectedAmount = '10'" 
                 :class="selectedAmount === '10' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-slate-200 bg-white'"
                 class="border rounded-2xl p-6 cursor-pointer transition-all hover:border-emerald-400 relative flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">🌟 Seedling</span>
                    <div class="mt-2 mb-3">
                        <span class="text-3xl font-black text-slate-900">$10</span>
                        <span class="text-xs text-slate-400">/ one-time or mo</span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Sponsors server bandwidth for over 1,000 students reading and taking quizzes this month.
                    </p>
                </div>
                <button type="button" class="mt-4 w-full py-2 bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 text-xs font-bold rounded-xl transition-all">
                    Select $10
                </button>
            </div>

            {{-- Tier 2 --}}
            <div @click="selectedAmount = '25'"
                 :class="selectedAmount === '25' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-slate-200 bg-white'"
                 class="border rounded-2xl p-6 cursor-pointer transition-all hover:border-emerald-400 relative flex flex-col justify-between shadow-sm">
                <span class="absolute -top-3 right-4 bg-emerald-600 text-white text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full shadow">Popular</span>
                <div>
                    <span class="text-xs font-bold text-teal-600 uppercase tracking-wider">📖 Knowledge Patron</span>
                    <div class="mt-2 mb-3">
                        <span class="text-3xl font-black text-slate-900">$25</span>
                        <span class="text-xs text-slate-400">/ one-time or mo</span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Covers secure cloud backup, database replication, and daily maintenance costs.
                    </p>
                </div>
                <button type="button" class="mt-4 w-full py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl transition-all shadow-md">
                    Select $25
                </button>
            </div>

            {{-- Tier 3 --}}
            <div @click="selectedAmount = '50'"
                 :class="selectedAmount === '50' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-slate-200 bg-white'"
                 class="border rounded-2xl p-6 cursor-pointer transition-all hover:border-emerald-400 relative flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">🏛️ Pillar of Light</span>
                    <div class="mt-2 mb-3">
                        <span class="text-3xl font-black text-slate-900">$50</span>
                        <span class="text-xs text-slate-400">/ one-time or mo</span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Powers automated AI queries and scientific correlation mapping for thousands of researchers.
                    </p>
                </div>
                <button type="button" class="mt-4 w-full py-2 bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 text-xs font-bold rounded-xl transition-all">
                    Select $50
                </button>
            </div>

            {{-- Tier 4 --}}
            <div @click="selectedAmount = '100'"
                 :class="selectedAmount === '100' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-slate-200 bg-white'"
                 class="border rounded-2xl p-6 cursor-pointer transition-all hover:border-emerald-400 relative flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">✨ Sadaqah Benefactor</span>
                    <div class="mt-2 mb-3">
                        <span class="text-3xl font-black text-slate-900">$100+</span>
                        <span class="text-xs text-slate-400">/ custom</span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Funds major platform expansion, multi-lingual translations, audio recitation engines, and native mobile upgrades.
                    </p>
                </div>
                <button type="button" class="mt-4 w-full py-2 bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-700 text-xs font-bold rounded-xl transition-all">
                    Select $100
                </button>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- PAYMENT DETAILS SECTION                                                   --}}
    {{-- (Organized cards with 1-click copy & placeholders ready for your details) --}}
    {{-- ========================================================================= --}}
    <div id="payment-methods" class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-xl mb-14">
        <div class="border-b border-slate-100 pb-6 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">
                    💳 Payment Channels
                </span>
                <h2 class="text-2xl font-black text-slate-900 mt-2">Payment Details</h2>
                <p class="text-xs text-slate-500">Choose your preferred channel below to complete your contribution.</p>
            </div>

            {{-- Channel Selector Tabs --}}
            <div class="flex flex-wrap gap-2">
                <button type="button" 
                        @click="activeTab = 'bank'"
                        :class="activeTab === 'bank' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5">
                    <span>🏦</span>
                    <span>Bank Transfer</span>
                </button>
                <!--<button type="button" 
                        @click="activeTab = 'online'"
                        :class="activeTab === 'online' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5">
                    <span>💳</span>
                    <span>Online / Card / PayPal</span>
                </button>-->
                <button type="button" 
                        @click="activeTab = 'mobile'"
                        :class="activeTab === 'mobile' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5">
                    <span>📱</span>
                    <span>Mobile Wallets</span>
                </button>
               <!-- <button type="button" 
                        @click="activeTab = 'crypto'"
                        :class="activeTab === 'crypto' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5">
                    <span>🪙</span>
                    <span>Crypto</span>
                </button>-->
            </div>
        </div>

        {{-- TAB 1: BANK TRANSFER --}}
        <div x-show="activeTab === 'bank'" x-transition class="space-y-4">
            <div class="p-4 bg-emerald-50/60 border border-emerald-100 rounded-2xl flex items-start space-x-3 text-xs text-emerald-900">
                <span class="text-base">ℹ️</span>
                <p>
                    Direct bank wire transfers allow 100% of your funds to reach the project with zero third-party gateway deductions. 
                    Copy any detail below with one click:
                </p>
            </div>

            <!-- EDIT PAYMENT DETAILS HERE: Direct Bank Transfer Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Account Title --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">Account Title</span>
                        <span class="text-sm font-bold text-slate-800">Muhammad Saad Siddique</span>
                    </div>
                    <button type="button" @click="copyText('The Eternal Echo / Asloob ul Hayat', 'Account Title copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>

                {{-- Bank Name --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">Bank Name</span>
                        <span class="text-sm font-bold text-slate-800">Meezan Bank Limited (Islamic Banking)</span>
                    </div>
                    <button type="button" @click="copyText('Meezan Bank Limited', 'Bank Name copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>

                {{-- IBAN --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">IBAN Number</span>
                        <span class="text-sm font-mono font-bold text-slate-900 tracking-wider">click to Copy</span>
                    </div>
                    <button type="button" @click="copyText('PK66MEZN0008010113847096', 'IBAN copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>

                <!--{{-- Account Number --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">Account Number</span>
                        <span class="text-sm font-mono font-bold text-slate-900">0101010101010101</span>
                    </div>
                    <button type="button" @click="copyText('0101010101010101', 'Account Number copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>

                {{-- SWIFT / BIC --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">SWIFT / BIC (International)</span>
                        <span class="text-sm font-mono font-bold text-slate-900">MEZNPKKA</span>
                    </div>
                    <button type="button" @click="copyText('MEZNPKKA', 'SWIFT code copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>

                {{-- Branch Code --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider block">Branch Code / Routing</span>
                        <span class="text-sm font-bold text-slate-800">Main Islamic Branch (0101)</span>
                    </div>
                    <button type="button" @click="copyText('0101', 'Branch code copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>-->
            </div>
        </div>

        <!--{{-- TAB 2: ONLINE / CARD / PAYPAL --}}
        <div x-show="activeTab === 'online'" x-transition class="space-y-6">
            <div class="p-4 bg-blue-50/60 border border-blue-100 rounded-2xl flex items-start space-x-3 text-xs text-blue-900">
                <span class="text-base">💳</span>
                <p>
                    Online debit/credit card and PayPal gateways enable instant, secure international contributions with recurring monthly options.
                </p>
            </div>

            <!-- EDIT PAYMENT DETAILS HERE: Online Card & PayPal URLs -->
           <!-- <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                {{-- PayPal Box --}}
                <div class="border border-slate-200 rounded-2xl p-6 bg-slate-50 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="text-2xl font-black text-[#003087]">Pay<span class="text-[#0079C1]">Pal</span></span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Support securely with your PayPal balance, linked bank account, or credit card in any international currency.
                        </p>
                        <div class="mt-3 bg-white border border-slate-200 rounded-xl p-3 flex items-center justify-between text-xs font-mono">
                            <span class="text-slate-700 truncate">paypal.me/TheEternalEcho</span>
                            <button type="button" @click="copyText('paypal.me/TheEternalEcho', 'PayPal link copied!')" class="text-blue-600 font-bold hover:underline ml-2">Copy</button>
                        </div>
                    </div>
                    <a href="https://paypal.me/TheEternalEcho" target="_blank" rel="noopener noreferrer"
                       class="w-full py-3 bg-[#0079C1] hover:bg-[#00457C] text-white font-bold text-xs rounded-xl shadow transition-all text-center flex items-center justify-center space-x-2">
                        <span>Donate via PayPal</span>
                        <span>→</span>
                    </a>
                </div>

                {{-- Stripe / Credit Card Box --}}
                <div class="border border-slate-200 rounded-2xl p-6 bg-slate-50 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="text-2xl font-black text-slate-900">Credit / Debit Card</span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Secured by 256-bit encryption. Accepts Visa, Mastercard, American Express, Apple Pay, and Google Pay.
                        </p>
                        <div class="mt-3 bg-white border border-slate-200 rounded-xl p-3 flex items-center justify-between text-xs font-mono">
                            <span class="text-slate-700 truncate">donate.stripe.com/theeternalecho</span>
                            <button type="button" @click="copyText('https://donate.stripe.com/theeternalecho', 'Card payment link copied!')" class="text-emerald-600 font-bold hover:underline ml-2">Copy</button>
                        </div>
                    </div>
                    <a href="https://donate.stripe.com/theeternalecho" target="_blank" rel="noopener noreferrer"
                       class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow transition-all text-center flex items-center justify-center space-x-2">
                        <span>Pay with Card (Stripe)</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        </div>-->

        {{-- TAB 3: MOBILE WALLETS --}}
        <div x-show="activeTab === 'mobile'" x-transition class="space-y-4">
            <div class="p-4 bg-emerald-50/60 border border-emerald-100 rounded-2xl flex items-start space-x-3 text-xs text-emerald-900">
                <span class="text-base">📱</span>
                <p>
                    Send instant zero-fee support directly through your mobile wallet apps (EasyPaisa, JazzCash, Raast, Zelle, Venmo):
                </p>
            </div>

            <!-- EDIT PAYMENT DETAILS HERE: Mobile Wallet Numbers -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- EasyPaisa --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-bold text-slate-800">EasyPaisa</span>
                        </div>
                        <span class="text-sm font-mono font-bold text-slate-900 mt-1 block">click to Copy</span>
                        <span class="text-[11px] text-slate-500 font-medium">Title: The Eternal Echo</span>
                    </div>
                    <button type="button" @click="copyText('03415887295', 'EasyPaisa number copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>

                {{-- JazzCash --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span class="text-xs font-bold text-slate-800">JazzCash</span>
                        </div>
                        <span class="text-sm font-mono font-bold text-slate-900 mt-1 block">click to Copy</span>
                        <span class="text-[11px] text-slate-500 font-medium">Title: The Eternal Echo</span>
                    </div>
                    <button type="button" @click="copyText('03028057225', 'JazzCash number copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>

                {{-- Raast Instant Pay --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span class="text-xs font-bold text-slate-800">Raast ID (Instant Interbank)</span>
                        </div>
                        <span class="text-sm font-mono font-bold text-slate-900 mt-1 block">click to Copy</span>
                        <span class="text-[11px] text-slate-500 font-medium">Instant transfer from any banking app</span>
                    </div>
                    <button type="button" @click="copyText('03415887295', 'Raast ID copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>

                {{-- International Zelle / Wise --}}
                <!--<div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                            <span class="text-xs font-bold text-slate-800">Wise / Zelle Transfer</span>
                        </div>
                        <span class="text-sm font-mono font-bold text-slate-900 mt-1 block">donate@asloobulhayat.com</span>
                        <span class="text-[11px] text-slate-500 font-medium">Recipient: Asloob ul Hayat</span>
                    </div>
                    <button type="button" @click="copyText('donate@asloobulhayat.com', 'Wise/Zelle email copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>-->
            </div>
        </div>

        {{-- TAB 4: CRYPTO --}}
        <div x-show="activeTab === 'crypto'" x-transition class="space-y-4">
            <div class="p-4 bg-slate-100 border border-slate-200 rounded-2xl flex items-start space-x-3 text-xs text-slate-700">
                <span class="text-base">🪙</span>
                <p>
                    For international donors wishing to support anonymously using digital assets. Please verify the network before sending.
                </p>
            </div>

            <!-- EDIT PAYMENT DETAILS HERE: Crypto Wallets -->
            <div class="space-y-3">
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase text-emerald-600 tracking-wider block">USDT (TRC20 Network - Recommended)</span>
                        <span class="text-xs font-mono font-bold text-slate-900 break-all">TXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX</span>
                    </div>
                    <button type="button" @click="copyText('TXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', 'USDT TRC20 address copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm ml-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase text-amber-600 tracking-wider block">Bitcoin (BTC)</span>
                        <span class="text-xs font-mono font-bold text-slate-900 break-all">1XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX</span>
                    </div>
                    <button type="button" @click="copyText('1XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', 'Bitcoin address copied!')" class="p-2 hover:bg-white rounded-xl text-slate-500 hover:text-emerald-600 transition-colors shadow-sm ml-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </button>
                </div>
            </div>
    </div>

    {{-- Recent Verified Community Supporters Wall --}}
    @if(isset($recentSupporters) && $recentSupporters->isNotEmpty())
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm mb-14">
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full">Community Pillars</span>
                <h3 class="text-lg font-black text-slate-900 mt-1">Recent Knowledge Benefactors</h3>
            </div>
            <span class="text-xs text-slate-400">Sadaqah Jariyah in action</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($recentSupporters as $supporter)
                <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-3.5 flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl {{ $supporter->is_anonymous ? 'bg-slate-200 text-slate-600' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center font-bold text-sm shrink-0">
                        {{ $supporter->is_anonymous ? '🤲' : '✨' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-slate-800 truncate">
                            {{ $supporter->public_donor_name }}
                        </h4>
                        <div class="flex items-center space-x-2 text-[10px] text-slate-500">
                            <span>{{ $supporter->created_at->diffForHumans() }}</span>
                            @if($supporter->amount)
                                <span>•</span>
                                <span class="font-bold text-emerald-600">{{ $supporter->currency }} {{ number_format($supporter->amount) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Optional Contribution Notice Form --}}
    <div class="bg-slate-900 border border-emerald-500/30 rounded-3xl p-8 sm:p-10 text-white shadow-xl mb-14">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-6">
                <span class="text-emerald-400 text-xs font-bold uppercase tracking-widest">Acknowledgment & Record</span>
                <h3 class="text-2xl font-black text-white mt-1">Let Us Know About Your Gift</h3>
                <p class="text-xs text-slate-400 mt-1">
                    If you sent a direct wire transfer or mobile payment, please let us know so we can confirm receipt, acknowledge your contribution, and make du'a for you.
                </p>
            </div>

            <form action="{{ route('donate.notify') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold uppercase text-slate-400">Your Name (Optional)</label>
                            <span class="text-[10px] text-emerald-400 font-medium">Add name or stay hidden</span>
                        </div>
                        <input type="text" name="donor_name" value="{{ auth()->user()->display_name ?? auth()->user()->name ?? old('donor_name') }}" placeholder="e.g. Abdullah or your initials"
                               class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                        
                        {{-- Hide Name / Stay Anonymous Option --}}
                        <div class="mt-2.5 bg-slate-800/50 border border-slate-700/60 rounded-xl p-2.5 flex items-start space-x-2.5">
                            <input type="checkbox" id="is_anonymous" name="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }}
                                   class="mt-0.5 rounded bg-slate-900 border-slate-600 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-slate-900 w-4 h-4 cursor-pointer">
                            <label for="is_anonymous" class="text-xs text-slate-300 font-medium cursor-pointer leading-tight">
                                <span class="font-bold text-white block">Hide my name publicly</span>
                                <span class="text-[11px] text-slate-400">Display as "Anonymous Supporter (فاعل خير)". Your name remains private with administration for verification.</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Your Email (Optional)</label>
                        <input type="email" name="donor_email" value="{{ auth()->user()->email ?? old('donor_email') }}" placeholder="For confirmation receipt"
                               class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                        <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                            Used only to send you a private acknowledgment receipt and prayer update. Never shared or sold.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Amount Given</label>
                        <input type="number" step="0.01" name="amount" x-model="selectedAmount" placeholder="e.g. 25"
                               class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Currency</label>
                        <select name="currency" class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
                            <option value="USD">USD ($)</option>
                            <option value="PKR">PKR (Rs)</option>
                            <option value="GBP">GBP (£)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="CAD">CAD ($)</option>
                            <option value="SAR">SAR (﷼)</option>
                            <option value="AED">AED (د.إ)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="paypal">PayPal</option>
                            <option value="stripe">Credit / Debit Card</option>
                            <option value="easypaisa">EasyPaisa</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="raast">Raast Instant</option>
                            <option value="wise">Wise / Zelle</option>
                            <option value="crypto">Cryptocurrency</option>
                            <option value="other">Other Channel</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Transaction Reference / Sender Notes (Optional)</label>
                    <input type="text" name="transaction_reference" placeholder="e.g. Ref #12345678 or sender account initials"
                           class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Special Du'a Request / Message (Optional)</label>
                    <textarea name="message" rows="2" placeholder="Share a du'a or intention for yourself, family, or the Ummah..."
                              class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 resize-none"></textarea>
                </div>

                <div class="pt-2 text-center">
                    <button type="submit" 
                            class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold text-xs rounded-full shadow-lg shadow-emerald-500/25 transition-all transform hover:-translate-y-0.5">
                        Submit Contribution Notification ✨
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Frequently Asked Questions --}}
    <div class="mb-14">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Frequently Asked Questions</h2>
        </div>

        <div class="space-y-4 max-w-3xl mx-auto">
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <h4 class="text-sm font-bold text-slate-900 mb-1">Is supporting this platform considered Sadaqah Jariyah?</h4>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Yes, absolutely insha'Allah. Because this platform teaches the Holy Quran, prophetic traditions, authentic Duas, and Islamic history freely to students across the globe, every verse read and every quiz solved creates continuous spiritual reward for those who support its continuity.
                </p>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <h4 class="text-sm font-bold text-slate-900 mb-1">Can I donate and choose to hide my name?</h4>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Yes, absolutely. You can provide your name for internal verification and simply tick <strong>"Hide my name publicly"</strong>. Your contribution will be displayed as <em>"Anonymous Supporter (فاعل خير)"</em>, or you can omit your name completely. Allah Ta'ala knows your sincere intention and rewards the hidden deed abundantly.
                </p>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <h4 class="text-sm font-bold text-slate-900 mb-1">Will this project ever introduce advertisements or paid paywalls?</h4>
                <p class="text-xs text-slate-500 leading-relaxed">
                    No. Our core philosophy is to keep the word of Allah free from commercialization, trackers, and distracting advertisements. Your donations are precisely what keeps this platform independent and ad-free.
                </p>
            </div>
        </div>
    </div>

    {{-- Copy Toast Notification --}}
    <div x-show="toastMessage" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 bg-slate-900 text-white text-xs font-bold px-5 py-2.5 rounded-full shadow-2xl border border-emerald-500/40 flex items-center space-x-2"
         x-cloak>
        <span class="text-emerald-400">✓</span>
        <span x-text="toastMessage"></span>
    </div>

</div>

<script>
function donationPage() {
    return {
        activeTab: 'bank',
        selectedAmount: '25',
        toastMessage: '',

        copyText(text, label) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            } else {
                const el = document.createElement('textarea');
                el.value = text;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
            }
            this.toastMessage = label || 'Copied to clipboard!';
            setTimeout(() => {
                this.toastMessage = '';
            }, 2500);
        }
    };
}
</script>
@endsection
