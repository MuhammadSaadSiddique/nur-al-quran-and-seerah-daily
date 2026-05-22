@extends('layouts.app')

@section('title', 'Question #' . $question->id . ' - The Eternal Echo')
@section('meta_description', 'Interactive review of Question #' . $question->id . ' on ' . ($question->theme ?? 'Quran & Seerah') . ' with explanations and source references.')

@section('content')
@php
    $isBookmarked = false;
    if (auth()->check()) {
        $bookmarks = auth()->user()->bookmarked_questions ?? [];
        foreach ($bookmarks as $bm) {
            $bmId = $bm['id'] ?? ($bm['question']['id'] ?? null);
            if ($bmId === $question->question_id) {
                $isBookmarked = true;
                break;
            }
        }
    }
@endphp

<div class="max-w-4xl mx-auto space-y-8 pb-16 animate-fadeIn"
     x-data="{
         selectedOption: null,
         isAnswered: false,
         correctIndex: {{ $question->correct_answer_index }},
         isBookmarked: {{ $isBookmarked ? 'true' : 'false' }},
         showShareModal: false,
         linkCopied: false,
         getShareText() {
             return '🧠 Can you answer this? \n\n*Question:* {{ addslashes($question->text) }}\n\nFind the correct answer and detailed explanation here:';
         },
         async shareTo(platform) {
             const text = this.getShareText();
             const url = window.location.href;
             const encodedText = encodeURIComponent(text);
             const encodedUrl = encodeURIComponent(url);

             if (platform === 'copy') {
                 try {
                     await navigator.clipboard.writeText(`${text}\n${url}`);
                     this.linkCopied = true;
                     setTimeout(() => this.linkCopied = false, 2000);
                 } catch (err) { console.error('Copy failed', err); }
                 return;
             }

             if (platform === 'native') {
                 if (navigator.share) {
                     try {
                         await navigator.share({ title: 'The Eternal Echo Challenge', text: text, url: url });
                     } catch (err) { console.log('Share canceled', err); }
                 }
                 this.showShareModal = false;
                 return;
             }

             const shareUrls = {
                 whatsapp: `https://api.whatsapp.com/send?text=${encodedText}%20${encodedUrl}`,
                 facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}&quote=${encodedText}`,
                 twitter: `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}`,
                 telegram: `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`,
                 linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
                 email: `mailto:?subject=${encodeURIComponent('Ummah Quiz Challenge')}&body=${encodedText}%20${encodedUrl}`
             };

             if (shareUrls[platform]) {
                 window.open(shareUrls[platform], '_blank', 'noopener,noreferrer,width=600,height=500');
             }
             this.showShareModal = false;
         },
         async toggleBookmark() {
             try {
                 const questionData = {
                     id: '{{ $question->question_id }}',
                     text: {!! json_encode($question->text) !!},
                     options: {!! json_encode($question->options) !!},
                     correctAnswerIndex: {{ $question->correct_answer_index }},
                     explanation: {!! json_encode($question->explanation) !!},
                     theme: {!! json_encode($question->theme) !!},
                     reference: {!! json_encode($question->reference) !!}
                 };

                 const res = await fetch('{{ route("bookmark.toggle") }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                         'Accept': 'application/json',
                     },
                     body: JSON.stringify({
                         question: questionData,
                         sourceInfo: '{{ $question->source_info ?? "General" }}'
                     }),
                 });
                 const data = await res.json();
                 if (data.success) {
                     this.isBookmarked = data.bookmarked;
                 }
             } catch (e) { console.error(e); }
         }
     }">

    {{-- Breadcrumb & Back navigation --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-2 text-sm text-slate-500 font-bold">
            <a href="{{ route('questions.index') }}" class="hover:text-emerald-600 transition-colors">Question Bank</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-400 capitalize">{{ str_replace('_', ' ', strtolower($question->type)) }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 font-black">Question #{{ $question->id }}</span>
        </div>

        <a href="{{ route('questions.index') }}" class="inline-flex items-center space-x-2 px-4 py-2 bg-white text-slate-700 hover:text-emerald-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-sm font-bold text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Bank</span>
        </a>
    </div>

    {{-- Main Question Card --}}
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200/80 shadow-2xl rounded-3xl overflow-hidden p-6 md:p-10 space-y-8 relative">
        
        {{-- Badges & Actions --}}
        <div class="flex items-center justify-between border-b border-slate-100 pb-6">
            <div class="flex flex-wrap gap-2.5">
                <span class="text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-xl border shadow-sm
                    {{ $question->type === 'PARA' ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' :
                       ($question->type === 'SEERAH' ? 'bg-blue-50 text-blue-700 border-blue-200/60' :
                       ($question->type === 'QURAN_HISTORY' ? 'bg-amber-50 text-amber-700 border-amber-200/60' : 'bg-violet-50 text-violet-700 border-violet-200/60')) }}">
                    {{ str_replace('_', ' ', $question->type) }}
                </span>
                <span class="text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-xl border shadow-sm
                    {{ $question->difficulty === 'Easy' ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' :
                       ($question->difficulty === 'Medium' ? 'bg-amber-50 text-amber-700 border-amber-200/60' : 'bg-rose-50 text-rose-700 border-rose-200/60') }}">
                    {{ $question->difficulty }}
                </span>
                @if($question->theme)
                    <span class="text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-xl bg-slate-50 text-slate-600 border border-slate-200/60 shadow-sm">
                        {{ $question->theme }}
                    </span>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                {{-- Bookmark Button --}}
                @auth
                <button @click="toggleBookmark()"
                        class="p-3.5 rounded-2xl border transition-all duration-300 hover:scale-105 active:scale-95 shadow-sm"
                        :class="isBookmarked ? 'bg-amber-50 border-amber-300 text-amber-500' : 'bg-white border-slate-200 text-slate-400 hover:text-slate-600 hover:border-slate-300'"
                        :title="isBookmarked ? 'Remove Bookmark' : 'Bookmark Question'">
                    <svg class="w-5 h-5 transition-transform" :class="isBookmarked ? 'scale-110' : ''" :fill="isBookmarked ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                </button>
                @endauth

                {{-- Share Button --}}
                <button @click="showShareModal = true"
                        class="p-3.5 bg-white border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50/50 rounded-2xl transition-all duration-300 hover:scale-105 active:scale-95 shadow-sm"
                        title="Share Question">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 10.742l4.636-2.318m0 0a3 3 0 10-4.636-2.318 3 3 0 004.636 2.318zm0 0a3 3 0 114.636 2.318m-4.636-2.318l-4.636 2.318m0 0a3 3 0 11-4.636-2.318 3 3 0 014.636 2.318z"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Question text --}}
        <div class="space-y-4 text-center md:text-left">
            <span class="text-xs font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100">Review Prompt</span>
            <h2 class="text-2xl md:text-3xl font-black text-slate-900 leading-snug mt-2">
                {{ $question->text }}
            </h2>
        </div>

        {{-- Interactive Option Selector (Test Yourself!) --}}
        <div class="space-y-4">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider text-center md:text-left">
                <span x-show="!isAnswered">💡 Click an option to test your knowledge:</span>
                <span x-show="isAnswered" class="text-emerald-600">✨ Answer revealed! See analysis below:</span>
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($question->options as $idx => $opt)
                    <button @click="if (!isAnswered) { selectedOption = {{ $idx }}; isAnswered = true; }"
                            :disabled="isAnswered"
                            class="w-full text-left p-5 rounded-2xl border-2 font-bold text-base transition-all duration-300 flex items-center space-x-4 shadow-sm group relative overflow-hidden
                                   {{ $idx === $question->correct_answer_index ? 'disabled:opacity-100' : '' }}"
                            :class="!isAnswered
                                ? 'bg-slate-50/50 hover:bg-white border-slate-100 hover:border-emerald-500 hover:shadow-lg hover:shadow-emerald-50/50 text-slate-700 hover:text-emerald-800'
                                : ({{ $idx }} === correctIndex
                                    ? 'bg-emerald-50/90 border-emerald-400 text-emerald-800 shadow-md shadow-emerald-50'
                                    : ({{ $idx }} === selectedOption
                                        ? 'bg-rose-50 border-rose-300 text-rose-800'
                                        : 'bg-slate-50/30 border-slate-100 text-slate-400 opacity-60'))">
                        
                        {{-- Alphabet Indicator (A, B, C, D) --}}
                        <span class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-black flex-shrink-0 transition-all duration-300"
                              :class="!isAnswered
                                  ? 'bg-slate-200 text-slate-500 group-hover:bg-emerald-600 group-hover:text-white'
                                  : ({{ $idx }} === correctIndex
                                      ? 'bg-emerald-600 text-white'
                                      : ({{ $idx }} === selectedOption
                                          ? 'bg-rose-600 text-white'
                                          : 'bg-slate-100 text-slate-400'))">
                            {{ chr(65 + $idx) }}
                        </span>
                        
                        <span class="flex-1">{{ $opt }}</span>

                        {{-- Correct/Incorrect Floating Check Icon --}}
                        <div x-show="isAnswered && ({{ $idx }} === correctIndex || ({{ $idx }} === selectedOption && {{ $idx }} !== correctIndex))"
                             x-cloak
                             class="absolute right-4 top-1/2 -translate-y-1/2">
                            <template x-if="{{ $idx }} === correctIndex">
                                <span class="text-emerald-600 bg-emerald-100 p-1.5 rounded-full inline-block">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </template>
                            <template x-if="{{ $idx }} === selectedOption && {{ $idx }} !== correctIndex">
                                <span class="text-rose-600 bg-rose-100 p-1.5 rounded-full inline-block">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </span>
                            </template>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Explanation Block (reveals immediately when isAnswered or if already reviewed) --}}
        <div x-show="isAnswered"
             x-collapse
             x-cloak
             class="border-t border-slate-100 pt-8 mt-6">
            <div class="bg-gradient-to-br from-sky-50 to-blue-50/50 rounded-3xl border border-sky-100 p-6 md:p-8 space-y-4">
                <div class="flex items-center space-x-2 text-sky-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-sm font-black uppercase tracking-wider">Educational Insight & Explanation</span>
                </div>
                
                <p class="text-slate-700 text-base md:text-lg font-medium leading-relaxed">
                    {{ $question->explanation }}
                </p>

                @if($question->reference || $question->source_info)
                    <div class="flex flex-wrap gap-3 border-t border-sky-100/70 pt-4 mt-2">
                        @if($question->reference)
                            <div class="inline-flex items-center space-x-1.5 text-sky-800 text-xs font-bold bg-sky-100/60 px-3 py-1.5 rounded-xl border border-sky-200/30">
                                <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                <span>Ref: {{ $question->reference }}</span>
                            </div>
                        @endif
                        @if($question->source_info)
                            <div class="inline-flex items-center space-x-1.5 text-slate-600 text-xs font-bold bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200/50">
                                <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Source: {{ $question->source_info }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Question Global Stats Dashboard --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white border border-slate-200/80 shadow-xl rounded-3xl p-6 flex items-center space-x-5">
            <div class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Attempts</p>
                <p class="text-2xl font-black text-slate-900">{{ max(0, $question->times_answered) }} attempts</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200/80 shadow-xl rounded-3xl p-6 flex items-center space-x-5">
            <div class="p-4 bg-blue-50 text-blue-600 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Correct Solves</p>
                <p class="text-2xl font-black text-slate-900">{{ max(0, $question->times_correct) }} solves</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200/80 shadow-xl rounded-3xl p-6 flex items-center space-x-5">
            @php
                $accuracy = $question->times_answered > 0 ? (int)round(($question->times_correct / $question->times_answered) * 100) : 0;
            @endphp
            <div class="p-4 rounded-2xl {{ $accuracy >= 70 ? 'bg-emerald-50 text-emerald-600' : ($accuracy >= 45 ? 'bg-amber-50 text-amber-600' : 'bg-rose-50 text-rose-600') }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Global Accuracy</p>
                <p class="text-2xl font-black {{ $accuracy >= 70 ? 'text-emerald-600' : ($accuracy >= 45 ? 'text-amber-600' : 'text-rose-600') }}">
                    {{ $accuracy }}%
                </p>
            </div>
        </div>
    </div>

    {{-- Share Modal --}}
    <div x-show="showShareModal" x-cloak
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[300] flex items-end sm:items-center justify-center p-4"
         @click.self="showShareModal = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-t-[2.5rem] sm:rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden animate-slideUp" @click.stop>
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 pt-6 pb-3">
                <div>
                    <h3 class="text-lg font-black text-slate-900">Share Question</h3>
                    <p class="text-xs text-slate-400 font-medium">Challenge your friends and family!</p>
                </div>
                <button @click="showShareModal = false"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 p-2 rounded-full transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Question Preview --}}
            <div class="mx-6 mb-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">Question Prompt</p>
                <p class="text-sm font-semibold text-slate-700 leading-relaxed line-clamp-2">{{ $question->text }}</p>
            </div>

            {{-- Platform Grid --}}
            <div class="grid grid-cols-4 gap-3 px-6 pb-6">
                {{-- WhatsApp --}}
                <button @click="shareTo('whatsapp')" class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-green-50 transition-all group">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all" style="background: #25D366">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500">WhatsApp</span>
                </button>

                {{-- Facebook --}}
                <button @click="shareTo('facebook')" class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-blue-50 transition-all group">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all" style="background: #1877F2">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500">Facebook</span>
                </button>

                {{-- Twitter/X --}}
                <button @click="shareTo('twitter')" class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-slate-50 transition-all group">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all bg-black">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500">X</span>
                </button>

                {{-- Telegram --}}
                <button @click="shareTo('telegram')" class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-sky-50 transition-all group">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all" style="background: #0088cc">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500">Telegram</span>
                </button>

                {{-- LinkedIn --}}
                <button @click="shareTo('linkedin')" class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-blue-50 transition-all group">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all" style="background: #0A66C2">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500">LinkedIn</span>
                </button>

                {{-- Email --}}
                <button @click="shareTo('email')" class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-orange-50 transition-all group">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all bg-gradient-to-br from-orange-400 to-rose-500">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500">Email</span>
                </button>

                {{-- Copy Link --}}
                <button @click="shareTo('copy')" class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-emerald-50 transition-all group">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all bg-gradient-to-br from-emerald-400 to-teal-500">
                        <svg x-show="!linkCopied" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                        <svg x-show="linkCopied" x-cloak class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold" :class="linkCopied ? 'text-emerald-600' : 'text-slate-500'" x-text="linkCopied ? 'Copied!' : 'Copy Text'"></span>
                </button>

                {{-- More (native share) --}}
                <button x-show="!!navigator.share" @click="shareTo('native')" class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-violet-50 transition-all group">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md group-hover:shadow-lg transition-all bg-gradient-to-br from-violet-400 to-purple-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500">More</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
