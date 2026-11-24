@extends('layouts.app')

@section('title', 'Admin Panel - Scholars')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Scholars</h1>
            <p class="text-slate-500 mt-1">Add as many scholars as you need, then publish or hide each profile.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('scholars.onboarding') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-xl">Add a scholar</a>
            <a href="{{ route('scholars.index') }}" target="_blank" rel="noopener" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm rounded-xl">Public directory ↗</a>
        </div>
    </div>

    @include('admin.partials.tabs')

    <div class="grid grid-cols-2 gap-3 mb-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <p class="text-xs font-semibold text-slate-500">Published</p>
            <p class="text-3xl font-black text-slate-900 mt-1">{{ number_format($stats['published']) }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <p class="text-xs font-semibold text-slate-500">Waiting for review</p>
            <p class="text-3xl font-black text-slate-900 mt-1">{{ number_format($stats['pending']) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.scholars.index') }}" class="mb-6 p-4 bg-white border border-slate-200 rounded-2xl flex flex-wrap gap-3 items-center">
        <div class="flex rounded-xl bg-slate-100 p-1 text-xs font-bold">
            @foreach(['all' => 'Everyone', 'pending' => 'Waiting', 'published' => 'Published'] as $value => $label)
                <a href="{{ route('admin.scholars.index', array_filter(['status' => $value === 'all' ? null : $value, 'q' => request('q')])) }}"
                    class="px-3 py-1.5 rounded-lg {{ $status === $value ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">{{ $label }}</a>
            @endforeach
        </div>
        @if($status !== 'all')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, field, or location"
            class="flex-1 min-w-[12rem] rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-bold">Search</button>
    </form>

    <div class="space-y-4">
        @forelse($scholars as $scholar)
            <article class="bg-white border border-slate-200 rounded-[1.5rem] p-5 flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div class="space-y-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-black text-slate-900">{{ $scholar->displayName() }}</h2>
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full {{ $scholar->isPublished() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">
                            {{ $scholar->isPublished() ? 'Published' : 'Pending' }}
                        </span>
                    </div>
                    <p class="text-sm text-emerald-700 font-bold">{{ $scholar->expertise }}</p>
                    <p class="text-xs text-slate-500 line-clamp-2">{{ $scholar->credentials }}</p>
                    @if($scholar->user)
                        <p class="text-[11px] text-slate-400">Submitted by {{ $scholar->user->email }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    <a href="{{ route('scholars.show', $scholar) }}" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-black uppercase">View</a>
                    <a href="{{ route('admin.scholars.edit', $scholar) }}" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-black uppercase">Edit</a>
                    <form method="POST" action="{{ route('admin.scholars.publish', $scholar) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-3 py-2 rounded-xl bg-emerald-600 text-white text-xs font-black uppercase">
                            {{ $scholar->isPublished() ? 'Hide' : 'Publish' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.scholars.destroy', $scholar) }}" onsubmit="return confirm('Remove this scholar?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-2 rounded-xl bg-rose-50 text-rose-700 text-xs font-black uppercase">Remove</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="bg-white border border-slate-200 rounded-2xl p-8 text-sm text-slate-500">
                No scholars yet. Use Add a scholar to onboard the first one.
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $scholars->links() }}</div>
</div>
@endsection
