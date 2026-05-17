@extends('layouts.app')
@section('title', 'Global Rankings - The Eternal Echo')
@section('meta_description', 'See where you stand in the global community of knowledge seekers. Celebrate the top performers on our spiritual leaderboard.')

@section('content')
    <div class="space-y-12 pb-10 animate-fadeIn">
        {{-- Header --}}
        <div class="text-center space-y-4">
            <div
                class="inline-flex items-center space-x-2 bg-emerald-100 text-emerald-700 px-4 py-1.5 rounded-full border border-emerald-200">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
                <span class="text-xs font-black uppercase tracking-widest">Hall of Seekers</span>
            </div>
            <h2 class="text-3xl font-extrabold text-slate-900 md:text-5xl">Global Rankings</h2>
            <p class="text-slate-600 font-medium max-w-lg mx-auto italic">"And for this let those who aspire, aspire." —
                Surah Al-Mutaffifin</p>

            {{-- Period Toggle --}}
            <div class="flex justify-center pt-4">
                <div class="inline-flex bg-slate-100 p-1.5 rounded-2xl shadow-inner">
                    <a href="{{ route('leaderboard', ['period' => 'lifetime']) }}" 
                       class="px-8 py-2.5 rounded-xl text-sm font-black transition-all {{ $period === 'lifetime' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                        Lifetime
                    </a>
                    <a href="{{ route('leaderboard', ['period' => 'weekly']) }}" 
                       class="px-8 py-2.5 rounded-xl text-sm font-black transition-all {{ $period === 'weekly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                        This Week
                    </a>
                </div>
            </div>

            @if($currentUser && $currentUser->quran_access_token)
            <div class="flex justify-center mt-4">
                <div class="inline-flex items-center gap-2 bg-[#114030]/10 text-[#114030] px-4 py-2 rounded-xl border border-[#114030]/20">
                    <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/></svg>
                    <span class="text-sm font-bold">Quran.com Streak: <span class="font-black">{{ $quranStreak ?? 0 }} Days</span></span>
                </div>
            </div>
            @else
            <div class="flex justify-center mt-4">
                <a href="{{ route('quran.redirect') }}" class="inline-flex items-center gap-2 text-emerald-600 hover:text-emerald-800 text-xs font-bold transition-colors">
                    Link Quran.com to track your streaks
                </a>
            </div>
            @endif
        </div>

        {{-- Podium --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end max-w-5xl mx-auto px-4">
            {{-- Second Place --}}
            @if(count($topUsers) >= 2)
                <div class="order-2 md:order-1 group">
                    <div
                        class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 relative text-center space-y-6 transition-all hover:-translate-y-2">
                        <div
                            class="absolute -top-6 left-1/2 -translate-x-1/2 w-12 h-12 bg-slate-200 rounded-full border-4 border-white flex items-center justify-center text-slate-500 font-black">
                            2</div>
                        <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-slate-100 to-slate-200 p-1">
                            <div
                                class="w-full h-full rounded-full bg-white flex items-center justify-center text-3xl font-black text-slate-400">
                                {{ strtoupper(substr($topUsers[1]->display_name ?: $topUsers[1]->email, 0, 1)) }}
                            </div>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-900 truncate">
                                {{ $topUsers[1]->display_name ?: explode('@', $topUsers[1]->email)[0] }}</h4>
                            <span
                                class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ $topUsers[1]->spiritual_level }}</span>
                        </div>
                        <div class="pt-4 border-t border-slate-100">
                            <span
                                class="text-2xl font-black text-slate-800">{{ number_format($topUsers[1]->display_score) }}</span>
                            <p class="text-[10px] font-black uppercase text-slate-400">Total Score</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- First Place --}}
            @if(count($topUsers) >= 1)
                <div class="order-1 md:order-2 group">
                    <div
                        class="bg-indigo-900 rounded-[3rem] p-10 shadow-2xl relative text-center space-y-8 transition-all hover:-translate-y-4 border-4 border-indigo-800">
                        <div
                            class="absolute -top-8 left-1/2 -translate-x-1/2 w-16 h-16 bg-amber-400 rounded-full border-4 border-indigo-900 flex items-center justify-center text-white shadow-lg">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                        </div>
                        <div
                            class="w-32 h-32 mx-auto rounded-full bg-gradient-to-br from-amber-200 via-amber-400 to-amber-500 p-1.5 shadow-2xl">
                            <div
                                class="w-full h-full rounded-full bg-indigo-950 flex items-center justify-center text-5xl font-black text-amber-400 shadow-inner">
                                {{ strtoupper(substr($topUsers[0]->display_name ?: $topUsers[0]->email, 0, 1)) }}
                            </div>
                        </div>
                        <div class="space-y-2">
                            <h4 class="text-2xl font-black text-white truncate">
                                {{ $topUsers[0]->display_name ?: explode('@', $topUsers[0]->email)[0] }}</h4>
                            <div
                                class="inline-flex bg-amber-400/20 text-amber-400 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-amber-400/30">
                                {{ $topUsers[0]->spiritual_level }}
                            </div>
                        </div>
                        <div class="pt-6 border-t border-indigo-800/50">
                            <span
                                class="text-5xl font-black text-white drop-shadow-lg">{{ number_format($topUsers[0]->display_score) }}</span>
                            <p class="text-[10px] font-black uppercase text-indigo-300 tracking-widest mt-2">{{ $period === 'weekly' ? 'Weekly Champion' : 'King of Knowledge' }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Third Place --}}
            @if(count($topUsers) >= 3)
                <div class="order-3 md:order-3 group">
                    <div
                        class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 relative text-center space-y-6 transition-all hover:-translate-y-2">
                        <div
                            class="absolute -top-6 left-1/2 -translate-x-1/2 w-12 h-12 bg-orange-100 rounded-full border-4 border-white flex items-center justify-center text-orange-600 font-black">
                            3</div>
                        <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-orange-50 to-orange-100 p-1">
                            <div
                                class="w-full h-full rounded-full bg-white flex items-center justify-center text-3xl font-black text-orange-300">
                                {{ strtoupper(substr($topUsers[2]->display_name ?: $topUsers[2]->email, 0, 1)) }}
                            </div>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-900 truncate">
                                {{ $topUsers[2]->display_name ?: explode('@', $topUsers[2]->email)[0] }}</h4>
                            <span
                                class="text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ $topUsers[2]->spiritual_level }}</span>
                        </div>
                        <div class="pt-4 border-t border-slate-100">
                            <span
                                class="text-2xl font-black text-slate-800">{{ number_format($topUsers[2]->display_score) }}</span>
                            <p class="text-[10px] font-black uppercase text-slate-400">Total Score</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Rest of Rankings --}}
        <div class="max-w-5xl mx-auto px-4 bg-white rounded-[3rem] shadow-xl border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-xl font-black text-slate-900">Rankings Listing</h3>
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Showing Top 100</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="px-8 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">Rank</th>
                            <th class="px-8 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">User</th>
                            <th class="px-8 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">Spiritual
                                Level</th>
                            <th
                                class="px-8 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">
                                Questions</th>
                            <th
                                class="px-8 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">
                                Accuracy</th>
                            <th
                                class="px-8 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest text-right">
                                {{ $period === 'weekly' ? 'Weekly Score' : 'Total Score' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($topUsers->slice(3) as $index => $user)
                            <tr
                                class="{{ Auth::id() === $user->id ? 'bg-indigo-50 border-l-4 border-indigo-500' : 'hover:bg-slate-50/50' }} transition-colors">
                                <td class="px-8 py-6 font-black text-slate-400">{{ $index + 4 }}</td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center space-x-4">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-400 text-sm">
                                            {{ strtoupper(substr($user->display_name ?: $user->email, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span
                                                class="block font-bold text-slate-900">{{ $user->display_name ?: explode('@', $user->email)[0] }}</span>
                                            @if(Auth::id() === $user->id)
                                                <span
                                                    class="inline-block bg-indigo-100 text-indigo-600 text-[8px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest">That's
                                                    You</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span
                                        class="bg-slate-100 text-slate-600 text-[9px] font-black px-2.5 py-1 rounded-lg uppercase tracking-widest">
                                        {{ $user->spiritual_level }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center font-bold text-slate-700">
                                    {{ number_format($user->total_questions) }}</td>
                                <td class="px-8 py-6 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <div class="w-12 bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500 rounded-full"
                                                style="width: {{ $user->accuracy }}%"></div>
                                        </div>
                                        <span class="text-xs font-black text-emerald-600">{{ $user->accuracy }}%</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <span
                                        class="text-lg font-black text-slate-900">{{ number_format($user->display_score) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- User's Current Rank (If not in top 100) --}}
        @if($currentUserRank > 100)
            <div class="max-w-5xl mx-auto px-4">
                <div
                    class="bg-indigo-900 rounded-[2rem] p-8 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl border border-white/10 relative overflow-hidden">
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -translate-y-1/2 translate-x-1/2">
                        </div>
                    </div>
                    <div class="relative z-10 flex items-center space-x-6 text-center md:text-left">
                        <div
                            class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-2xl font-black">
                            #{{ $currentUserRank }}
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-xl font-black">Your Progress</h3>
                            <p class="text-indigo-200 text-sm">Keep seeking knowledge to climb the global rankings!</p>
                        </div>
                    </div>
                    <div
                        class="relative z-10 flex items-center space-x-12 px-8 py-4 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/10">
                        <div class="text-center">
                            <span class="block text-2xl font-black">{{ number_format($currentUser->display_score) }}</span>
                            <span class="text-[9px] font-black uppercase text-indigo-300 tracking-widest">Your Score</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-2xl font-black">{{ $currentUser->accuracy }}%</span>
                            <span class="text-[9px] font-black uppercase text-indigo-300 tracking-widest">Accuracy</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection