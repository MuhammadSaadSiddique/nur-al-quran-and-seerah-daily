@extends('layouts.app')

@section('title', 'Admin - Manage Themes')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Manage Themes</h1>
            <p class="text-slate-500 mt-1">Define and separate Quran and Seerah quiz themes.</p>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    @include('admin.partials.tabs')

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium animate-fadeIn">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- Quran Themes --}}
        <div class="space-y-6">
            <div class="bg-white/70 backdrop-blur-xl border border-slate-200 rounded-3xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="bg-sky-100 p-2 rounded-xl text-sky-700">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <h2 class="text-xl font-black text-slate-800">Quran Themes</h2>
                    </div>
                </div>

                <form action="{{ route('admin.themes.store') }}" method="POST" class="mb-6 flex gap-2">
                    @csrf
                    <input type="hidden" name="type" value="PARA">
                    <input type="text" name="name" placeholder="New Quran Theme..." class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-sky-500 transition-all font-medium" required>
                    <button type="submit" class="bg-sky-600 text-white px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-sky-700 transition-all">Add</button>
                </form>

                <div class="space-y-2">
                    @forelse($quranThemes as $theme)
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 group">
                            <form action="{{ route('admin.themes.update', $theme) }}" method="POST" class="flex-1 flex items-center space-x-3">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $theme->name }}" class="flex-1 bg-transparent border-none p-0 text-sm font-bold text-slate-700 focus:ring-0">
                                <select name="is_active" onchange="this.form.submit()" class="bg-white border border-slate-200 rounded-lg text-[10px] font-black uppercase px-2 py-1 outline-none">
                                    <option value="1" {{ $theme->is_active ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !$theme->is_active ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </form>
                            <form action="{{ route('admin.themes.destroy', $theme) }}" method="POST" onsubmit="return confirm('Delete this theme?');" class="ml-2 opacity-0 group-hover:opacity-100 transition-all">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-center py-8 text-slate-400 text-sm italic">No Quran themes defined.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Seerah Themes --}}
        <div class="space-y-6">
            <div class="bg-white/70 backdrop-blur-xl border border-slate-200 rounded-3xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="bg-amber-100 p-2 rounded-xl text-amber-700">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                        </div>
                        <h2 class="text-xl font-black text-slate-800">Seerah Themes</h2>
                    </div>
                </div>

                <form action="{{ route('admin.themes.store') }}" method="POST" class="mb-6 flex gap-2">
                    @csrf
                    <input type="hidden" name="type" value="SEERAH">
                    <input type="text" name="name" placeholder="New Seerah Theme..." class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-amber-500 transition-all font-medium" required>
                    <button type="submit" class="bg-amber-600 text-white px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-amber-700 transition-all">Add</button>
                </form>

                <div class="space-y-2">
                    @forelse($seerahThemes as $theme)
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 group">
                            <form action="{{ route('admin.themes.update', $theme) }}" method="POST" class="flex-1 flex items-center space-x-3">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $theme->name }}" class="flex-1 bg-transparent border-none p-0 text-sm font-bold text-slate-700 focus:ring-0">
                                <select name="is_active" onchange="this.form.submit()" class="bg-white border border-slate-200 rounded-lg text-[10px] font-black uppercase px-2 py-1 outline-none">
                                    <option value="1" {{ $theme->is_active ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !$theme->is_active ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </form>
                            <form action="{{ route('admin.themes.destroy', $theme) }}" method="POST" onsubmit="return confirm('Delete this theme?');" class="ml-2 opacity-0 group-hover:opacity-100 transition-all">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-center py-8 text-slate-400 text-sm italic">No Seerah themes defined.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
