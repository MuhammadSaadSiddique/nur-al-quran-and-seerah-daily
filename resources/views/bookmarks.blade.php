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
                <p class="text-slate-500 font-bold">No bookmarked questions yet.</p>
                <a href="{{ route('home') }}"
                    class="inline-block bg-emerald-600 text-white px-6 py-3 rounded-xl font-black hover:bg-emerald-700 transition-all">Start
                    Exploring</a>
            </div>
        </template>

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