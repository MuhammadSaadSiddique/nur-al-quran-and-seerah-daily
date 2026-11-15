@extends('layouts.app')

@section('title', 'Admin Panel - Edit Video Contribution')

@section('content')
@php
    $initialScopeMode = 'single';
    if (is_array($video->surah_numbers) && count($video->surah_numbers) > 1) {
        $initialScopeMode = 'multiple_surahs';
    } elseif ($video->end_surah_number && $video->end_surah_number > $video->surah_number) {
        $initialScopeMode = 'surah_range';
    } elseif ($video->is_full_surah) {
        $initialScopeMode = 'full_surah';
    } elseif ($video->end_verse_number && $video->end_verse_number > $video->verse_number) {
        $initialScopeMode = 'verse_range';
    }
@endphp

<div class="max-w-4xl mx-auto space-y-6 pb-12 animate-fadeIn"
     x-data="{
        scopeMode: '{{ old('scope_mode', $initialScopeMode) }}',
        selectedCategory: '{{ old('category', $video->category) }}',
        videoUrl: '{{ old('video_url', $video->video_url) }}',
        detectedPlatform: '{{ ucfirst($video->video_platform) }}',
        updatePlatform() {
            const url = this.videoUrl.trim().toLowerCase();
            if (url.includes('youtube.com') || url.includes('youtu.be')) this.detectedPlatform = 'YouTube';
            else if (url.includes('vimeo.com')) this.detectedPlatform = 'Vimeo';
            else if (url.includes('tiktok.com')) this.detectedPlatform = 'TikTok';
            else if (url.includes('facebook.com') || url.includes('fb.watch')) this.detectedPlatform = 'Facebook';
            else if (url.includes('instagram.com')) this.detectedPlatform = 'Instagram';
            else if (url.includes('dailymotion.com') || url.includes('dai.ly')) this.detectedPlatform = 'Dailymotion';
            else if (url.includes('twitter.com') || url.includes('x.com')) this.detectedPlatform = 'X (Twitter)';
            else if (url.endsWith('.mp4') || url.endsWith('.webm') || url.endsWith('.mov')) this.detectedPlatform = 'Direct Video File';
            else if (url.length > 5) this.detectedPlatform = 'External Video Provider';
            else this.detectedPlatform = '';
        }
     }">

    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.videos.index') }}" class="hover:text-emerald-600 transition-colors">Videos Management</a>
                <span>/</span>
                <span>Edit Video #{{ $video->id }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Edit Video Details</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.videos.index') }}"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all">
                ← Back to Queue
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-700 space-y-1">
            <strong class="font-bold block">Please fix the following issues:</strong>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 p-6 md:p-8 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black">Edit Video #{{ $video->id }}</h2>
                <p class="text-xs md:text-sm text-slate-300 mt-1">
                    Created {{ $video->created_at->diffForHumans() }} by {{ $video->submitter_name ?: 'Community' }}.
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if($video->status !== 'published')
                    <form method="POST" action="{{ route('admin.videos.approve', $video->id) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                            ✓ Approve Now
                        </button>
                    </form>
                @endif
                @if($video->status !== 'rejected')
                    <form method="POST" action="{{ route('admin.videos.reject', $video->id) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                            ✕ Reject
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <form action="{{ route('admin.videos.update', $video->id) }}" method="POST" class="p-6 md:p-8 space-y-6">
            @csrf
            @method('PUT')

            {{-- Publishing Status --}}
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                <label class="block text-xs font-black uppercase tracking-wider text-slate-700">Status *</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-emerald-400 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/70 transition-all flex items-center space-x-3">
                        <input type="radio" name="status" value="published" {{ old('status', $video->status) === 'published' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-xs text-slate-900 block">✨ Published</span>
                            <span class="text-[10px] text-slate-500">Live in video gallery.</span>
                        </div>
                    </label>
                    <label class="p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-amber-400 has-[:checked]:border-amber-600 has-[:checked]:bg-amber-50/70 transition-all flex items-center space-x-3">
                        <input type="radio" name="status" value="pending" {{ old('status', $video->status) === 'pending' ? 'checked' : '' }} class="text-amber-600 focus:ring-amber-500">
                        <div>
                            <span class="font-bold text-xs text-slate-900 block">⏳ Pending</span>
                            <span class="text-[10px] text-slate-500">In verification queue.</span>
                        </div>
                    </label>
                    <label class="p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-rose-400 has-[:checked]:border-rose-600 has-[:checked]:bg-rose-50/70 transition-all flex items-center space-x-3">
                        <input type="radio" name="status" value="rejected" {{ old('status', $video->status) === 'rejected' ? 'checked' : '' }} class="text-rose-600 focus:ring-rose-500">
                        <div>
                            <span class="font-bold text-xs text-slate-900 block">🛑 Rejected</span>
                            <span class="text-[10px] text-slate-500">Hidden from public.</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Title --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700">Video Title / Headline *</label>
                <input type="text" name="title" value="{{ old('title', $video->title) }}" required
                       class="w-full py-2.5 px-3.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none font-medium">
            </div>

            {{-- Video URL with Platform Detection --}}
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700">Video URL *</label>
                    <span x-show="detectedPlatform" x-cloak class="text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-100 flex items-center gap-1">
                        <span>Platform:</span>
                        <span x-text="detectedPlatform"></span>
                    </span>
                </div>
                <input type="url" name="video_url" x-model="videoUrl" @input="updatePlatform()" required
                       class="w-full py-2.5 px-3.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none font-mono text-xs">
            </div>

            {{-- Category Selection --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-700">Category *</label>
                    <a href="{{ route('admin.categories.index', ['type' => 'quran_research']) }}" class="text-xs font-bold text-indigo-600 hover:underline">
                        🏷️ Manage Categories
                    </a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($categories as $catObj)
                        <label class="p-3 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-indigo-400 has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/50 transition-all flex flex-col justify-between">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="category" value="{{ $catObj->slug }}" x-model="selectedCategory" {{ old('category', $video->category) === $catObj->slug ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                                <span class="font-bold text-xs text-slate-900">{{ $catObj->emoji ?: '🔬' }} {{ $catObj->name }}</span>
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1 line-clamp-1">{{ $catObj->description ?: 'Quranic research topic' }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Quran Scope Selection --}}
            <div class="space-y-4 p-5 bg-slate-50 border border-slate-200 rounded-2xl">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-700">Quranic Scope (Verses / Surahs) *</label>
                    <span class="text-[10px] text-slate-500">Current: {{ $video->scope_label }}</span>
                </div>

                {{-- Scope Mode Selector --}}
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="scopeMode = 'single'"
                            :class="scopeMode === 'single' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all">
                        Single Verse
                    </button>
                    <button type="button" @click="scopeMode = 'verse_range'"
                            :class="scopeMode === 'verse_range' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all">
                        Verse Range
                    </button>
                    <button type="button" @click="scopeMode = 'full_surah'"
                            :class="scopeMode === 'full_surah' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all">
                        Entire Surah
                    </button>
                    <button type="button" @click="scopeMode = 'surah_range'"
                            :class="scopeMode === 'surah_range' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all">
                        Surah Range
                    </button>
                    <button type="button" @click="scopeMode = 'multiple_surahs'"
                            :class="scopeMode === 'multiple_surahs' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all">
                        Multiple Surahs
                    </button>
                </div>
                <input type="hidden" name="scope_mode" :value="scopeMode">

                {{-- Scope Mode: Single Verse --}}
                <div x-show="scopeMode === 'single'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Surah (Chapter) *</label>
                        <select name="surah_number" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white">
                            @foreach($surahs as $s)
                                <option value="{{ $s->number }}" {{ old('surah_number', $video->surah_number) == $s->number ? 'selected' : '' }}>
                                    {{ $s->number }}. {{ $s->name_transliteration }} ({{ $s->name_arabic }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Ayah (Verse Number) *</label>
                        <input type="number" name="verse_number" min="1" value="{{ old('verse_number', $video->verse_number ?: 1) }}"
                               class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white">
                    </div>
                </div>

                {{-- Scope Mode: Verse Range --}}
                <div x-show="scopeMode === 'verse_range'" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Surah *</label>
                        <select name="surah_number" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                            @foreach($surahs as $s)
                                <option value="{{ $s->number }}" {{ old('surah_number', $video->surah_number) == $s->number ? 'selected' : '' }}>
                                    {{ $s->number }}. {{ $s->name_transliteration }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Start Ayah *</label>
                        <input type="number" name="verse_number" min="1" value="{{ old('verse_number', $video->verse_number ?: 1) }}"
                               class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">End Ayah *</label>
                        <input type="number" name="end_verse_number" min="1" value="{{ old('end_verse_number', $video->end_verse_number) }}" placeholder="e.g. 5"
                               class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                    </div>
                </div>

                {{-- Scope Mode: Entire Surah --}}
                <div x-show="scopeMode === 'full_surah'" x-cloak class="space-y-2">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Surah (Full Chapter) *</label>
                        <select name="surah_number" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                            @foreach($surahs as $s)
                                <option value="{{ $s->number }}" {{ old('surah_number', $video->surah_number) == $s->number ? 'selected' : '' }}>
                                    {{ $s->number }}. {{ $s->name_transliteration }} ({{ $s->verse_count }} verses)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="is_full_surah" value="1">
                </div>

                {{-- Scope Mode: Surah Range --}}
                <div x-show="scopeMode === 'surah_range'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">From Surah *</label>
                        <select name="surah_number" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                            @foreach($surahs as $s)
                                <option value="{{ $s->number }}" {{ old('surah_number', $video->surah_number) == $s->number ? 'selected' : '' }}>
                                    {{ $s->number }}. {{ $s->name_transliteration }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">To Surah *</label>
                        <select name="end_surah_number" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                            @foreach($surahs as $s)
                                <option value="{{ $s->number }}" {{ old('end_surah_number', $video->end_surah_number ?: 114) == $s->number ? 'selected' : '' }}>
                                    {{ $s->number }}. {{ $s->name_transliteration }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Scope Mode: Multiple Surahs --}}
                <div x-show="scopeMode === 'multiple_surahs'" x-cloak class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700">Surah Numbers *</label>
                    <input type="text" name="surah_numbers"
                           value="{{ old('surah_numbers', is_array($video->surah_numbers) ? implode(', ', $video->surah_numbers) : '') }}"
                           placeholder="e.g. 112, 113, 114"
                           class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                </div>
            </div>

            {{-- Description / Research Summary --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700">Research Summary / Notes</label>
                <textarea name="description" rows="4"
                          class="w-full p-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none">{{ old('description', $video->description) }}</textarea>
            </div>

            {{-- Submitter Attribution --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700">Submitter Attribution Name</label>
                <input type="text" name="submitter_name" value="{{ old('submitter_name', $video->submitter_name) }}"
                       class="w-full py-2.5 px-3.5 rounded-xl border border-slate-200 text-sm bg-white">
            </div>

            {{-- Form Actions --}}
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.videos.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-all">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider px-7 py-3 rounded-xl shadow-lg shadow-indigo-600/20 transition-all">
                    💾 Update Video
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
