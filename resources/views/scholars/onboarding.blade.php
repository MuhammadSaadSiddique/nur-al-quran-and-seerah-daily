@extends('layouts.app')

@section('title', $mode === 'edit' ? 'Edit scholar' : 'Scholar onboarding')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12 space-y-8">
    <div class="relative overflow-hidden bg-slate-900 rounded-[2.5rem] p-8 md:p-10 text-white shadow-2xl border border-slate-800">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-800/30 to-indigo-900/30 mix-blend-multiply"></div>
        <div class="relative z-10 space-y-3">
            <span class="text-xs font-black uppercase tracking-widest text-emerald-400 bg-emerald-950/60 border border-emerald-900 px-3 py-1 rounded-full">
                Onboarding
            </span>
            <h1 class="text-3xl md:text-4xl font-black tracking-tight leading-none">
                @if($mode === 'edit')
                    Edit scholar
                @elseif($mode === 'add')
                    Add a scholar
                @else
                    Scholar onboarding
                @endif
            </h1>
            <p class="text-slate-300 text-sm md:text-base leading-relaxed">
                @if($mode === 'add')
                    Fill in this scholar's details and confirm the profile. Each submission adds another scholar. You can add as many as you need.
                @elseif($mode === 'edit')
                    Update this scholar's details. The confirmation is required again so the published profile stays accurate.
                @else
                    Enter your details, the sources you rely on, and confirm this profile will not present unresearched or unauthentic material. An administrator publishes it after review.
                @endif
            </p>
        </div>
    </div>

    @if($mode === 'apply' && $scholar)
        <div class="bg-slate-50 border border-slate-200 text-slate-700 px-6 py-4 rounded-3xl text-sm">
            @if($scholar?->isPublished())
                Your profile is published. Saving here updates the public page.
            @else
                Your profile is saved and waiting for an administrator to publish it.
            @endif
        </div>
    @endif

    <form action="{{ $formAction }}" method="POST" class="bg-white rounded-[2.5rem] border border-slate-100 p-8 shadow-sm space-y-6">
        @csrf
        @if($formMethod !== 'POST')
            @method($formMethod)
        @endif

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl text-sm">
                <p class="font-black">Please complete every required field.</p>
                <ul class="mt-2 list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2 space-y-2">
                <label for="full_name" class="text-xs font-black uppercase tracking-widest text-slate-600">Full name</label>
                <input id="full_name" name="full_name" type="text" required maxlength="120"
                    value="{{ old('full_name', $scholar?->full_name ?? '') }}"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="space-y-2">
                <label for="title" class="text-xs font-black uppercase tracking-widest text-slate-600">Title <span class="text-slate-400">(optional)</span></label>
                <input id="title" name="title" type="text" maxlength="80"
                    value="{{ old('title', $scholar?->title ?? '') }}"
                    placeholder="Shaykh, Dr, Ustadh"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="space-y-2">
            <label for="credentials" class="text-xs font-black uppercase tracking-widest text-slate-600">Qualifications and credentials</label>
            <textarea id="credentials" name="credentials" required maxlength="2000" rows="4"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                placeholder="Studies, teachers, institutions, or the basis for this profile">{{ old('credentials', $scholar?->credentials ?? '') }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-2">
                <label for="expertise" class="text-xs font-black uppercase tracking-widest text-slate-600">Area of expertise</label>
                <input id="expertise" name="expertise" type="text" required maxlength="255"
                    value="{{ old('expertise', $scholar?->expertise ?? '') }}"
                    placeholder="Example: Tafsir, fiqh, Seerah"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="space-y-2">
                <label for="location" class="text-xs font-black uppercase tracking-widest text-slate-600">Location <span class="text-slate-400">(optional)</span></label>
                <input id="location" name="location" type="text" maxlength="120"
                    value="{{ old('location', $scholar?->location ?? '') }}"
                    placeholder="City or region"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="space-y-2">
            <label for="background" class="text-xs font-black uppercase tracking-widest text-slate-600">Background</label>
            <textarea id="background" name="background" required maxlength="3000" rows="4"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                placeholder="A short account of this scholar's work and focus">{{ old('background', $scholar?->background ?? '') }}</textarea>
        </div>

        <div class="space-y-2">
            <label for="languages" class="text-xs font-black uppercase tracking-widest text-slate-600">Languages <span class="text-slate-400">(optional)</span></label>
            <input id="languages" name="languages" type="text" maxlength="255"
                value="{{ old('languages', $scholar?->languages ?? '') }}"
                placeholder="Arabic, English, Urdu"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div class="space-y-2">
            <label for="sources" class="text-xs font-black uppercase tracking-widest text-slate-600">Sources used for this profile</label>
            <textarea id="sources" name="sources" required maxlength="2000" rows="3"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                placeholder="Books, teachers, or references this profile is based on">{{ old('sources', $scholar?->sources ?? '') }}</textarea>
        </div>

        <div class="space-y-2">
            <label for="works" class="text-xs font-black uppercase tracking-widest text-slate-600">Books, fatwas, or teachings <span class="text-slate-400">(optional)</span></label>
            <textarea id="works" name="works" maxlength="2000" rows="3"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                placeholder="Notable books, fatwas, or lessons associated with this scholar">{{ old('works', $scholar?->works ?? '') }}</textarea>
        </div>

        <label class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 cursor-pointer">
            <input type="checkbox" name="authenticity_pledge" value="1" class="mt-1 h-4 w-4 rounded border-amber-300 text-emerald-600 focus:ring-emerald-500"
                @checked(old('authenticity_pledge'))>
            <span class="text-sm text-slate-800 leading-relaxed">
                <span class="font-black block mb-1">Authenticity confirmation</span>
                {{ \App\Models\Scholar::PLEDGE }}
            </span>
        </label>

        <div class="flex flex-col sm:flex-row gap-3 pt-2">
            <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 px-6 rounded-2xl text-xs font-black uppercase tracking-widest shadow-md">
                @if($mode === 'edit')
                    Save scholar
                @elseif($mode === 'add')
                    Add this scholar
                @elseif($scholar)
                    Update scholar profile
                @else
                    Submit scholar profile
                @endif
            </button>
            <a href="{{ $mode === 'edit' ? route('admin.scholars.index') : route('scholars.index') }}" class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 py-3.5 px-6 rounded-2xl text-xs font-black uppercase tracking-widest">
                {{ $mode === 'edit' ? 'Back to manage scholars' : 'Back to scholars' }}
            </a>
        </div>
    </form>

    @if($mode === 'add' && $recentScholars->isNotEmpty())
        <div class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-500">Recently added</h2>
                <a href="{{ route('admin.scholars.index') }}" class="text-xs font-black uppercase tracking-widest text-emerald-700">Manage all</a>
            </div>
            <div class="bg-white rounded-[2rem] border border-slate-100 divide-y divide-slate-100">
                @foreach($recentScholars as $recent)
                    <div class="px-5 py-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-black text-slate-800">{{ $recent->displayName() }}</p>
                            <p class="text-xs text-slate-500">{{ $recent->expertise }}</p>
                        </div>
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full {{ $recent->isPublished() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">
                            {{ $recent->isPublished() ? 'Published' : 'Pending' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
