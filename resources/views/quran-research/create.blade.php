@extends('layouts.app')

@section('title', 'Contribute Quran Research / Video')
@section('meta_description', 'Add your Quranic research, link explanatory videos, and collaborate with scholars and students of knowledge.')

@section('content')
<div class="max-w-3xl mx-auto space-y-6 pb-12 animate-fadeIn">
    <div class="flex items-center justify-between">
        <a href="{{ route('research.contributions.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
            <span>← Back to Contributions</span>
        </a>
        <span class="text-xs font-black uppercase tracking-wider text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full border border-indigo-100">
            Open Peer Contribution
        </span>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 p-6 md:p-8 text-white">
            <h1 class="text-2xl font-black">Submit Quran Research or Video</h1>
            <p class="text-xs md:text-sm text-slate-300 mt-1">
                Link valuable lectures, scientific insights, historical contexts, or thematic reflections. Submissions will enter the peer verification queue and be published once 5 community approvals are reached.
            </p>
        </div>

        <form action="{{ route('research.contributions.store') }}" method="POST" class="p-6 md:p-8 space-y-6">
            @csrf

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

            {{-- Category Selection --}}
            <div class="space-y-3" x-data="{ selectedCategory: '{{ old('category', 'video') }}' }">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-700">Category *</label>
                    @if(auth()->check() && auth()->user()->is_admin)
                        <a href="{{ route('admin.categories.index', ['type' => 'quran_research']) }}" class="text-xs font-bold text-indigo-600 hover:underline">
                            🏷️ Manage Categories
                        </a>
                    @endif
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @php
                        $catsList = isset($researchCategories) && $researchCategories->isNotEmpty()
                            ? $researchCategories
                            : \App\Models\Category::getQuranResearchCategories();
                    @endphp
                    @foreach($catsList as $catObj)
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
                        <span class="text-[10px] text-indigo-500 mt-1">Create a new research topic</span>
                    </label>
                </div>

                {{-- Custom Category Input Fields --}}
                <div x-show="selectedCategory === 'custom'" x-cloak class="p-4 bg-indigo-50/60 border border-indigo-200 rounded-2xl grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-900 mb-1">Emoji (Optional)</label>
                        <input type="text" name="category_emoji" value="{{ old('category_emoji', '🔬') }}" maxlength="16" placeholder="🔬"
                            class="w-full py-2 px-3 rounded-xl border border-indigo-200 bg-white text-sm text-center">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-[11px] font-bold text-indigo-900 mb-1">New Category Name *</label>
                        <input type="text" name="custom_category" value="{{ old('custom_category') }}" maxlength="100"
                            :required="selectedCategory === 'custom'"
                            placeholder="e.g. Linguistic Miracles, Numerical Patterns, Medicine..."
                            class="w-full py-2 px-3.5 rounded-xl border border-indigo-200 bg-white text-sm focus:border-indigo-500 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- Surah & Ayah coordinate --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700">Surah (Chapter) *</label>
                    <select name="surah_number" required class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        @foreach($surahs as $s)
                            <option value="{{ $s->number }}" {{ old('surah_number', 1) == $s->number ? 'selected' : '' }}>
                                {{ $s->number }}. {{ $s->name_transliteration }} ({{ $s->name_arabic }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700">Ayah (Verse Number) *</label>
                    <input type="number" name="verse_number" min="1" value="{{ old('verse_number', 1) }}" required
                           class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
            </div>

            {{-- Title --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700">Title / Headline *</label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Deep Explanation of the Iron Miracle (Surah Hadid 57:25)"
                       class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>

            {{-- Video URL --}}
            <div class="space-y-1" id="video_url_container">
                <label class="block text-xs font-bold text-slate-700">Video URL (YouTube / Vimeo / MP4)</label>
                <input type="url" name="video_url" value="{{ old('video_url') }}" placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/..."
                       class="w-full py-2.5 px-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                <p class="text-[10px] text-slate-400">Supported platforms: YouTube, Vimeo, direct MP4 video URLs.</p>
            </div>

            {{-- Description / Research Summary --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700">Research Summary / Notes</label>
                <textarea name="description" rows="4" placeholder="Provide a summary of the points discussed in the video or research paper, key timestamps, or related tafsir references..."
                          class="w-full p-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">{{ old('description') }}</textarea>
            </div>

            {{-- Contributor info if guest --}}
            @guest
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Your Name (Optional)</label>
                        <input type="text" name="submitter_name" value="{{ old('submitter_name') }}" placeholder="Brother / Sister"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Your Email (Optional, for notifications)</label>
                        <input type="email" name="submitter_email" value="{{ old('submitter_email') }}" placeholder="you@example.com"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                </div>
            @endguest

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('research.contributions.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-all">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider px-7 py-3 rounded-xl shadow-lg shadow-indigo-600/20 transition-all">
                    🚀 Submit Contribution
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
