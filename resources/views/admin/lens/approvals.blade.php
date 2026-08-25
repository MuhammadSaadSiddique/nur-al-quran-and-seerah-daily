@extends('layouts.app')

@section('title', 'Researcher Moderation Queue - The Eternal Echo')

@section('content')
    <div class="space-y-8 animate-fadeIn"
        x-data="{ activeTab: (new URLSearchParams(window.location.search)).get('tab') || 'analyses' }" x-init="$watch('activeTab', value => {
             const url = new URL(window.location.href);
             url.searchParams.set('tab', value);
             window.history.replaceState(null, '', url.toString());
         })">
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-3xl font-extrabold text-slate-900">Researcher Moderation Cockpit</h2>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Review collaborative submissions,
                    link quiz themes, or manually map direct links</p>
            </div>
        </div>

        {{-- Alert Messages --}}
        @if(session('success'))
            <div
                class="bg-emerald-50 text-emerald-800 border border-emerald-100 p-4 rounded-2xl text-sm font-semibold flex items-center space-x-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div
                class="bg-rose-50 text-rose-800 border border-rose-100 p-4 rounded-2xl text-sm font-semibold flex items-center space-x-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Tabs --}}
        <div class="flex bg-slate-100 p-1.5 rounded-2xl max-w-2xl flex-wrap gap-1">
            <button @click="activeTab = 'analyses'"
                :class="activeTab === 'analyses' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all min-w-[100px]">
                Analyses ({{ $pendingAnalyses->total() }})
            </button>
            <button @click="activeTab = 'words'"
                :class="activeTab === 'words' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all min-w-[100px]">
                Word Tags ({{ $pendingWordTags->total() }})
            </button>
            <button @click="activeTab = 'verses'"
                :class="activeTab === 'verses' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all min-w-[100px]">
                Verse Tags ({{ $pendingVerseTags->total() }})
            </button>
            <button @click="activeTab = 'connections'"
                :class="activeTab === 'connections' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all min-w-[150px]">
                🔗 Connections ({{ $pendingConnections->total() }})
            </button>
            <button @click="activeTab = 'approved'"
                :class="activeTab === 'approved' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all min-w-[120px]">
                📜 Active ({{ $approvedAnalyses->total() }})
            </button>
            <button @click="activeTab = 'create'"
                :class="activeTab === 'create' ? 'bg-white shadow-sm text-emerald-800' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all min-w-[120px]">
                ➕ Map Connection
            </button>
            <button @click="activeTab = 'categories'"
                :class="activeTab === 'categories' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all min-w-[150px]">
                🧠 Categories ({{ $scienceCategories->count() }})
            </button>
        </div>

        {{-- Filters Form --}}
        <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm">
            <form action="{{ route('admin.lens.approvals.index') }}" method="GET"
                class="grid grid-cols-1 sm:grid-cols-5 gap-4 items-end">
                <input type="hidden" name="tab" :value="activeTab">

                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Surah Number</label>
                    <input type="number" name="chapter_number" value="{{ request('chapter_number') }}" min="1" max="114"
                        placeholder="e.g. 2"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-bold text-slate-700 focus:border-emerald-500 focus:bg-white outline-none transition-all text-xs" />
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Verse Number</label>
                    <input type="number" name="verse_number" value="{{ request('verse_number') }}" min="1"
                        placeholder="e.g. 255"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-bold text-slate-700 focus:border-emerald-500 focus:bg-white outline-none transition-all text-xs" />
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Category</label>
                    @if(Auth::user() && Auth::user()->is_researcher && !Auth::user()->is_admin && Auth::user()->expert_category_id)
                        @php
                            $userExpCat = \App\Models\ScienceCategory::find(Auth::user()->expert_category_id);
                        @endphp
                        <select disabled
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-100 font-bold text-slate-500 outline-none text-xs cursor-not-allowed">
                            <option value="{{ $userExpCat->id }}">{{ $userExpCat->emoji }} {{ $userExpCat->name }}</option>
                        </select>
                        <input type="hidden" name="category_id" value="{{ $userExpCat->id }}">
                    @else
                        <select name="category_id"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-bold text-slate-700 focus:border-emerald-500 focus:bg-white outline-none transition-all text-xs cursor-pointer">
                            <option value="">All Categories</option>
                            @foreach($scienceCategories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->emoji }} {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Connection Type</label>
                    <select name="connection_type"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-bold text-slate-700 focus:border-emerald-500 focus:bg-white outline-none transition-all text-xs cursor-pointer">
                        <option value="all" {{ request('connection_type') === 'all' ? 'selected' : '' }}>All Connection Types
                        </option>
                        <option value="science" {{ request('connection_type') === 'science' ? 'selected' : '' }}>🧠 Science
                            Links</option>
                        <option value="seerat" {{ request('connection_type') === 'seerat' ? 'selected' : '' }}>🕌 Seerah Links
                        </option>
                        <option value="hadith" {{ request('connection_type') === 'hadith' ? 'selected' : '' }}>📚 Hadith Links
                        </option>
                        <option value="history" {{ request('connection_type') === 'history' ? 'selected' : '' }}>🏛️ History
                            Links</option>
                        <option value="scripture" {{ request('connection_type') === 'scripture' ? 'selected' : '' }}>📖
                            Scripture Links</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all shadow-sm">
                        Filter
                    </button>
                    <a href="{{ route('admin.lens.approvals.index') }}?tab=analyses" @click="activeTab = 'analyses'"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs uppercase tracking-widest py-3 px-4 rounded-xl transition-all text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Analysis Tab --}}
        <div x-show="activeTab === 'analyses'" class="space-y-4" x-cloak>
            @if($pendingAnalyses->count() > 0)
                <div class="grid grid-cols-1 gap-6">
                    @foreach($pendingAnalyses as $analysis)
                        <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm space-y-4">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-50 pb-4">
                                <div>
                                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest block">Lens:
                                        {{ strtoupper($analysis->lens_type) }}</span>
                                    <h4 class="font-bold text-slate-900 text-lg">{{ $analysis->title }}</h4>
                                    <span class="text-xs text-slate-500 font-medium">Submitted by:
                                        {{ $analysis->user ? $analysis->user->email : 'System' }}</span>
                                </div>
                                <div class="flex items-center space-x-2 shrink-0">
                                    <span
                                        class="text-xs font-black uppercase tracking-widest bg-emerald-50 text-emerald-800 border border-emerald-100 px-3 py-1 rounded-full">
                                        Surah {{ $analysis->chapter_number }} : {{ $analysis->verse_number }}
                                    </span>
                                    <a href="{{ route('lens.verse', [$analysis->chapter_number, $analysis->verse_number]) }}"
                                        target="_blank"
                                        class="p-2 border border-slate-100 hover:border-emerald-500 rounded-xl transition-all text-slate-400 hover:text-emerald-600"
                                        title="View Context">
                                        👁️ Context
                                    </a>
                                </div>
                            </div>

                            <p
                                class="text-slate-600 text-sm leading-relaxed whitespace-pre-line bg-slate-50/50 p-6 rounded-2xl border border-slate-50">
                                {{ $analysis->content }}
                            </p>

                            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-50">
                                {{-- Reject Form --}}
                                <form action="{{ route('admin.lens.approvals.reject', ['analysis', $analysis->id]) }}" method="POST"
                                    class="flex items-center space-x-2">
                                    @csrf
                                    <input type="text" name="rejection_reason" placeholder="Rejection reason (optional)"
                                        class="px-4 py-2.5 rounded-xl border border-slate-100 focus:border-rose-500 bg-slate-50 outline-none text-xs font-medium w-48 transition-all" />
                                    <button type="submit"
                                        class="bg-rose-50 hover:bg-rose-100 text-rose-700 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest border border-rose-100 transition-all">
                                        Reject
                                    </button>
                                </form>

                                {{-- Approve Form --}}
                                <form action="{{ route('admin.lens.approvals.approve', ['analysis', $analysis->id]) }}"
                                    method="POST" class="flex items-center space-x-2">
                                    @csrf
                                    <select name="theme_id"
                                        class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 outline-none text-xs font-bold text-slate-700 focus:bg-white focus:border-emerald-500 max-w-[200px] transition-all">
                                        <option value="">-- Link to Quiz Theme --</option>
                                        @foreach($themes as $theme)
                                            <option value="{{ $theme->id }}" {{ $analysis->theme_id == $theme->id ? 'selected' : '' }}>
                                                {{ $theme->name }} ({{ $theme->type }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-600/10 transition-all">
                                        Approve
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $pendingAnalyses->links() }}
                </div>
            @else
                <div
                    class="text-center py-16 bg-white rounded-3xl border border-slate-100 shadow-sm text-slate-400 font-bold text-sm">
                    No pending analyses in queue.
                </div>
            @endif
        </div>

        {{-- Word Tags Tab --}}
        <div x-show="activeTab === 'words'" class="space-y-4" x-cloak>
            @if($pendingWordTags->count() > 0)
                <div class="grid grid-cols-1 gap-4">
                    @foreach($pendingWordTags as $tag)
                        <div
                            class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div class="space-y-2">
                                <div class="flex items-center space-x-3">
                                    <span class="font-arabic text-xl font-bold text-slate-800">{{ $tag->word_text }}</span>
                                    <span
                                        class="text-[9px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 border border-slate-200 px-2 py-0.5 rounded-full">
                                        Pos: {{ $tag->word_position }}
                                    </span>
                                    <span
                                        class="text-xs font-black uppercase tracking-widest bg-indigo-50 text-indigo-700 border border-indigo-100 px-2.5 py-0.5 rounded-full">
                                        {{ $tag->tag_type }}: {{ $tag->tag_value }}
                                    </span>
                                </div>
                                <p class="text-slate-600 text-sm font-medium italic">"{{ $tag->explanation }}"</p>
                                <span class="text-[10px] text-slate-400 font-black block uppercase tracking-widest">
                                    By: {{ $tag->user ? $tag->user->email : 'System' }} • Context: Surah {{ $tag->chapter_number }}
                                    : {{ $tag->verse_number }}
                                </span>
                            </div>

                            <div class="flex items-center space-x-2 shrink-0 self-end md:self-center">
                                {{-- Reject Form --}}
                                <form action="{{ route('admin.lens.approvals.reject', ['word-tag', $tag->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-rose-50 hover:bg-rose-100 text-rose-700 px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest border border-rose-100 transition-all">
                                        Reject
                                    </button>
                                </form>

                                {{-- Approve Form --}}
                                <form action="{{ route('admin.lens.approvals.approve', ['word-tag', $tag->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-600/10 transition-all">
                                        Approve
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $pendingWordTags->links() }}
                </div>
            @else
                <div
                    class="text-center py-16 bg-white rounded-3xl border border-slate-100 shadow-sm text-slate-400 font-bold text-sm">
                    No pending word tags in queue.
                </div>
            @endif
        </div>

        {{-- Verse Tags Tab --}}
        <div x-show="activeTab === 'verses'" class="space-y-4" x-cloak>
            @if($pendingVerseTags->count() > 0)
                <div class="grid grid-cols-1 gap-4">
                    @foreach($pendingVerseTags as $tag)
                        <div
                            class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div class="space-y-2">
                                <div class="flex items-center space-x-3">
                                    <span
                                        class="text-xs font-black uppercase tracking-widest bg-emerald-50 text-emerald-800 border border-emerald-100 px-2.5 py-0.5 rounded-full">
                                        Surah {{ $tag->chapter_number }} : {{ $tag->verse_number }}
                                    </span>
                                    <span
                                        class="text-xs font-black uppercase tracking-widest bg-indigo-50 text-indigo-700 border border-indigo-100 px-2.5 py-0.5 rounded-full">
                                        {{ $tag->tag_type }}: {{ $tag->tag_value }}
                                    </span>
                                </div>
                                <p class="text-slate-600 text-sm font-medium italic">"{{ $tag->explanation }}"</p>
                                <span class="text-[10px] text-slate-400 font-black block uppercase tracking-widest">
                                    By: {{ $tag->user ? $tag->user->email : 'System' }}
                                </span>
                            </div>

                            <div class="flex items-center space-x-2 shrink-0 self-end md:self-center">
                                {{-- Reject Form --}}
                                <form action="{{ route('admin.lens.approvals.reject', ['verse-tag', $tag->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-rose-50 hover:bg-rose-100 text-rose-700 px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest border border-rose-100 transition-all">
                                        Reject
                                    </button>
                                </form>

                                {{-- Approve Form --}}
                                <form action="{{ route('admin.lens.approvals.approve', ['verse-tag', $tag->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-600/10 transition-all">
                                        Approve
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $pendingVerseTags->links() }}
                </div>
            @else
                <div
                    class="text-center py-16 bg-white rounded-3xl border border-slate-100 shadow-sm text-slate-400 font-bold text-sm">
                    No pending verse tags in queue.
                </div>
            @endif
        </div>

        {{-- Active Analyses Tab --}}
        <div x-show="activeTab === 'approved'" class="space-y-4" x-cloak>
            @if($approvedAnalyses->count() > 0)
                <div class="grid grid-cols-1 gap-6">
                    @foreach($approvedAnalyses as $analysis)
                        <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm space-y-4">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-50 pb-4">
                                <div>
                                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest block">Lens:
                                        {{ strtoupper($analysis->lens_type) }}</span>
                                    <h4 class="font-bold text-slate-900 text-lg">{{ $analysis->title }}</h4>
                                    <span class="text-xs text-slate-500 font-medium">Submitted by:
                                        {{ $analysis->user ? $analysis->user->email : 'System' }}</span>
                                    @if($analysis->theme)
                                        <span
                                            class="text-[9px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200/50 px-2 py-0.5 rounded block w-max mt-1.5">
                                            🎯 Linked Theme: {{ $analysis->theme->name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2 shrink-0">
                                    <span
                                        class="text-xs font-black uppercase tracking-widest bg-emerald-50 text-emerald-800 border border-emerald-100 px-3 py-1 rounded-full">
                                        Surah {{ $analysis->chapter_number }} : {{ $analysis->verse_number }}
                                    </span>
                                    <a href="{{ route('lens.verse', [$analysis->chapter_number, $analysis->verse_number]) }}"
                                        target="_blank"
                                        class="p-2 border border-slate-100 hover:border-emerald-500 rounded-xl transition-all text-slate-400 hover:text-emerald-600"
                                        title="View Context">
                                        👁️ Context
                                    </a>
                                </div>
                            </div>

                            <p
                                class="text-slate-600 text-sm leading-relaxed whitespace-pre-line bg-slate-50/50 p-6 rounded-2xl border border-slate-50">
                                {{ $analysis->content }}
                            </p>

                            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-50">
                                <form action="{{ route('admin.lens.approvals.analysis.destroy', $analysis->id) }}" method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete/retract this analysis? This action is permanent.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="bg-rose-50 hover:bg-rose-100 text-rose-700 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest border border-rose-100 transition-all">
                                        Delete / Retract
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $approvedAnalyses->links() }}
                </div>
            @else
                <div
                    class="text-center py-16 bg-white rounded-3xl border border-slate-100 shadow-sm text-slate-400 font-bold text-sm">
                    No active analyses published yet.
                </div>
            @endif
        </div>

        {{-- Create Connection Link Tab --}}
        <div x-show="activeTab === 'create'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm max-w-2xl mx-auto space-y-6">
                <div>
                    <h3 class="text-lg font-black text-slate-900">Map Direct Verse Connection</h3>
                    <p class="text-xs text-slate-400 font-semibold mt-1">Create relationship connections between verses and
                        databases with integrated duplicate verification checks.</p>
                </div>

                <form action="{{ route('admin.lens.approvals.create-link') }}" method="POST" class="space-y-5">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Surah / Chapter
                                (1-114)</label>
                            <select name="chapter_number"
                                class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:border-emerald-500 outline-none transition-all"
                                required>
                                @for ($i = 1; $i <= 114; $i++)
                                    <option value="{{ $i }}">Surah {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Verse Number</label>
                            <input type="number" name="verse_number" min="1" placeholder="e.g. 255"
                                class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:border-emerald-500 outline-none transition-all"
                                required />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Connection Category</label>
                        <select name="link_type"
                            class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:border-emerald-500 outline-none transition-all"
                            required>
                            <option value="science">🔬 Science Facts</option>
                            <option value="seerat">🕌 Seerah Events</option>
                            <option value="hadith">📚 Hadith Records</option>
                            <option value="history">🏛️ Historical Context</option>
                            <option value="bible">✝️ Biblical Verses</option>
                            <option value="torah">📜 Torah Sections</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Title / Scripture
                            Reference</label>
                        <input type="text" name="title"
                            placeholder="e.g. Celestial Orbit / Genesis 1:1 / Sahih Hadith text excerpt"
                            class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:border-emerald-500 outline-none transition-all"
                            required />
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Content / Connection
                            Description</label>
                        <textarea name="content" rows="5"
                            placeholder="Enter translation, scientific description, historical events context, or scripture text..."
                            class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:border-emerald-500 outline-none transition-all resize-none"
                            required></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Extra Badge Info /
                            Classification (Optional)</label>
                        <input type="text" name="extra_info"
                            placeholder="e.g. Astronomy / Medinan / Hadith No. 256 / Bronze Age / Gen 1:1"
                            class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:border-emerald-500 outline-none transition-all" />
                        <p class="text-[9px] text-slate-400 font-bold ml-1 mt-1">This badge displays dynamically on homepage
                            summaries (e.g. Field, Category, Collection name).</p>
                    </div>

                    <button type="submit"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-4.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-emerald-600/10 hover:shadow-emerald-600/20 hover:-translate-y-0.5 transition-all">
                        Map Connection & Save
                    </button>
                </form>
            </div>
        </div>

        {{-- Science Categories Tab --}}
        <div x-show="activeTab === 'categories'" class="space-y-6" x-cloak>
            <!-- Card: Add New Category -->
            <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-50">
                    <h3 class="text-sm font-black uppercase tracking-widest text-slate-800 flex items-center gap-2">
                        <span>🧠</span> Add New Science Category
                    </h3>
                </div>
                <form action="{{ route('admin.lens.approvals.science-category.store') }}?tab=categories" method="POST"
                    class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Category Name</label>
                            <input type="text" name="name" placeholder="e.g. Neuroscience / Psychology"
                                class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:border-purple-500 outline-none transition-all text-xs"
                                required />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Slug (under_scored)</label>
                            <input type="text" name="slug" placeholder="e.g. neuroscience_psychology"
                                class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:border-purple-500 outline-none transition-all text-xs"
                                required />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Emoji Representation</label>
                            <input type="text" name="emoji" placeholder="e.g. 🧠"
                                class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:border-purple-500 outline-none transition-all text-xs"
                                required />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-1">Mapped Database Fields (Comma
                            separated)</label>
                        <input type="text" name="mapped_fields" placeholder="e.g. neuroscience,psychology"
                            class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-800 focus:bg-white focus:border-purple-500 outline-none transition-all text-xs"
                            required />
                        <p class="text-[9px] text-slate-400 font-bold ml-1 mt-1">These values match the 'field' attribute in
                            your science_facts database (case-insensitive).</p>
                    </div>

                    <button type="submit"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-purple-600/10 hover:shadow-purple-600/20 hover:-translate-y-0.5 transition-all">
                        Create & Register Category
                    </button>
                </form>
            </div>

            <!-- Card: List Categories -->
            <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm space-y-6">
                <h3 class="text-sm font-black uppercase tracking-widest text-slate-800">Active Scientific Categories</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="py-3 text-[10px] font-black uppercase tracking-wider text-slate-400">Emoji / Name
                                </th>
                                <th class="py-3 text-[10px] font-black uppercase tracking-wider text-slate-400">Slug</th>
                                <th class="py-3 text-[10px] font-black uppercase tracking-wider text-slate-400">Mapped
                                    Database Fields</th>
                                <th class="py-3 text-[10px] font-black uppercase tracking-wider text-slate-400 text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($scienceCategories as $cat)
                                <tr class="group" x-data="{ editing: false }">
                                    <td class="py-4">
                                        <div class="flex items-center space-x-3">
                                            <span class="text-2xl">{{ $cat->emoji }}</span>
                                            <div>
                                                <span x-show="!editing"
                                                    class="font-bold text-slate-800 text-xs">{{ $cat->name }}</span>
                                                <input x-show="editing" type="text"
                                                    class="p-2 border border-slate-200 rounded text-xs font-bold text-slate-800"
                                                    value="{{ $cat->name }}"
                                                    @change="$refs.editName_{{ $cat->id }}.value = $event.target.value" />
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 font-mono text-[10px] font-bold text-slate-500">
                                        <span x-show="!editing">{{ $cat->slug }}</span>
                                        <input x-show="editing" type="text"
                                            class="p-2 border border-slate-200 rounded font-mono text-[10px] font-bold text-slate-800"
                                            value="{{ $cat->slug }}"
                                            @change="$refs.editSlug_{{ $cat->id }}.value = $event.target.value" />
                                    </td>
                                    <td class="py-4">
                                        <span x-show="!editing"
                                            class="text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-full border border-slate-100">
                                            {{ $cat->mapped_fields }}
                                        </span>
                                        <input x-show="editing" type="text"
                                            class="p-2 border border-slate-200 rounded text-xs font-bold text-slate-800 w-full"
                                            value="{{ $cat->mapped_fields }}"
                                            @change="$refs.editFields_{{ $cat->id }}.value = $event.target.value" />
                                    </td>
                                    <td class="py-4 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <!-- Edit Mode Trigger -->
                                            <button x-show="!editing" @click="editing = true"
                                                class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-800 px-3 py-1.5 rounded-lg hover:bg-indigo-50 transition-all select-none">
                                                Edit
                                            </button>

                                            <!-- Edit Save/Cancel Form -->
                                            <form x-show="editing"
                                                action="{{ route('admin.lens.approvals.science-category.update', $cat->id) }}?tab=categories"
                                                method="POST" class="inline" x-cloak>
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="name" x-ref="editName_{{ $cat->id }}"
                                                    value="{{ $cat->name }}" />
                                                <input type="hidden" name="slug" x-ref="editSlug_{{ $cat->id }}"
                                                    value="{{ $cat->slug }}" />
                                                <input type="hidden" name="emoji" value="{{ $cat->emoji }}" />
                                                <input type="hidden" name="mapped_fields" x-ref="editFields_{{ $cat->id }}"
                                                    value="{{ $cat->mapped_fields }}" />

                                                <button type="submit"
                                                    class="text-[10px] font-black uppercase text-emerald-600 hover:text-emerald-800 px-3 py-1.5 rounded-lg hover:bg-emerald-50 transition-all select-none">
                                                    Save
                                                </button>
                                            </form>
                                            <button x-show="editing" @click="editing = false"
                                                class="text-[10px] font-black uppercase text-slate-500 hover:text-slate-800 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition-all select-none"
                                                x-cloak>
                                                Cancel
                                            </button>

                                            <!-- Delete Form -->
                                            <form
                                                action="{{ route('admin.lens.approvals.science-category.destroy', $cat->id) }}?tab=categories"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this category?')"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-[10px] font-black uppercase text-rose-600 hover:text-rose-800 px-3 py-1.5 rounded-lg hover:bg-rose-50 transition-all select-none">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Connections Tab --}}
        <div x-show="activeTab === 'connections'" class="space-y-4" x-cloak>
            @if($pendingConnections->count() > 0)
                <div class="grid grid-cols-1 gap-6">
                    @foreach($pendingConnections as $conn)
                        <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm space-y-4">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-50 pb-4">
                                <div>
                                    <h4 class="font-bold text-slate-900 text-lg">{{ $conn->title }}</h4>
                                    <span class="text-xs text-slate-500 font-medium">Category / Detail:
                                        {{ $conn->extra_info }}</span>
                                </div>
                                <div class="flex items-center space-x-2 shrink-0">
                                    <span
                                        class="text-xs font-black uppercase tracking-widest bg-purple-50 text-purple-800 border border-purple-100 px-3 py-1 rounded-full">
                                        Surah {{ $conn->surah_name }} ({{ $conn->surah_number }}:{{ $conn->verse_number }})
                                    </span>
                                    <a href="{{ route('lens.verse', [$conn->surah_number, $conn->verse_number]) }}" target="_blank"
                                        class="p-2 border border-slate-100 hover:border-emerald-500 rounded-xl transition-all text-slate-400 hover:text-emerald-600"
                                        title="View Context">
                                        👁️ Context
                                    </a>
                                </div>
                            </div>

                            <p
                                class="text-slate-600 text-sm leading-relaxed whitespace-pre-line bg-slate-50/50 p-6 rounded-2xl border border-slate-50">
                                {{ $conn->content }}
                            </p>

                            <div class="flex items-center justify-end space-x-3 pt-2 border-t border-slate-50">
                                <form
                                    action="{{ route('admin.lens.approvals.connection.approve', [$conn->table, $conn->id]) }}?tab=connections"
                                    method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-widest px-5 py-3 rounded-xl transition-all shadow-md">
                                        Approve (Exact Match)
                                    </button>
                                </form>
                                <form
                                    action="{{ route('admin.lens.approvals.connection.reject', [$conn->table, $conn->id]) }}?tab=connections"
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this connection link?')">
                                    @csrf
                                    <button type="submit"
                                        class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-100 font-black text-xs uppercase tracking-widest px-5 py-3 rounded-xl transition-all">
                                        Reject & Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $pendingConnections->links() }}
                </div>
            @else
                <div class="text-center py-16 bg-white rounded-3xl border border-slate-100 space-y-3">
                    <div class="text-4xl">✅</div>
                    <h4 class="font-bold text-slate-700 text-sm">No connection links pending validation</h4>
                    <p class="text-xs text-slate-400 max-w-xs mx-auto leading-relaxed">All active database linkages are verified
                        exact matches.</p>
                </div>
            @endif
        </div>
    </div>
@endsection