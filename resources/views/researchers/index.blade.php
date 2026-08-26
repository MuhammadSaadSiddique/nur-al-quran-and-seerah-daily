@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-12 space-y-12">
    {{-- Header Card --}}
    <div class="relative overflow-hidden bg-slate-900 rounded-[2.5rem] p-8 md:p-12 text-white shadow-2xl border border-slate-800">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-800/30 to-indigo-900/30 mix-blend-multiply"></div>
        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-emerald-500/10 rounded-full blur-3xl"></div>
        
        <div class="relative z-10 max-w-3xl space-y-4">
            <span class="text-xs font-black uppercase tracking-widest text-emerald-400 bg-emerald-950/60 border border-emerald-900 px-3 py-1 rounded-full">
                Directory
            </span>
            <h1 class="text-3xl md:text-5xl font-black tracking-tight leading-none">
                Quranic Research Team
            </h1>
            <p class="text-slate-300 text-sm md:text-base leading-relaxed">
                Meet our dedicated panel of researchers, scholars, and students of knowledge. Together, we review, verify, and tag literary, seerah, and science perspectives to create a collaborative map of Quranic wisdom.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-6 py-4 rounded-3xl text-sm font-bold flex items-center gap-3">
            <span>🎉</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Grid Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Researchers Directory --}}
        <div class="lg:col-span-2 space-y-6">
            <h2 class="text-xl font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                <span>👥</span> Verified Researchers ({{ $researchers->count() }})
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                @foreach($researchers as $researcher)
                    @php
                        $initials = strtoupper(substr($researcher->display_name ?: $researcher->email, 0, 2));
                        $isCurrent = auth()->check() && auth()->id() === $researcher->id;
                    @endphp
                    <div class="bg-white rounded-[2rem] border border-slate-100 p-6 shadow-sm flex items-center gap-4 hover:border-emerald-500/20 transition-all {{ $isCurrent ? 'ring-2 ring-emerald-500 ring-offset-2' : '' }}">
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center text-white font-black text-sm shrink-0">
                            {{ $initials }}
                        </div>
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h4 class="font-black text-slate-800 text-sm truncate">
                                    {{ $researcher->display_name ?: explode('@', $researcher->email)[0] }}
                                </h4>
                                @if($isCurrent)
                                    <span class="text-[9px] font-black uppercase bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full border border-emerald-100 shrink-0">You</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400 truncate">{{ $researcher->email }}</p>
                            <div class="flex flex-wrap gap-1.5 mt-1">
                                @if($researcher->is_admin)
                                    <span class="text-[9px] font-black uppercase bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full border border-indigo-100">Admin</span>
                                @endif
                                @if($researcher->is_researcher)
                                    <span class="text-[9px] font-black uppercase bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full border border-emerald-100">Researcher</span>
                                @endif
                                @if($researcher->expertCategory)
                                    <span class="text-[9px] font-black uppercase bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full border border-purple-100">
                                        Expert: {{ $researcher->expertCategory->emoji }} {{ $researcher->expertCategory->name }}
                                    </span>
                                @endif
                            </div>

                            @if(auth()->check() && auth()->user()->is_admin)
                                <form action="{{ route('researchers.update-expert', $researcher->id) }}" method="POST" class="mt-2 flex items-center gap-1">
                                    @csrf
                                    <select name="expert_category_id" class="text-[10px] font-bold p-1 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 focus:outline-none">
                                        <option value="">-- No Specialty --</option>
                                        @foreach($scienceCategories as $cat)
                                            <option value="{{ $cat->id }}" {{ $researcher->expert_category_id == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->emoji }} {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="text-[9px] font-black uppercase bg-indigo-600 hover:bg-indigo-700 text-white px-2 py-1 rounded-lg">
                                        Set Expert
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Call To Action Panel --}}
        <div class="space-y-6">
            <h2 class="text-xl font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                <span>✨</span> Join the Mission
            </h2>

            <div class="bg-white rounded-[2.5rem] border border-slate-100 p-8 shadow-sm space-y-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl"></div>

                <h3 class="font-black text-slate-800 text-lg">Help Tag & Verify Quranic Lens Content</h3>
                <p class="text-slate-500 text-xs leading-relaxed">
                    By joining as a researcher, you will gain access to the Approvals Dashboard. You'll be able to review, approve, or reject user-submitted analyses, word-by-word linguistic tags, and verse cross-references.
                </p>

                <div class="space-y-4 border-t border-slate-50 pt-6">
                    <div class="flex items-start gap-3">
                        <span class="text-emerald-500 mt-0.5">✓</span>
                        <div>
                            <h5 class="text-xs font-black text-slate-700">Approve Analyses</h5>
                            <p class="text-[11px] text-slate-400 leading-normal">Validate Tafsir, Seerah, and Science analyses for the public feed.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-emerald-500 mt-0.5">✓</span>
                        <div>
                            <h5 class="text-xs font-black text-slate-700">Tag Arabic Words</h5>
                            <p class="text-[11px] text-slate-400 leading-normal">Assign linguistic terminologies directly to individual words.</p>
                        </div>
                    </div>
                </div>

                @auth
                    @if(auth()->user()->is_researcher || auth()->user()->is_admin)
                        <div class="bg-slate-50 rounded-2xl p-4 text-center border border-slate-100">
                            <span class="text-xs font-black text-slate-600">You are already a member of the research team.</span>
                        </div>
                    @else
                        <form action="{{ route('researchers.join') }}" method="POST" class="pt-2">
                            @csrf
                            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 px-6 rounded-2xl text-xs font-black uppercase tracking-widest shadow-md hover:shadow-lg transition-all duration-300">
                                Join as Researcher
                            </button>
                        </form>
                    @endif
                @else
                    <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl p-4 text-center text-xs font-bold">
                        Please <a href="{{ route('login') }}" class="underline hover:text-emerald-700">login</a> to join our team of researchers.
                    </div>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection
