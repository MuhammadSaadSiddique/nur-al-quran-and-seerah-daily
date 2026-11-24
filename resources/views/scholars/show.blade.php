@extends('layouts.app')

@section('title', $scholar->displayName())
@section('meta_description', $scholar->expertise.' — '.$scholar->displayName().' on The Eternal Echo scholars directory.')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12 space-y-8">
    <a href="{{ route('scholars.index') }}" class="inline-flex text-xs font-black uppercase tracking-widest text-emerald-700 hover:underline">
        All scholars
    </a>

    @if(!$scholar->isPublished())
        <div class="bg-amber-50 border border-amber-200 text-amber-900 px-6 py-4 rounded-3xl text-sm font-bold">
            This profile is not public yet.
        </div>
    @endif

    <div class="bg-white rounded-[2.5rem] border border-slate-100 p-8 md:p-10 shadow-sm space-y-6">
        <div class="space-y-2">
            @if($scholar->title)
                <p class="text-xs font-black uppercase tracking-widest text-emerald-700">{{ $scholar->title }}</p>
            @endif
            <h1 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight">{{ $scholar->full_name }}</h1>
            <p class="text-sm font-bold text-slate-600">{{ $scholar->expertise }}</p>
            @if($scholar->location || $scholar->languages)
                <p class="text-xs text-slate-400">
                    {{ collect([$scholar->location, $scholar->languages])->filter()->implode(' · ') }}
                </p>
            @endif
        </div>

        <div class="space-y-2">
            <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Background</h2>
            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $scholar->background }}</p>
        </div>

        <div class="space-y-2">
            <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Qualifications</h2>
            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $scholar->credentials }}</p>
        </div>

        <div class="space-y-2">
            <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Sources</h2>
            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $scholar->sources }}</p>
        </div>

        @if($scholar->works)
            <div class="space-y-2">
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Books, fatwas, and teachings</h2>
                <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $scholar->works }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
