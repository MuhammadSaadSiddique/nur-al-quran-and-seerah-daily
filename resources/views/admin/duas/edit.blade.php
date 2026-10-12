@extends('layouts.app')

@section('title', 'Admin Panel - Review & Edit Dua')

@section('content')
<div class="max-w-4xl mx-auto animate-fadeIn space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.duas.index') }}" class="hover:text-emerald-600 transition-colors">Duas Management</a>
                <span>/</span>
                <span>Review & Edit Dua #{{ $dua->id }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Review & Edit Dua</h1>
        </div>
        <div class="flex items-center space-x-2">
            @if($dua->status === 'approved')
                <a href="{{ route('duas.show', $dua->slug) }}" target="_blank"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all inline-flex items-center space-x-1">
                    <span>View Live</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            @endif
            <a href="{{ route('admin.duas.index') }}"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all">
                ← Back to Queue
            </a>
        </div>
    </div>

    {{-- Submitter & Review Context Banner --}}
    <div class="bg-slate-900 text-white rounded-2xl p-5 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Submission Provenance</span>
                @if($dua->status === 'approved')
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Live Approved</span>
                @elseif($dua->status === 'pending')
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 animate-pulse">Awaiting Approval</span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">Rejected</span>
                @endif
            </div>
            <p class="text-sm font-medium">
                Submitted by: 
                <span class="font-bold text-emerald-300">
                    {{ $dua->submitter ? $dua->submitter->name : ($dua->submitter_name ?: 'Platform Contributor') }}
                </span>
                <span class="text-slate-400 text-xs">({{ $dua->submitter ? $dua->submitter->email : ($dua->submitter_email ?: 'No email recorded') }})</span>
                • {{ $dua->created_at->format('M d, Y g:i A') }} ({{ $dua->created_at->diffForHumans() }})
            </p>
        </div>

        @if($dua->status !== 'approved')
            <div class="flex items-center space-x-2">
                <form method="POST" action="{{ route('admin.duas.approve', $dua->id) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition-all shadow-md">
                        ✓ One-Click Approve
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm">
            <p class="font-bold mb-1">Please fix the following issues:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.duas.update', $dua->id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Core Identification --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">1. Basic Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Dua Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $dua->title) }}" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Category <span class="text-rose-500">*</span></label>
                    <input type="text" name="category" list="categoryList" value="{{ old('category', $dua->category) }}" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                    <datalist id="categoryList">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Short Context / Description</label>
                <textarea name="description" rows="2"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('description', $dua->description) }}</textarea>
            </div>
        </div>

        {{-- Sacred Texts --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">2. Sacred Arabic & Translations</h2>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Arabic Text (with Tashkeel / Harakat) <span class="text-rose-500">*</span></label>
                <textarea name="arabic_text" rows="3" required dir="rtl"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 font-arabic text-xl text-slate-900 leading-loose text-right focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('arabic_text', $dua->arabic_text) }}</textarea>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Transliteration (Pronunciation Guide)</label>
                <input type="text" name="transliteration" value="{{ old('transliteration', $dua->transliteration) }}"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">English Translation <span class="text-rose-500">*</span></label>
                    <textarea name="translation_en" rows="3" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('translation_en', $dua->translation_en) }}</textarea>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Urdu Translation (اردو ترجمہ)</label>
                    <textarea name="translation_ur" rows="3" dir="rtl"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-right focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('translation_ur', $dua->translation_ur) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Citations & Source --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4"
             x-data="{ sourceType: '{{ old('source_type', $dua->source_type) }}' }">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">3. Source & Citation References</h2>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-700">Source Type <span class="text-rose-500">*</span></label>
                <div class="flex items-center space-x-6">
                    <label class="inline-flex items-center space-x-2 text-sm text-slate-700 cursor-pointer">
                        <input type="radio" name="source_type" value="hadith" x-model="sourceType" class="text-emerald-600 focus:ring-emerald-500">
                        <span>Hadith Supplication</span>
                    </label>
                    <label class="inline-flex items-center space-x-2 text-sm text-slate-700 cursor-pointer">
                        <input type="radio" name="source_type" value="quran" x-model="sourceType" class="text-emerald-600 focus:ring-emerald-500">
                        <span>Quranic Ayah</span>
                    </label>
                    <label class="inline-flex items-center space-x-2 text-sm text-slate-700 cursor-pointer">
                        <input type="radio" name="source_type" value="both" x-model="sourceType" class="text-emerald-600 focus:ring-emerald-500">
                        <span>Both (Quranic verse cited in Hadith)</span>
                    </label>
                </div>
            </div>

            {{-- Quran Citation Fields --}}
            <div x-show="sourceType === 'quran' || sourceType === 'both'" class="p-4 bg-teal-50/50 rounded-xl border border-teal-100 space-y-3">
                <h3 class="text-xs font-bold text-teal-800 uppercase tracking-wider">Quran Reference Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Surah Number (1-114)</label>
                        <input type="number" name="surah_number" value="{{ old('surah_number', $dua->surah_number) }}" min="1" max="114"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Verse Number</label>
                        <input type="number" name="verse_number" value="{{ old('verse_number', $dua->verse_number) }}" min="1" max="286"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Display Reference</label>
                        <input type="text" name="quran_reference" value="{{ old('quran_reference', $dua->quran_reference) }}"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            {{-- Hadith Citation Fields --}}
            <div x-show="sourceType === 'hadith' || sourceType === 'both'" class="p-4 bg-amber-50/50 rounded-xl border border-amber-100 space-y-3">
                <h3 class="text-xs font-bold text-amber-800 uppercase tracking-wider">Hadith Reference Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Hadith Book</label>
                        <input type="text" name="hadith_book" value="{{ old('hadith_book', $dua->hadith_book) }}"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Hadith Number</label>
                        <input type="text" name="hadith_number" value="{{ old('hadith_number', $dua->hadith_number) }}"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Grading</label>
                        <input type="text" name="hadith_grading" value="{{ old('hadith_grading', $dua->hadith_grading) }}"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Full Citation</label>
                        <input type="text" name="hadith_reference" value="{{ old('hadith_reference', $dua->hadith_reference) }}"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Contemplation, Virtues & Recitation --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">4. Spiritual Insights & Recitation</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Detailed Meaning & Theological Explanation</label>
                    <textarea name="meaning_explanation" rows="3"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('meaning_explanation', $dua->meaning_explanation) }}</textarea>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Benefits & Virtues (Faza'il)</label>
                    <textarea name="benefits_and_virtues" rows="3"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('benefits_and_virtues', $dua->benefits_and_virtues) }}</textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Recommended Recitation Time</label>
                    <input type="text" name="when_to_recite" value="{{ old('when_to_recite', $dua->when_to_recite) }}"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Repeat Count (Tasbih)</label>
                    <input type="number" name="repeat_count" value="{{ old('repeat_count', $dua->repeat_count) }}" min="1" max="1000"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Display Order</label>
                    <input type="number" name="order" value="{{ old('order', $dua->order) }}"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                </div>
            </div>

            {{-- Administrative Decision & Review Notes --}}
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-4 mt-2">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Administrative Decision & Status</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Publication Status <span class="text-rose-500">*</span></label>
                        <select name="status" class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                            <option value="approved" {{ old('status', $dua->status) === 'approved' ? 'selected' : '' }}>
                                ✅ Approved (Visible to everyone on Website & Mobile App)
                            </option>
                            <option value="pending" {{ old('status', $dua->status) === 'pending' ? 'selected' : '' }}>
                                ⏳ Pending (Queued in review)
                            </option>
                            <option value="rejected" {{ old('status', $dua->status) === 'rejected' ? 'selected' : '' }}>
                                🛑 Rejected (Declined)
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center pt-6 space-x-2">
                        <input type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured', $dua->is_featured) ? 'checked' : '' }}
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <label for="is_featured" class="text-sm font-semibold text-slate-700">Featured / Daily Dua highlight</label>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Reviewer Notes / Rejection Reason</label>
                    <textarea name="admin_notes" rows="2"
                        placeholder="Internal feedback, verification notes, or reason for rejection..."
                        class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">{{ old('admin_notes', $dua->admin_notes) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-between pt-2">
            <div>
                @if($dua->status !== 'approved')
                    <span class="text-xs font-semibold text-amber-600">Tip: Selecting 'Approved' will publish this Dua immediately.</span>
                @endif
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.duas.index') }}"
                    class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all">
                    Cancel
                </a>
                <button type="submit"
                    class="px-7 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-lg hover:shadow-emerald-500/30 transition-all transform hover:-translate-y-0.5">
                    Update Dua & Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
