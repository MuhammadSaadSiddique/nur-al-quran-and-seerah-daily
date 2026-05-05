@extends('layouts.app')

@section('title', 'Admin - Manage Themes')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Manage Themes</h1>
            <p class="text-slate-500 mt-1">Define and separate Quran and Seerah quiz themes for SEO and user exploration.</p>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    @include('admin.partials.tabs')

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium animate-fadeIn">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-8">
        {{-- Theme Management Card --}}
        <div class="bg-white/70 backdrop-blur-xl border border-slate-200 rounded-3xl p-8 shadow-xl">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-xl font-black text-slate-800">All Quiz Themes</h2>
                <button @click="$dispatch('open-add-modal')" class="bg-emerald-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-500/20">
                    Add New Theme
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">
                            <th class="px-4 py-4">Theme Info</th>
                            <th class="px-4 py-4">Type</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-4 py-4">SEO Slug</th>
                            <th class="px-4 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($quranThemes->concat($seerahThemes)->sortBy('name') as $theme)
                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-5">
                                    <div class="font-bold text-slate-800">{{ $theme->name }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5 line-clamp-1 max-w-xs">{{ $theme->description ?? 'No description' }}</div>
                                </td>
                                <td class="px-4 py-5">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $theme->type === 'PARA' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $theme->type }}
                                    </span>
                                </td>
                                <td class="px-4 py-5">
                                    <span class="flex items-center space-x-1.5">
                                        <span class="w-2 h-2 rounded-full {{ $theme->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                        <span class="text-xs font-bold {{ $theme->is_active ? 'text-emerald-700' : 'text-slate-500' }}">
                                            {{ $theme->is_active ? 'Active' : 'Disabled' }}
                                        </span>
                                    </span>
                                </td>
                                <td class="px-4 py-5">
                                    <code class="text-[10px] bg-slate-100 px-2 py-1 rounded text-slate-500">/themes/{{ $theme->slug }}</code>
                                </td>
                                <td class="px-4 py-5 text-right">
                                    <div class="flex items-center justify-end space-x-2" x-data="{ open: false }">
                                        <button @click="open = true" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors" title="Edit Theme">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        
                                        {{-- Inline Edit Form (Hidden by default) --}}
                                        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                                            <div @click.away="open = false" class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl animate-slideUp">
                                                <h3 class="text-2xl font-black text-slate-800 mb-6">Edit Theme: {{ $theme->name }}</h3>
                                                <form action="{{ route('admin.themes.update', $theme) }}" method="POST" class="space-y-4 text-left">
                                                    @csrf
                                                    @method('PUT')
                                                    <div>
                                                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1 ml-1">Theme Name</label>
                                                        <input type="text" name="name" value="{{ $theme->name }}" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold outline-none focus:border-indigo-500 transition-all" required>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1 ml-1">SEO Slug</label>
                                                        <input type="text" name="slug" value="{{ $theme->slug }}" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold outline-none focus:border-indigo-500 transition-all" required>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1 ml-1">SEO Description</label>
                                                        <textarea name="description" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm font-medium outline-none focus:border-indigo-500 transition-all">{{ $theme->description }}</textarea>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1 ml-1">Status</label>
                                                        <select name="is_active" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold outline-none focus:border-indigo-500 transition-all">
                                                            <option value="1" {{ $theme->is_active ? 'selected' : '' }}>Active</option>
                                                            <option value="0" {{ !$theme->is_active ? 'selected' : '' }}>Disabled</option>
                                                        </select>
                                                    </div>
                                                    <div class="pt-4 flex gap-3">
                                                        <button type="submit" class="flex-1 bg-indigo-600 text-white py-4 rounded-2xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-500/20 transition-all">Save Changes</button>
                                                        <button type="button" @click="open = false" class="px-6 bg-slate-100 text-slate-600 rounded-2xl font-bold hover:bg-slate-200 transition-all">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <form action="{{ route('admin.themes.destroy', $theme) }}" method="POST" onsubmit="return confirm('Delete this theme?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition-colors">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add Theme Modal --}}
    <div x-data="{ showAdd: false }" @open-add-modal.window="showAdd = true">
        <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div @click.away="showAdd = false" class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl animate-slideUp">
                <h3 class="text-2xl font-black text-slate-800 mb-6">Create New Theme</h3>
                <form action="{{ route('admin.themes.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1 ml-1">Theme Name</label>
                        <input type="text" name="name" placeholder="e.g., Stories of Prophets" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold outline-none focus:border-emerald-500 transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1 ml-1">Type</label>
                        <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold outline-none focus:border-emerald-500 transition-all">
                            <option value="PARA">Quran (PARA)</option>
                            <option value="SEERAH">Seerah & History</option>
                        </select>
                    </div>
                    <p class="text-[10px] text-slate-400 italic px-1">Slug and default description will be automatically generated. You can edit them after creation.</p>
                    <div class="pt-4 flex gap-3">
                        <button type="submit" class="flex-1 bg-emerald-600 text-white py-4 rounded-2xl font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 transition-all">Create Theme</button>
                        <button type="button" @click="showAdd = false" class="px-6 bg-slate-100 text-slate-600 rounded-2xl font-bold hover:bg-slate-200 transition-all">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
