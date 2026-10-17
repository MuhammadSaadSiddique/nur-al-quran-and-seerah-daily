@extends('layouts.app')
@section('title', ($insight['title'] ?? 'Quranic History') . ' — Historical Context & Preservation')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($insight['text'] ?? "Learn about the historical revelation, compilation, and preservation of the Holy Quran: " . ($insight['title'] ?? '')), 155))
@section('canonical', route('quran.history'))

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@type": "Article",
    "headline": "{{ addslashes($insight['title'] ?? 'Quranic History') }}",
    "description": "{{ addslashes(\Illuminate\Support\Str::limit(strip_tags($insight['text'] ?? ''), 155)) }}",
    "mainEntityOfPage": "{{ route('quran.history') }}",
    "publisher": {
        "@type": "Organization",
        "name": "The Eternal Echo",
        "logo": {
            "@type": "ImageObject",
            "url": "{{ asset('eternal.png') }}"
        }
    }
}
</script>
@endpush

@section('content')
    <div class="max-w-3xl mx-auto space-y-8 pb-10 animate-fadeIn"
        x-data="insightQuiz({{ json_encode($insight['question']) }}, {{ $isRead ? 'true' : 'false' }})">
        {{-- Toast Feedback --}}
        <div x-show="toastMessage" x-cloak
            class="fixed bottom-6 right-6 z-50 bg-emerald-700 text-white px-6 py-4 rounded-2xl shadow-2xl border border-emerald-500/30 flex items-center space-x-3 animate-slideUp">
            <span>✨</span>
            <span class="text-sm font-black" x-text="toastMessage"></span>
        </div>

        {{-- Insight Content --}}
        <div class="bg-white rounded-[2rem] p-10 shadow-xl border border-slate-100 space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center space-x-2 text-amber-600 font-black uppercase text-xs tracking-widest">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" />
                    </svg>
                    <span>Historical Insight</span>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="markAsRead()" :disabled="readStatusLoading"
                        class="px-3.5 py-1.5 rounded-full text-xs font-black transition-all shadow-sm flex items-center gap-1.5"
                        :class="isRead ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200'">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <span x-text="isRead ? '✓ Read' : 'Mark as Read'"></span>
                    </button>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black text-white uppercase tracking-wider {{ $difficulty === 'Easy' ? 'bg-emerald-500' : ($difficulty === 'Medium' ? 'bg-amber-500' : 'bg-rose-500') }}">
                        {{ $difficulty }}
                    </span>
                </div>
            </div>
            <h1 class="text-3xl font-black text-slate-900 leading-tight">{{ $insight['title'] }}</h1>
            <div class="text-slate-700 text-lg leading-relaxed italic border-l-4 border-amber-200 pl-6 py-2">
                "{!! e($insight['content']) !!}"
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-slate-50">
                <p class="text-xs text-slate-400 font-medium">Compilation & Preservation of the Quran</p>
                <button @click="markAsRead()" :disabled="readStatusLoading"
                    class="text-xs font-black transition-colors flex items-center gap-1"
                    :class="isRead ? 'text-emerald-600' : 'text-amber-600 hover:text-amber-800'">
                    <span x-text="isRead ? '✓ Marked as read' : '📖 Mark reflection as read'"></span>
                </button>
            </div>
        </div>

        {{-- Quiz Question --}}
        <div class="bg-white rounded-2xl shadow-2xl p-6 md:p-8 border border-slate-100">
            <div class="mb-6">
                <div class="flex items-center space-x-2 mb-2">
                    <span class="h-1 w-8 bg-emerald-500 rounded-full"></span>
                    <span class="text-emerald-700 font-bold text-xs tracking-widest uppercase">Knowledge Check</span>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mt-2 leading-tight" x-text="question.text"></h2>
            </div>
            <div class="space-y-4">
                <template x-for="(option, idx) in question.options" :key="idx">
                    <button :disabled="answered" @click="selectAnswer(idx)" :class="getOptionClass(idx)"
                        class="w-full text-left p-5 rounded-xl border-2 transition-all duration-300 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <span :class="getLetterClass(idx)"
                                class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm"
                                x-text="String.fromCharCode(65 + idx)"></span>
                            <span class="font-semibold text-lg" x-text="option"></span>
                        </div>
                    </button>
                </template>
            </div>
            <div x-show="answered"
                class="mt-8 p-6 bg-blue-50 rounded-2xl border-2 border-blue-100 shadow-inner animate-slideUp">
                <h4 class="text-blue-900 font-bold text-sm uppercase tracking-wider mb-1">Deep Insight</h4>
                <p class="text-blue-800 text-md leading-relaxed font-medium" x-text="question.explanation"></p>
            </div>
        </div>

        {{-- Pagination & Generate Controls --}}
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
            {{-- Pagination Links --}}
            <div class="flex items-center space-x-3 w-full md:w-auto bg-white p-3 rounded-2xl shadow-md border border-slate-100 justify-between md:justify-start">
                @if ($insights->onFirstPage())
                    <span class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-50 rounded-xl cursor-not-allowed">
                        &larr; Newer
                    </span>
                @else
                    <a href="{{ $insights->previousPageUrl() }}" class="px-4 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                        &larr; Newer
                    </a>
                @endif

                <span class="text-xs font-black text-slate-500 uppercase tracking-wider">
                    {{ $insights->currentPage() }} / {{ $insights->lastPage() }}
                </span>

                @if ($insights->hasMorePages())
                    <a href="{{ $insights->nextPageUrl() }}" class="px-4 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                        Older &rarr;
                    </a>
                @else
                    <span class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-50 rounded-xl cursor-not-allowed">
                        Older &rarr;
                    </span>
                @endif
            </div>

            {{-- Generate New Insight Form --}}
            <form method="GET" action="{{ route('quran.history') }}" class="flex items-center space-x-2 bg-white p-3 rounded-2xl shadow-md border border-slate-100 w-full md:w-auto justify-between md:justify-start">
                <input type="hidden" name="generate" value="1">
                <select name="difficulty" class="px-3 py-2 bg-slate-100 rounded-xl font-bold text-xs text-slate-700 outline-none cursor-pointer border-0">
                    <option value="Easy" {{ $difficulty === 'Easy' ? 'selected' : '' }}>Easy</option>
                    <option value="Medium" {{ $difficulty === 'Medium' ? 'selected' : '' }}>Medium</option>
                    <option value="Hard" {{ $difficulty === 'Hard' ? 'selected' : '' }}>Hard</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-black text-xs transition-all shadow-md">
                    Generate New
                </button>
            </form>
        </div>

        <a x-show="answered" href="{{ auth()->check() ? route('home') : route('welcome') }}"
            class="block w-full bg-slate-900 text-white py-5 rounded-2xl font-black text-lg shadow-lg hover:bg-slate-800 transition-all text-center">
            Return to Home
        </a>
    </div>
@endsection

@push('scripts')
    <script>
        function insightQuiz(question, initialRead = false) {
            return {
                question,
                selectedAnswer: null,
                answered: false,
                isRead: initialRead,
                readStatusLoading: false,
                toastMessage: '',

                async markAsRead() {
                    if (this.isRead || this.readStatusLoading) return;
                    this.readStatusLoading = true;
                    try {
                        const res = await fetch('{{ route("reading.mark-insight") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ type: 'QURAN_HISTORY', insight_id: this.question.id })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.isRead = true;
                            this.toastMessage = data.message;
                            setTimeout(() => { this.toastMessage = ''; }, 3500);
                        }
                    } catch (e) {
                        console.error('Error marking as read:', e);
                    } finally {
                        this.readStatusLoading = false;
                    }
                },

                async selectAnswer(idx) {
                    if (this.answered) return;
                    this.selectedAnswer = idx;
                    this.answered = true;
                    // Answering also marks the reflection as read
                    if (!this.isRead) {
                        this.markAsRead();
                    }
                    try {
                        const res = await fetch('{{ route("insight.answer") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ questionId: this.question.id, selectedIndex: idx }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.question.correctAnswerIndex = data.correctAnswerIndex;
                            this.question.explanation = data.explanation || '';
                        }
                    } catch (e) { }
                },

                getOptionClass(idx) {
                    if (!this.answered) return 'border-slate-200 bg-white hover:border-emerald-500 hover:bg-emerald-50 text-slate-700';
                    if (this.question.correctAnswerIndex >= 0 && idx === this.question.correctAnswerIndex) return 'border-emerald-600 bg-emerald-100 text-emerald-900 ring-4 ring-emerald-50 scale-[1.02]';
                    if (idx === this.selectedAnswer) return 'border-rose-600 bg-rose-100 text-rose-900';
                    return 'border-slate-100 bg-slate-50 text-slate-400 opacity-60 cursor-not-allowed';
                },
                getLetterClass(idx) {
                    if (!this.answered) return 'bg-slate-100 text-slate-500';
                    if (this.question.correctAnswerIndex >= 0 && idx === this.question.correctAnswerIndex) return 'bg-emerald-600 text-white';
                    if (idx === this.selectedAnswer) return 'bg-rose-600 text-white';
                    return 'bg-slate-200 text-slate-400';
                },
            };
        }
    </script>
@endpush