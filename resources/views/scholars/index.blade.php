@extends('layouts.app')

@section('title', 'Scholars')
@section('meta_description', 'Meet the scholars listed on The Eternal Echo, with their fields, sources, and published works.')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-12 space-y-12">
    <div class="relative overflow-hidden bg-slate-900 rounded-[2.5rem] p-8 md:p-12 text-white shadow-2xl border border-slate-800">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-800/30 to-indigo-900/30 mix-blend-multiply"></div>
        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-emerald-500/10 rounded-full blur-3xl"></div>

        <div class="relative z-10 max-w-3xl space-y-4">
            <span class="text-xs font-black uppercase tracking-widest text-emerald-400 bg-emerald-950/60 border border-emerald-900 px-3 py-1 rounded-full">
                Directory
            </span>
            <h1 class="text-3xl md:text-5xl font-black tracking-tight leading-none">Scholars</h1>
            <p class="text-slate-300 text-sm md:text-base leading-relaxed">
                A growing list of scholars, their fields, the sources they are known for, and the books and fatwas connected with their work.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <h2 class="text-xl font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                <span>🎓</span> Published scholars ({{ $scholars->count() }})
            </h2>

            @if($scholars->isEmpty())
                <div class="bg-white rounded-[2rem] border border-slate-100 p-8 text-sm text-slate-500">
                    No scholars have been published yet. Onboarding is open, and each completed profile can be added to this list.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach($scholars as $scholar)
                        @php
                            $initials = strtoupper(substr($scholar->full_name, 0, 2));
                        @endphp
                        <a href="{{ route('scholars.show', $scholar) }}" class="bg-white rounded-[2rem] border border-slate-100 p-6 shadow-sm flex items-start gap-4 hover:border-emerald-500/30 transition-all">
                            <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center text-white font-black text-sm shrink-0">
                                {{ $initials }}
                            </div>
                            <div class="space-y-1 min-w-0">
                                <h3 class="font-black text-slate-800 text-sm">{{ $scholar->displayName() }}</h3>
                                <p class="text-xs text-emerald-700 font-bold">{{ $scholar->expertise }}</p>
                                @if($scholar->location)
                                    <p class="text-[11px] text-slate-400">{{ $scholar->location }}</p>
                                @endif
                                @if($scholar->languages)
                                    <p class="text-[11px] text-slate-500">{{ $scholar->languages }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <h2 class="text-xl font-black text-slate-800 uppercase tracking-widest">Onboarding</h2>
            <div class="bg-white rounded-[2.5rem] border border-slate-100 p-8 shadow-sm space-y-5">
                <h3 class="font-black text-slate-800 text-lg">Add a scholar</h3>
                <p class="text-slate-500 text-xs leading-relaxed">
                    Onboarding records a scholar's name, qualifications, field, sources, and works. Administrators can submit the form once for every scholar they want to list.
                </p>
                <ul class="space-y-3 text-xs text-slate-600">
                    <li class="flex gap-2"><span class="text-emerald-500">✓</span> Name, title, and qualifications</li>
                    <li class="flex gap-2"><span class="text-emerald-500">✓</span> Field, sources, and notable works</li>
                    <li class="flex gap-2"><span class="text-emerald-500">✓</span> A confirmation that the profile stays authentic</li>
                </ul>

                @auth
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('scholars.onboarding') }}" class="block w-full text-center bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 px-6 rounded-2xl text-xs font-black uppercase tracking-widest shadow-md">
                            Add a scholar
                        </a>
                        <a href="{{ route('admin.scholars.index') }}" class="block w-full text-center bg-slate-100 hover:bg-slate-200 text-slate-700 py-3.5 px-6 rounded-2xl text-xs font-black uppercase tracking-widest">
                            Manage scholars
                            @if($pendingCount > 0)
                                ({{ $pendingCount }} waiting)
                            @endif
                        </a>
                    @elseif($mine)
                        <div class="bg-slate-50 rounded-2xl p-4 text-center border border-slate-100 space-y-3">
                            <span class="text-xs font-black text-slate-600 block">
                                {{ $mine->isPublished() ? 'Your scholar profile is published.' : 'Your profile is saved and waiting for review.' }}
                            </span>
                            <a href="{{ route('scholars.onboarding') }}" class="inline-block text-[11px] font-black uppercase tracking-widest text-emerald-700 underline">Update your details</a>
                        </div>
                    @else
                        <a href="{{ route('scholars.onboarding') }}" class="block w-full text-center bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 px-6 rounded-2xl text-xs font-black uppercase tracking-widest shadow-md">
                            Start scholar onboarding
                        </a>
                        <p class="text-[11px] text-slate-500 text-center leading-normal">
                            Your profile stays hidden until an administrator publishes it.
                        </p>
                    @endif
                @else
                    <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl p-4 text-center text-xs font-bold">
                        Please <a href="{{ route('login') }}" class="underline">login</a> to add a scholar.
                    </div>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection
