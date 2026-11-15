@extends('layouts.app')

@section('title', 'Admin Panel - Add Video Contribution')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-12 animate-fadeIn"
     x-data="{
        scopeMode: '{{ old('scope_mode', 'single') }}',
        selectedCategory: '{{ old('category', 'video') }}',
        videoUrl: '{{ old('video_url', '') }}',
        detectedPlatform: '',
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
     }"
     x-init="updatePlatform()">

    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.videos.index') }}" class="hover:text-emerald-600 transition-colors">Videos Management</a>
                <span>/</span>
                <span>Add Video</span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Add Video from Backend</h1>
        </div>
        <a href="{{ route('admin.videos.index') }}"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all">
            ← Back to Queue
        </a>
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
        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 p-6 md:p-8 text-white">
            <h2 class="text-2xl font-black">Publish or Queue a Video Connection</h2>
            <p class="text-xs md:text-sm text-slate-300 mt-1">
                Link authentic lectures, tafsir insights, or scientific explanations. As an administrator, you can publish videos directly without requiring 5 community approvals.
            </p>
        </div>

        <form action="{{ route('admin.videos.store') }}" method="POST" class="p-6 md:p-8 space-y-6">
            @csrf

            {{-- Publishing Status --}}
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                <label class="block text-xs font-black uppercase tracking-wider text-slate-700">Publishing Status *</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-emerald-400 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/70 transition-all flex items-center space-x-3">
                        <input type="radio" name="status" value="published" {{ old('status', 'published') === 'published' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-xs text-slate-900 block">✨ Publish Immediately (Live)</span>
                            <span class="text-[10px] text-slate-500">Visible right away in Quranic Lens, video gallery & mobile app.</span>
                        </div>
                    </label>
                    <label class="p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-amber-400 has-[:checked]:border-amber-600 has-[:checked]:bg-amber-50/70 transition-all flex items-center space-x-3">
                        <input type="radio" name="status" value="pending" {{ old('status') === 'pending' ? 'checked' : '' }} class="text-amber-600 focus:ring-amber-500">
                        <div>
                            <span class="font-bold text-xs text-slate-900 block">⏳ Queue as Pending</span>
                            <span class="text-[10px] text-slate-500">Allow community researchers to review and verify first.</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Title --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700">Video Title / Headline *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       placeholder="e.g. Linguistic Miracles & Depth of Surah Al-Kahf"
                       class="w-full py-2.5 px-3.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none font-medium">
            </div>

            {{-- Video URL with Real-time Platform Detection --}}
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700">Video URL (YouTube / Vimeo / TikTok / Facebook / MP4) *</label>
                    <span x-show="detectedPlatform" x-cloak class="text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-100 flex items-center gap-1">
                        <span>Detected:</span>
                        <span x-text="detectedPlatform"></span>
                    </span>
                </div>
                <input type="url" name="video_url" x-model="videoUrl" @input="updatePlatform()" required
                       placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/... or direct mp4"
                       class="w-full py-2.5 px-3.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none font-mono text-xs">
                <p class="text-[10px] text-slate-400">Supported: YouTube, Shorts, Vimeo, TikTok, Instagram Reels, Facebook Video, Dailymotion, X, Google Drive, and direct .mp4 links.</p>
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
                                <input type="radio" name="category" value="{{ $catObj->slug }}" x-model="selectedCategory" {{ old('category', 'video') === $catObj->slug ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                                <span class="font-bold text-xs text-slate-900">{{ $catObj->emoji ?: '🔬' }} {{ $catObj->name }}</span>
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1 line-clamp-1">{{ $catObj->description ?: 'Quranic research topic' }}</span>
                        </label>
                    @endforeach

                    {{-- Add New Custom Category Option --}}
                    <label class="p-3 border-2 border-dashed border-indigo-300 rounded-2xl cursor-pointer hover:border-indigo-500 has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/70 transition-all flex flex-col justify-between">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="category" value="custom" x-model="selectedCategory" {{ old('category') === 'custom' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                            <span class="font-bold text-xs text-indigo-700">➕ Add New Category</span>
                        </div>
                        <span class="text-[10px] text-indigo-500 mt-1">Create a new category on the fly</span>
                    </label>
                </div>

                <div x-show="selectedCategory === 'custom'" x-cloak class="p-4 bg-indigo-50/60 border border-indigo-200 rounded-2xl grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-900 mb-1">Emoji (Optional)</label>
                        <input type="text" name="category_emoji" value="{{ old('category_emoji', '🎬') }}" maxlength="16" placeholder="🎬"
                            class="w-full py-2 px-3 rounded-xl border border-indigo-200 bg-white text-sm text-center">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-[11px] font-bold text-indigo-900 mb-1">New Category Name *</label>
                        <input type="text" name="custom_category" value="{{ old('custom_category') }}" maxlength="100"
                            :required="selectedCategory === 'custom'"
                            placeholder="e.g. Classical Tafsir, Arabic Grammar, Seerah Insights..."
                            class="w-full py-2 px-3.5 rounded-xl border border-indigo-200 bg-white text-sm focus:border-indigo-500 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- Quran Scope Selection --}}
            <div class="space-y-4 p-5 bg-slate-50 border border-slate-200 rounded-2xl">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-700">Quranic Scope (Verses / Surahs) *</label>
                    <span class="text-[10px] text-slate-500">Select what portion of the Quran this video addresses</span>
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
                        Verse Range (e.g. 1–5)
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
                                <option value="{{ $s->number }}" {{ old('surah_number', 1) == $s->number ? 'selected' : '' }}>
                                    {{ $s->number }}. {{ $s->name_transliteration }} ({{ $s->name_arabic }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Ayah (Verse Number) *</label>
                        <input type="number" name="verse_number" min="1" value="{{ old('verse_number', 1) }}"
                               class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white">
                    </div>
                </div>

                {{-- Scope Mode: Verse Range --}}
                <div x-show="scopeMode === 'verse_range'" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Surah *</label>
                        <select name="surah_number" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white">
                            @foreach($surahs as $s)
                                <option value="{{ $s->number }}" {{ old('surah_number', 1) == $s->number ? 'selected' : '' }}>
                                    {{ $s->number }}. {{ $s->name_transliteration }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Start Ayah *</label>
                        <input type="number" name="verse_number" min="1" value="{{ old('verse_number', 1) }}"
                               class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">End Ayah *</label>
                        <input type="number" name="end_verse_number" min="1" value="{{ old('end_verse_number') }}" placeholder="e.g. 5"
                               class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                    </div>
                </div>

                {{-- Scope Mode: Entire Surah --}}
                <div x-show="scopeMode === 'full_surah'" x-cloak class="space-y-2">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Surah (Full Chapter) *</label>
                        <select name="surah_number" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                            @foreach($surahs as $s)
                                <option value="{{ $s->number }}" {{ old('surah_number', 1) == $s->number ? 'selected' : '' }}>
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
                                <option value="{{ $s->number }}">{{ $s->number }}. {{ $s->name_transliteration }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">To Surah *</label>
                        <select name="end_surah_number" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                            @foreach($surahs as $s)
                                <option value="{{ $s->number }}" {{ $s->number == 114 ? 'selected' : '' }}>{{ $s->number }}. {{ $s->name_transliteration }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Scope Mode: Multiple Surahs --}}
                <div x-show="scopeMode === 'multiple_surahs'" x-cloak class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700">Surah Numbers (comma or range separated) *</label>
                    <input type="text" name="surah_numbers" value="{{ old('surah_numbers') }}" placeholder="e.g. 112, 113, 114 or 1-3"
                           class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm bg-white">
                    <p class="text-[10px] text-slate-400">Example: "112, 113, 114" for the 3 Quls, or "1, 2, 18".</p>
                </div>
            </div>

            {{-- Description / Research Summary --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700">Research Summary / Notes / Timestamps</label>
                <textarea name="description" rows="4"
                          placeholder="Provide a summary of points discussed in the video, speaker name, key timestamps, or related tafsir references..."
                          class="w-full p-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none">{{ old('description') }}</textarea>
            </div>

            {{-- Submitter Attribution --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700">Submitter Attribution Name (Optional)</label>
                <input type="text" name="submitter_name" value="{{ old('submitter_name', auth()->user()->name ?? 'Administrator') }}"
                       placeholder="Administrator or Scholar Name"
                       class="w-full py-2.5 px-3.5 rounded-xl border border-slate-200 text-sm bg-white">
                <p class="text-[10px] text-slate-400">Name shown on the card as contributor.</p>
            </div>

            {{-- Form Actions --}}
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.videos.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-all">
                    Cancel
                </a>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs uppercase tracking-wider px-7 py-3 rounded-xl shadow-lg shadow-emerald-600/20 transition-all">
                    🚀 Save & Publish Video
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
