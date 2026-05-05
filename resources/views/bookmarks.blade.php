@extends('layouts.app')
@section('title', 'Bookmarks - The Eternal Echo')
@section('meta_description', 'Your bookmarked quiz questions. Review saved questions and deepen your understanding of the Quran and Seerah.')

@section('content')
    <div class="max-w-3xl mx-auto space-y-8 pb-10 animate-fadeIn" x-data="bookmarkPage({{ json_encode($bookmarks) }})">
        <h1 class="text-2xl font-black text-slate-900">Bookmarked Questions</h1>

        <template x-if="bookmarks.length === 0">
            <div class="bg-white rounded-[2rem] p-12 shadow-xl border border-slate-100 text-center space-y-4">
                <svg class="w-16 h-16 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" stroke-width="2" />
                </svg>
                <p class="text-slate-500 font-bold">No local bookmarked questions yet.</p>
                <a href="{{ route('home') }}"
                    class="inline-block bg-emerald-600 text-white px-6 py-3 rounded-xl font-black hover:bg-emerald-700 transition-all">Start
                    Exploring</a>
            </div>
        </template>

        <!-- Quran.com Bookmarks Section -->
        @if(count($quranBookmarks) > 0)
        <div class="mt-8 mb-4">
            <h2 class="text-xl font-black text-[#114030] flex items-center gap-2">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/></svg>
                Quran.com Bookmarks
            </h2>
        </div>
        <div class="space-y-4">
            @foreach($quranBookmarks as $quranBm)
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-[#114030]/20 space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600 mb-1">
                            Quran Verse
                        </p>
                        <h3 class="text-lg font-bold text-slate-900">
                            Verse Key: {{ $quranBm['verse_key'] ?? 'Unknown' }}
                        </h3>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <hr class="my-8 border-slate-200">
        @elseif(Auth::user()->quran_access_token)
        <div class="mt-8 mb-8 p-6 bg-slate-50 rounded-2xl border border-slate-100 text-center">
            <p class="text-slate-500 font-bold">No Quran.com bookmarks found.</p>
        </div>
        @else
        <div class="mt-8 mb-8 p-6 bg-emerald-50 rounded-2xl border border-emerald-100 text-center">
            <p class="text-emerald-800 font-bold mb-4">Link your Quran.com account to sync verses.</p>
            <a href="{{ route('quran.redirect') }}" class="inline-block bg-[#114030] text-[#E0F2EB] px-6 py-2 rounded-xl font-black text-sm hover:bg-[#1a5a44] transition-all">Link Quran.com</a>
        </div>
        @endif

        <h2 class="text-xl font-black text-slate-900 mb-4">Local Quiz Bookmarks</h2>
        <div class="space-y-4">
            <template x-for="(bm, index) in bookmarks" :key="bm.id || index">
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100 space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600 mb-1"
                                x-text="bm.sourceInfo"></p>
                            <h3 class="text-lg font-bold text-slate-900" x-text="bm.question?.text || bm.text || ''"></h3>
                        </div>
                        <button @click="removeBookmark(index, bm)"
                            class="text-rose-400 hover:text-rose-600 p-2 hover:bg-rose-50 rounded-lg transition-all"
                            title="Remove">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    stroke-width="2" />
                            </svg>
                        </button>
                    </div>
                    <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                        <p class="text-emerald-900 font-bold text-sm"
                            x-text="'✓ ' + ((bm.question?.options || [])[bm.question?.correctAnswerIndex] || '')"></p>
                        <p class="text-emerald-700 text-sm mt-2" x-text="bm.question?.explanation || ''"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function bookmarkPage(bookmarks) {
            return {
                bookmarks,
                async removeBookmark(index, bm) {
                    try {
                        await fetch('{{ route("bookmark.toggle") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                question: bm.question || bm,
                                sourceInfo: bm.sourceInfo || 'Unknown',
                            }),
                        });
                        this.bookmarks.splice(index, 1);
                    } catch (e) { console.error(e); }
                }
            };
        }
    </script>
@endpush