@extends('layouts.app')

@section('title', 'Contribute a Dua - Sacred Supplications')
@section('meta_description', 'Contribute an authentic Quranic or Prophetic Dua with references and translations. Submissions undergo scholarly verification before publication.')

@section('content')
<div class="max-w-4xl mx-auto space-y-8 pb-16 animate-fadeIn">
    {{-- Breadcrumb & Heading --}}
    <div>
        <div class="flex items-center space-x-2 text-sm text-slate-500 mb-2">
            <a href="{{ route('duas.index') }}" class="hover:text-emerald-600 transition-colors">Duas Library</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Contribute a Dua</span>
        </div>

        <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 rounded-3xl p-8 md:p-10 text-white shadow-xl border border-slate-800 relative overflow-hidden">
            <div class="relative z-10 space-y-3">
                <div class="inline-flex items-center space-x-2 bg-emerald-500/20 rounded-full px-3.5 py-1 border border-emerald-400/20 text-xs font-bold text-emerald-200">
                    <span>🤲</span>
                    <span>Community Contribution • مساهمة مباركة</span>
                </div>
                <h1 class="text-2xl md:text-4xl font-black tracking-tight leading-snug">
                    Contribute an Authentic Dua
                </h1>
                <p class="text-slate-300 text-sm md:text-base leading-relaxed max-w-2xl">
                    Share a prayer from the Holy Quran or the verified Sunnah. Every submission is carefully vetted for citation accuracy, authentic chains, and linguistic precision before publication.
                </p>
            </div>
            <div class="absolute -right-16 -bottom-16 w-64 h-64 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-5 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-sm space-y-1">
            <p class="font-bold flex items-center space-x-1.5">
                <span>⚠️</span>
                <span>Please correct the highlighted fields:</span>
            </p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('duas.store') }}" class="space-y-6"
          x-data="{
              sourceType: '{{ old('source_type', 'hadith') }}',
              words: [],
              addWord() {
                  this.words.push({ arabic: '', transliteration: '', meaning_en: '', meaning_ur: '' });
              },
              removeWord(index) {
                  this.words.splice(index, 1);
              }
          }">
        @csrf

        {{-- 1. Dua Essentials --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 md:p-8 shadow-sm space-y-5">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">1. Basic Information</h2>
                    <p class="text-xs text-slate-500">Provide an evocative title and suitable category for easy discovery.</p>
                </div>
                <span class="text-xl">📖</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Dua Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        placeholder="e.g. Dua for Patience in Adversity"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-700">Category <span class="text-rose-500">*</span></label>
                        <span class="text-[11px] font-semibold text-emerald-600">Select or type a new category</span>
                    </div>
                    <input type="text" name="category" list="categoryOptions" value="{{ old('category', 'Forgiveness & Mercy') }}" required
                        placeholder="Select or type a new category name..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
                    <datalist id="categoryOptions">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">Brief Context / Occasion</label>
                <textarea name="description" rows="2"
                    placeholder="When was this supplication uttered, or what spiritual need does it address?"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('description') }}</textarea>
            </div>
        </div>

        {{-- 2. Arabic & Meaning --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 md:p-8 shadow-sm space-y-5">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">2. Sacred Arabic & Translations</h2>
                    <p class="text-xs text-slate-500">Provide accurate Arabic with tashkeel (vowels) and faithful translations.</p>
                </div>
                <span class="text-xl">✨</span>
            </div>

            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">Arabic Text (النص القرآني أو النبوي) <span class="text-rose-500">*</span></label>
                    <span class="text-[11px] text-slate-400">Vowels/Tashkeel strongly recommended</span>
                </div>
                <textarea name="arabic_text" rows="3" required dir="rtl"
                    placeholder="رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الآخِرَةِ حَسَنَةً وَقِنَا عَذَابَ النَّارِ"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 font-arabic text-xl md:text-2xl text-slate-900 leading-loose text-right focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('arabic_text') }}</textarea>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">Transliteration (Roman Arabic)</label>
                <input type="text" name="transliteration" value="{{ old('transliteration') }}"
                    placeholder="Rabbanā ātinā fid-dunyā ḥasanatan wa fil-ākhirati ḥasanatan wa qinā 'adhāban-nār"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">English Translation <span class="text-rose-500">*</span></label>
                    <textarea name="translation_en" rows="3" required
                        placeholder="Our Lord! Give us in this world that which is good and in the Hereafter that which is good, and save us from the punishment of the Fire."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('translation_en') }}</textarea>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Urdu Translation (اردو ترجمہ)</label>
                    <textarea name="translation_ur" rows="3" dir="rtl"
                        placeholder="اے ہمارے رب! ہمیں دنیا میں بھی بھلائی عطا فرما اور آخرت میں بھی بھلائی عطا فرما اور ہمیں آگ کے عذاب سے بچا۔"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-right focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('translation_ur') }}</textarea>
                </div>
            </div>
        </div>

        {{-- 3. Source & References --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 md:p-8 shadow-sm space-y-5">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">3. Source & Verification Citations</h2>
                    <p class="text-xs text-slate-500">Authenticity is paramount. Detail the Quranic verse or Hadith source.</p>
                </div>
                <span class="text-xl">📜</span>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-700">Primary Source Type <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer transition-all"
                           :class="sourceType === 'hadith' ? 'bg-amber-50 border-amber-400 text-amber-900 font-bold ring-2 ring-amber-200' : 'bg-slate-50 border-slate-200 text-slate-600'">
                        <input type="radio" name="source_type" value="hadith" x-model="sourceType" class="sr-only">
                        <span>Hadith</span>
                    </label>
                    <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer transition-all"
                           :class="sourceType === 'quran' ? 'bg-teal-50 border-teal-400 text-teal-900 font-bold ring-2 ring-teal-200' : 'bg-slate-50 border-slate-200 text-slate-600'">
                        <input type="radio" name="source_type" value="quran" x-model="sourceType" class="sr-only">
                        <span>Quran</span>
                    </label>
                    <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer transition-all"
                           :class="sourceType === 'both' ? 'bg-purple-50 border-purple-400 text-purple-900 font-bold ring-2 ring-purple-200' : 'bg-slate-50 border-slate-200 text-slate-600'">
                        <input type="radio" name="source_type" value="both" x-model="sourceType" class="sr-only">
                        <span>Both</span>
                    </label>
                </div>
            </div>

            {{-- Quran Citation Fields --}}
            <div x-show="sourceType === 'quran' || sourceType === 'both'" class="p-4 bg-teal-50/60 rounded-xl border border-teal-200 space-y-3">
                <h3 class="text-xs font-bold text-teal-900 uppercase tracking-wider">Quran Reference Citation</h3>
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
                        <label class="text-[11px] font-bold text-slate-600">Reference Text</label>
                        <input type="text" name="quran_reference" value="{{ old('quran_reference') }}"
                            placeholder="Surah Al-Baqarah (2:201)"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            {{-- Hadith Citation Fields --}}
            <div x-show="sourceType === 'hadith' || sourceType === 'both'" class="p-4 bg-amber-50/60 rounded-xl border border-amber-200 space-y-3">
                <h3 class="text-xs font-bold text-amber-900 uppercase tracking-wider">Hadith Source Citation</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Hadith Book</label>
                        <input type="text" name="hadith_book" value="{{ old('hadith_book') }}"
                            placeholder="e.g. Sahih al-Bukhari"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Hadith Number</label>
                        <input type="text" name="hadith_number" value="{{ old('hadith_number') }}"
                            placeholder="e.g. 6384"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Grading</label>
                        <input type="text" name="hadith_grading" value="{{ old('hadith_grading', 'Sahih') }}"
                            placeholder="e.g. Sahih"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-600">Full Citation</label>
                        <input type="text" name="hadith_reference" value="{{ old('hadith_reference') }}"
                            placeholder="Sahih al-Bukhari 6384"
                            class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Contemplation & Recitation Context --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 md:p-8 shadow-sm space-y-5">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">4. Spiritual Insights & Recitation</h2>
                    <p class="text-xs text-slate-500">Help worshippers appreciate the depth, virtues, and optimal times for this prayer.</p>
                </div>
                <span class="text-xl">💎</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Detailed Meaning / Commentary</label>
                    <textarea name="meaning_explanation" rows="3"
                        placeholder="Explanation of key Arabic expressions or linguistic subtleties..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('meaning_explanation') }}</textarea>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Benefits & Virtues (فضائل وثواب)</label>
                    <textarea name="benefits_and_virtues" rows="3"
                        placeholder="Promised rewards or spiritual sanctuary associated with this Dua..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all">{{ old('benefits_and_virtues') }}</textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">When to Recite</label>
                    <input type="text" name="when_to_recite" value="{{ old('when_to_recite') }}"
                        placeholder="e.g. When waking up, In prostration (Sujood), On Friday"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Recommended Repeat Count</label>
                    <input type="number" name="repeat_count" value="{{ old('repeat_count', 1) }}" min="1" max="1000"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm">
                </div>
            </div>
        </div>

        {{-- 5. Submitter Details --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 md:p-8 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">5. Contributor Attribution</h2>

            @auth
                <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center space-x-3 text-sm">
                    <span class="text-xl">👤</span>
                    <div>
                        <p class="font-bold text-emerald-900">Signed in as {{ auth()->user()->name }}</p>
                        <p class="text-xs text-emerald-700">{{ auth()->user()->email }} • Your contribution will be attributed to your profile.</p>
                    </div>
                </div>
            @else
                <p class="text-xs text-slate-500">You are submitting as a guest. Optionally provide your name and email so we can acknowledge your submission.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Your Name</label>
                        <input type="text" name="submitter_name" value="{{ old('submitter_name') }}"
                            placeholder="e.g. Abdullah"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Your Email (Optional)</label>
                        <input type="email" name="submitter_email" value="{{ old('submitter_email') }}"
                            placeholder="abdullah@example.com"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm">
                    </div>
                </div>
            @endauth
        </div>

        {{-- Submission Notice & Button --}}
        <div class="p-5 bg-amber-50/80 border border-amber-200 rounded-2xl flex items-start space-x-3 text-amber-900">
            <span class="text-2xl">⏳</span>
            <div class="text-xs leading-relaxed">
                <p class="font-bold">Editorial & Scholarly Review Notice:</p>
                <p class="text-amber-800 mt-0.5">
                    To preserve authenticity and theological precision, every submitted Dua is held in a review queue. Once our administrative and scholarly team approves the citation, it will automatically become accessible to all users across the website and the Android mobile app.
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('duas.index') }}"
                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all">
                Cancel
            </a>
            <button type="submit"
                class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-xl shadow-emerald-900/20 hover:shadow-emerald-900/30 transition-all transform hover:-translate-y-0.5">
                Submit Dua for Review ✨
            </button>
        </div>
    </form>
</div>
@endsection
