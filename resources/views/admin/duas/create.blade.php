@extends('layouts.app')

@section('title', 'Admin Panel - Add Authentic Dua')

@section('content')
<div class="max-w-4xl mx-auto animate-fadeIn space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.duas.index') }}" class="hover:text-emerald-600 transition-colors">Duas Management</a>
                <span>/</span>
                <span>Add Authentic Dua</span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Create & Publish Dua</h1>
        </div>
        <a href="{{ route('admin.duas.index') }}"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all">
            ← Back to Queue
        </a>
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

    <form method="POST" action="{{ route('admin.duas.store') }}" class="space-y-6">
        @csrf

        {{-- Core Identification --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">1. Basic Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Dua Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        placeholder="e.g. Dua for Guidance & Piety"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Category <span class="text-rose-500">*</span></label>
                    <input type="text" name="category" list="categoryList" value="{{ old('category', 'Guidance & Faith') }}" required
                        placeholder="e.g. Forgiveness, Morning & Evening, Protection"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                    <datalist id="categoryList">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}"></option>
                        @endforeach
                        <option value="Forgiveness & Mercy"></option>
                        <option value="Morning & Evening"></option>
                        <option value="Protection & Safety"></option>
                        <option value="Guidance & Faith"></option>
                        <option value="Health & Healing"></option>
                        <option value="Anxiety & Distress"></option>
                        <option value="Gratitude & Sustenance"></option>
                        <option value="Travel & Journey"></option>
                        <option value="Family & Children"></option>
                    </datalist>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Short Context / Description</label>
                <textarea name="description" rows="2"
                    placeholder="Brief background or context of when the Prophet (ﷺ) or believers made this prayer..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('description') }}</textarea>
            </div>
        </div>

        {{-- Sacred Texts --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">2. Sacred Arabic & Translations</h2>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Arabic Text (with Tashkeel / Harakat) <span class="text-rose-500">*</span></label>
                <textarea name="arabic_text" rows="3" required dir="rtl"
                    placeholder="اللَّهُمَّ إِنِّي أَسْأَلُكَ الْهُدَى وَالتُّقَى وَالْعَفَافَ وَالْغِنَى"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 font-arabic text-xl text-slate-900 leading-loose text-right focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('arabic_text') }}</textarea>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Transliteration (Pronunciation Guide)</label>
                <input type="text" name="transliteration" value="{{ old('transliteration') }}"
                    placeholder="Allāhumma innī as'aluka al-hudā, wat-tuqā, wal-'afāfa, wal-ghinā"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">English Translation <span class="text-rose-500">*</span></label>
                    <textarea name="translation_en" rows="3" required
                        placeholder="O Allah, I ask You for guidance, piety, chastity, and self-sufficiency."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('translation_en') }}</textarea>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Urdu Translation (اردو ترجمہ)</label>
                    <textarea name="translation_ur" rows="3" dir="rtl"
                        placeholder="اے اللہ! میں تجھ سے ہدایت، تقویٰ، پاکدامنی اور غنا (بے نیازی) کا سوال کرتا ہوں۔"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-right focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('translation_ur') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Citations & Source --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4"
             x-data="{ sourceType: '{{ old('source_type', 'hadith') }}' }">
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
                        <input type="number" name="surah_number" value="{{ old('surah_number') }}" min="1" max="114"
                            placeholder="e.g. 2"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Verse Number</label>
                        <input type="number" name="verse_number" value="{{ old('verse_number') }}" min="1" max="286"
                            placeholder="e.g. 201"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Display Reference</label>
                        <input type="text" name="quran_reference" value="{{ old('quran_reference') }}"
                            placeholder="Surah Al-Baqarah 2:201"
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
                        <input type="text" name="hadith_book" value="{{ old('hadith_book') }}"
                            placeholder="e.g. Sahih Muslim"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Hadith Number</label>
                        <input type="text" name="hadith_number" value="{{ old('hadith_number') }}"
                            placeholder="e.g. 2722"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Grading</label>
                        <input type="text" name="hadith_grading" value="{{ old('hadith_grading', 'Sahih') }}"
                            placeholder="e.g. Sahih, Hasan"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Full Citation</label>
                        <input type="text" name="hadith_reference" value="{{ old('hadith_reference') }}"
                            placeholder="Sahih Muslim 2722"
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
                        placeholder="Deep dive into key Arabic words and spiritual wisdom..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('meaning_explanation') }}</textarea>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Benefits & Virtues (Faza'il)</label>
                    <textarea name="benefits_and_virtues" rows="3"
                        placeholder="Documented rewards, spiritual protection, and merits..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('benefits_and_virtues') }}</textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Recommended Recitation Time</label>
                    <input type="text" name="when_to_recite" value="{{ old('when_to_recite') }}"
                        placeholder="e.g. After every Fard prayer, Morning & Evening"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Repeat Count (Tasbih)</label>
                    <input type="number" name="repeat_count" value="{{ old('repeat_count', 1) }}" min="1" max="1000"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Display Order</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Status <span class="text-rose-500">*</span></label>
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                        <option value="approved" selected>Approved (Published immediately)</option>
                        <option value="pending">Pending (Review queue)</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div class="flex items-center pt-6 space-x-2">
                    <input type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                    <label for="is_featured" class="text-sm font-semibold text-slate-700">Set as Featured / Daily Dua highlight</label>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('admin.duas.index') }}"
                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all">
                Cancel
            </a>
            <button type="submit"
                class="px-7 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-lg hover:shadow-emerald-500/30 transition-all transform hover:-translate-y-0.5">
                Save & Publish Authentic Dua
            </button>
        </div>
    </form>
</div>
@endsection
