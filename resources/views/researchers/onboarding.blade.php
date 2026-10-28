@extends('layouts.app')

@section('title', 'Researcher Onboarding')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12 space-y-8">
    <div class="relative overflow-hidden bg-slate-900 rounded-[2.5rem] p-8 md:p-10 text-white shadow-2xl border border-slate-800">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-800/30 to-indigo-900/30 mix-blend-multiply"></div>
        <div class="relative z-10 space-y-3">
            <span class="text-xs font-black uppercase tracking-widest text-emerald-400 bg-emerald-950/60 border border-emerald-900 px-3 py-1 rounded-full">
                Onboarding
            </span>
            <h1 class="text-3xl md:text-4xl font-black tracking-tight leading-none">Researcher onboarding plan</h1>
            <p class="text-slate-300 text-sm md:text-base leading-relaxed">
                Enter your details, name the sources you will check, and confirm that you will not approve anything unresearched or unauthentic. Submitting this form records your pledge. An administrator still grants researcher access.
            </p>
        </div>
    </div>

    <ol class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <li class="bg-white rounded-2xl border border-slate-100 p-4 text-sm">
            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Step 1</span>
            <p class="font-black text-slate-800 mt-1">Your details</p>
            <p class="text-xs text-slate-500 mt-1">Name, qualifications, and languages.</p>
        </li>
        <li class="bg-white rounded-2xl border border-slate-100 p-4 text-sm">
            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Step 2</span>
            <p class="font-black text-slate-800 mt-1">Your field</p>
            <p class="text-xs text-slate-500 mt-1">Expertise and the background you review from.</p>
        </li>
        <li class="bg-white rounded-2xl border border-slate-100 p-4 text-sm">
            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Step 3</span>
            <p class="font-black text-slate-800 mt-1">Your sources</p>
            <p class="text-xs text-slate-500 mt-1">What you will check before anything is approved.</p>
        </li>
        <li class="bg-white rounded-2xl border border-slate-100 p-4 text-sm">
            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Step 4</span>
            <p class="font-black text-slate-800 mt-1">Authenticity pledge</p>
            <p class="text-xs text-slate-500 mt-1">Confirm you will not approve unresearched or unauthentic content.</p>
        </li>
    </ol>

    @if($onboarding)
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-6 py-4 rounded-3xl text-sm font-bold">
            Pledge recorded {{ $onboarding->pledged_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}.
            @if(!auth()->user()->is_researcher && !auth()->user()->is_admin)
                An administrator still needs to grant researcher access.
            @endif
        </div>
    @endif

    <form action="{{ route('researchers.onboarding.store') }}" method="POST" class="bg-white rounded-[2.5rem] border border-slate-100 p-8 shadow-sm space-y-6">
        @csrf

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl text-sm">
                <p class="font-black">Please complete every required step.</p>
                <ul class="mt-2 list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-2">
            <label for="full_name" class="text-xs font-black uppercase tracking-widest text-slate-600">Full name</label>
            <input id="full_name" name="full_name" type="text" required maxlength="120"
                value="{{ old('full_name', $onboarding->full_name ?? auth()->user()->display_name ?? auth()->user()->name) }}"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div class="space-y-2">
            <label for="credentials" class="text-xs font-black uppercase tracking-widest text-slate-600">Qualifications and credentials</label>
            <textarea id="credentials" name="credentials" required maxlength="2000" rows="4"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                placeholder="Studies, teachers, institutions, or relevant experience">{{ old('credentials', $onboarding->credentials ?? '') }}</textarea>
        </div>

        <div class="space-y-2">
            <label for="expertise" class="text-xs font-black uppercase tracking-widest text-slate-600">Area of expertise</label>
            <input id="expertise" name="expertise" type="text" required maxlength="255"
                value="{{ old('expertise', $onboarding->expertise ?? '') }}"
                placeholder="Example: Seerah, tafsir, Arabic linguistics"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div class="space-y-2">
            <label for="requested_expert_category_id" class="text-xs font-black uppercase tracking-widest text-slate-600">Requested specialty</label>
            <select id="requested_expert_category_id" name="requested_expert_category_id"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">No specialty requested</option>
                @foreach($scienceCategories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('requested_expert_category_id', $onboarding->requested_expert_category_id ?? '') === (string) $category->id)>
                        {{ $category->emoji }} {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <p class="text-[11px] text-slate-400">An administrator assigns the specialty used to limit your approval queue.</p>
        </div>

        <div class="space-y-2">
            <label for="background" class="text-xs font-black uppercase tracking-widest text-slate-600">Background</label>
            <textarea id="background" name="background" required maxlength="3000" rows="4"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                placeholder="How you study, and what kinds of submissions you are prepared to review">{{ old('background', $onboarding->background ?? '') }}</textarea>
        </div>

        <div class="space-y-2">
            <label for="languages" class="text-xs font-black uppercase tracking-widest text-slate-600">Languages <span class="text-slate-400">(optional)</span></label>
            <input id="languages" name="languages" type="text" maxlength="255"
                value="{{ old('languages', $onboarding->languages ?? '') }}"
                placeholder="Arabic, English, Urdu"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div class="space-y-2">
            <label for="sources" class="text-xs font-black uppercase tracking-widest text-slate-600">Sources you will check before approving</label>
            <textarea id="sources" name="sources" required maxlength="2000" rows="4"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                placeholder="Quran, hadith collections, tafsir, seerah works, or scientific references you rely on">{{ old('sources', $onboarding->sources ?? '') }}</textarea>
        </div>

        <label class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 cursor-pointer">
            <input type="checkbox" name="authenticity_pledge" value="1" class="mt-1 h-4 w-4 rounded border-amber-300 text-emerald-600 focus:ring-emerald-500"
                @checked(old('authenticity_pledge'))>
            <span class="text-sm text-slate-800 leading-relaxed">
                <span class="font-black block mb-1">Authenticity pledge</span>
                {{ \App\Models\ResearcherOnboarding::PLEDGE }}
            </span>
        </label>

        <div class="flex flex-col sm:flex-row gap-3 pt-2">
            <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 px-6 rounded-2xl text-xs font-black uppercase tracking-widest shadow-md">
                {{ $onboarding ? 'Update details and reconfirm pledge' : 'Submit onboarding and pledge' }}
            </button>
            <a href="{{ route('researchers.index') }}" class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 py-3.5 px-6 rounded-2xl text-xs font-black uppercase tracking-widest">
                Back to researchers
            </a>
        </div>
    </form>
</div>
@endsection
