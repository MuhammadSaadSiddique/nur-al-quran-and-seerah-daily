@extends('layouts.app')

@section('title', 'Quran Research Contributions & Video Gallery')
@section('meta_description', 'Contribute and explore Quranic research, watch verified explanatory videos, and participate in peer verification.')

@section('content')
<div class="space-y-8 pb-12 animate-fadeIn" x-data="{ videoModalOpen: false, currentVideoUrl: '', currentVideoTitle: '', verifyModalOpen: false, selectedContribId: null, selectedContribTitle: '' }">

    {{-- Hero Section --}}
    <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-[2.5rem] p-8 md:p-12 text-white overflow-hidden shadow-2xl border border-indigo-900/50">
        <div class="absolute inset-0 opacity-15 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-indigo-400 via-slate-900 to-slate-950 pointer-events-none"></div>
        <div class="relative z-10 max-w-4xl space-y-4">
            <div class="inline-flex items-center space-x-2 bg-indigo-500/20 backdrop-blur-md rounded-full px-4 py-1.5 border border-indigo-400/30">
                <span class="flex w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                <span class="text-[10px] font-black tracking-widest uppercase text-indigo-200">Community Quran Research</span>
            </div>
            <h1 class="text-3xl md:text-5xl font-black tracking-tight leading-tight">
                Contribute, Watch Videos & <br class="hidden md:block"/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-emerald-200 to-teal-200">Verify Discoveries</span>
            </h1>
            <p class="text-slate-300 font-medium text-sm md:text-base leading-relaxed max-w-2xl">
                Contribute your findings, link lecture & analysis videos to Quranic Surahs and Ayahs, and review pending research. <strong class="text-emerald-400">5 approvals</strong> publish an item to the global library, while downvotes can return items to pending for refinement.
            </p>

            <div class="pt-2 flex flex-wrap gap-3">
                <a href="{{ route('research.contributions.create') }}"
                   class="bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider px-6 py-3.5 rounded-2xl shadow-lg shadow-emerald-900/40 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <span>➕ Add New Contribution / Video</span>
                </a>
                <a href="{{ route('welcome') }}"
                   class="bg-slate-800/80 hover:bg-slate-800 text-slate-200 border border-slate-700/60 font-bold text-xs uppercase tracking-wider px-5 py-3.5 rounded-2xl transition-all flex items-center gap-2">
                    <span>🔍 Browse Quranic Lens</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Main Filter Navigation --}}
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 border-b border-slate-200 pb-4">
        {{-- Status Tabs: Published vs Pending --}}
        <div class="flex items-center gap-2 bg-slate-100 p-1.5 rounded-2xl w-full md:w-auto">
            <a href="{{ route('research.contributions.index', array_merge(request()->query(), ['tab' => 'published'])) }}"
               class="flex-1 md:flex-initial px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition-all text-center {{ $tab !== 'pending' ? 'bg-white text-indigo-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <span>🌟 Published ({{ $publishedCount }})</span>
            </a>
            <a href="{{ route('research.contributions.index', array_merge(request()->query(), ['tab' => 'pending'])) }}"
               class="flex-1 md:flex-initial px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition-all text-center relative {{ $tab === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <span>⏳ Pending Review ({{ $pendingCount }})</span>
                @if($pendingCount > 0 && $tab !== 'pending')
                    <span class="inline-block w-2 h-2 rounded-full bg-amber-500 ml-1"></span>
                @endif
            </a>
        </div>

        {{-- Categories Filter --}}
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('research.contributions.index', array_merge(request()->query(), ['category' => 'all'])) }}"
               class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all {{ ($category === 'all' || empty($category)) ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                🌐 All
            </a>
            @php
                $dynamicResearchCategories = isset($researchCategories) && $researchCategories->isNotEmpty()
                    ? $researchCategories
                    : \App\Models\Category::getQuranResearchCategories();
            @endphp
            @foreach($dynamicResearchCategories as $catObj)
                <a href="{{ route('research.contributions.index', array_merge(request()->query(), ['category' => $catObj->slug])) }}"
                   class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all {{ $category === $catObj->slug ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $catObj->emoji ?: '🔬' }} {{ $catObj->name }}
                </a>
            @endforeach
            <button type="button" onclick="document.getElementById('addResearchCategoryModal').classList.remove('hidden'); document.getElementById('addResearchCategoryModal').classList.add('flex');"
                class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition-all">
                + Add Category
            </button>
            @if(auth()->check() && auth()->user()->is_admin)
                <a href="{{ route('admin.categories.index', ['type' => 'quran_research']) }}"
                   class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition-all">
                    🏷️ Manage Categories
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl font-medium flex items-center gap-2 text-sm">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('info'))
        <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl font-medium flex items-center gap-2 text-sm">
            <span>ℹ️</span>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    {{-- Filter / Search Bar --}}
    <form method="GET" action="{{ route('research.contributions.index') }}" class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="hidden" name="category" value="{{ $category }}">

        <div class="flex-1 relative">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search contributions by keyword, title, scholar, or insight..."
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            <span class="absolute left-3.5 top-3 text-slate-400">🔍</span>
        </div>

        <div class="w-full md:w-64">
            <select name="surah_number" class="w-full py-2.5 px-3 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                <option value="">All Surahs (1-114)</option>
                @foreach($surahs as $s)
                    <option value="{{ $s->number }}" {{ (string)$surahNumber === (string)$s->number ? 'selected' : '' }}>
                        {{ $s->number }}. {{ $s->name_transliteration }} ({{ $s->name_arabic }})
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider px-6 py-2.5 rounded-xl transition-all">
            Filter
        </button>
        @if($search || $surahNumber)
            <a href="{{ route('research.contributions.index', ['tab' => $tab, 'category' => $category]) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition-all flex items-center justify-center">
                Clear
            </a>
        @endif
    </form>

    {{-- Notification Callout if viewing Pending --}}
    @if($tab === 'pending')
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
            <span class="text-2xl">⚖️</span>
            <div>
                <h3 class="text-sm font-bold text-amber-900">Peer Verification Queue</h3>
                <p class="text-xs text-amber-800 mt-0.5">
                    Logged-in members review pending submissions. When a contribution receives <strong class="text-amber-950">5 approvals</strong> with no overriding objections, it is automatically published. Downvoting returns contentious items to pending for revision.
                </p>
            </div>
        </div>
    @endif

    {{-- Contributions Grid --}}
    @if($contributions->isEmpty())
        <div class="text-center py-16 bg-white rounded-3xl border border-slate-200 shadow-sm p-8">
            <span class="text-5xl block mb-3">🎬</span>
            <h3 class="text-lg font-black text-slate-800">No contributions found</h3>
            <p class="text-sm text-slate-500 max-w-md mx-auto mt-1 mb-6">
                {{ $tab === 'pending' ? 'There are currently no items pending peer verification in this category.' : 'Be the first to share research or link an authentic explanatory video!' }}
            </p>
            <a href="{{ route('research.contributions.create') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-wider px-6 py-3 rounded-xl shadow-md transition-all">
                <span>➕ Add Research / Video</span>
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($contributions as $item)
                @php
                    $isPending = $item->status === 'pending';
                    $myReview = $userReviews[$item->id] ?? null;
                    $embedUrl = $item->embed_url;
                @endphp
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden">
                    {{-- Card Media / Video Thumbnail Header --}}
                    @if($item->category === 'video' && $item->video_platform === 'youtube' && $item->video_id)
                        <div class="relative bg-slate-900 aspect-video group cursor-pointer overflow-hidden"
                             @click="videoModalOpen = true; currentVideoUrl = '{{ $embedUrl }}'; currentVideoTitle = '{{ addslashes($item->title) }}'">
                            <img src="https://img.youtube.com/vi/{{ $item->video_id }}/hqdefault.jpg" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 opacity-90 group-hover:opacity-100">
                            <div class="absolute inset-0 bg-black/30 flex items-center justify-center">
                                <div class="w-14 h-14 rounded-full bg-red-600/90 text-white flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                    <svg class="w-7 h-7 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </div>
                            <span class="absolute bottom-2 right-2 bg-black/80 text-white text-[10px] font-black uppercase px-2 py-0.5 rounded-md">YouTube</span>
                        </div>
                    @elseif($item->category === 'video' && !empty($item->video_url))
                        <div class="relative bg-slate-900 aspect-video flex items-center justify-center cursor-pointer group"
                             @click="videoModalOpen = true; currentVideoUrl = '{{ $embedUrl }}'; currentVideoTitle = '{{ addslashes($item->title) }}'">
                            <div class="text-center space-y-2">
                                <span class="text-4xl block group-hover:scale-110 transition-transform">▶️</span>
                                <span class="text-xs text-slate-300 font-bold block">Watch Video</span>
                            </div>
                        </div>
                    @else
                        <div class="bg-gradient-to-r from-slate-100 to-indigo-50 p-4 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-indigo-700">📖 Quranic Research Note</span>
                            <span class="text-xl">🔬</span>
                        </div>
                    @endif

                    {{-- Body --}}
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            {{-- Top Metadata Pill Row --}}
                            <div class="flex items-center justify-between gap-2">
                                <span class="bg-indigo-50 text-indigo-700 font-black text-[10px] uppercase tracking-wider px-2.5 py-1 rounded-full border border-indigo-100">
                                    Surah {{ $item->surah_number }}:{{ $item->verse_number }}
                                </span>
                                @if($isPending)
                                    <span class="bg-amber-100 text-amber-800 font-black text-[10px] uppercase tracking-wider px-2.5 py-0.5 rounded-full border border-amber-200">
                                        ⏳ Pending ({{ $item->approvals_count }}/5 Approvals)
                                    </span>
                                @else
                                    <span class="bg-emerald-100 text-emerald-800 font-black text-[10px] uppercase tracking-wider px-2.5 py-0.5 rounded-full border border-emerald-200">
                                        ✅ Published
                                    </span>
                                @endif
                            </div>

                            <h2 class="text-base font-bold text-slate-900 leading-snug line-clamp-2">
                                {{ $item->title }}
                            </h2>

                            @if($item->description)
                                <p class="text-xs text-slate-600 leading-relaxed line-clamp-3">
                                    {{ $item->description }}
                                </p>
                            @endif
                        </div>

                        {{-- Footer & Verification Block --}}
                        <div class="pt-3 border-t border-slate-100 space-y-3">
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span>By: <strong class="text-slate-700">{{ $item->submitter_name ?: ($item->user->name ?? 'Community Contributor') }}</strong></span>
                                <span>{{ $item->created_at->diffForHumans() }}</span>
                            </div>

                            {{-- Verification Progress Bar --}}
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[10px] font-bold">
                                    <span class="text-emerald-700">👍 {{ $item->approvals_count }} Approval(s)</span>
                                    <span class="text-rose-600">👎 {{ $item->downvotes_count }} Downvote(s)</span>
                                </div>
                                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden flex">
                                    @php
                                        $appPct = min(100, ($item->approvals_count / 5) * 100);
                                    @endphp
                                    <div class="bg-emerald-500 h-full rounded-full transition-all" style="width: {{ $appPct }}%;"></div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2 pt-1">
                                @if($item->category === 'video' && !empty($item->video_url))
                                    <button type="button"
                                            @click="videoModalOpen = true; currentVideoUrl = '{{ $embedUrl }}'; currentVideoTitle = '{{ addslashes($item->title) }}'"
                                            class="flex-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs py-2 rounded-xl transition-all text-center flex items-center justify-center gap-1.5">
                                        <span>▶️ Watch</span>
                                    </button>
                                @endif

                                @auth
                                    <button type="button"
                                            @click="verifyModalOpen = true; selectedContribId = {{ $item->id }}; selectedContribTitle = '{{ addslashes($item->title) }}'"
                                            class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2 rounded-xl transition-all text-center flex items-center justify-center gap-1">
                                        <span>⚖️ Verify / Vote</span>
                                    </button>
                                @else
                                    <a href="{{ route('login') }}" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-2 rounded-xl transition-all text-center">
                                        Log in to Verify
                                    </a>
                                @endauth
                            </div>

                            {{-- Current user's existing vote badge --}}
                            @if($myReview)
                                <div class="text-[10px] font-medium text-slate-500 bg-slate-50 p-2 rounded-lg border border-slate-100">
                                    Your verdict:
                                    <strong class="{{ $myReview->vote === 'approve' ? 'text-emerald-700' : 'text-rose-600' }}">
                                        {{ $myReview->vote === 'approve' ? 'Approved 👍' : 'Downvoted 👎' }}
                                    </strong>
                                    @if($myReview->feedback)
                                        <span class="block italic text-slate-400 mt-0.5">"{{ $myReview->feedback }}"</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $contributions->links() }}
        </div>
    @endif

    {{-- Video Player Modal --}}
    <div x-show="videoModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 animate-fadeIn"
         @keydown.escape.window="videoModalOpen = false; currentVideoUrl = ''">
        <div class="bg-slate-900 rounded-3xl overflow-hidden max-w-4xl w-full border border-slate-800 shadow-2xl space-y-3"
             @click.away="videoModalOpen = false; currentVideoUrl = ''">
            <div class="p-4 flex items-center justify-between border-b border-slate-800">
                <h3 class="text-sm font-bold text-white truncate pr-4" x-text="currentVideoTitle"></h3>
                <button type="button" @click="videoModalOpen = false; currentVideoUrl = ''" class="text-slate-400 hover:text-white text-lg font-bold">
                    ✕
                </button>
            </div>
            <div class="aspect-video w-full bg-black">
                <template x-if="videoModalOpen && currentVideoUrl">
                    <iframe :src="currentVideoUrl" class="w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </template>
            </div>
        </div>
    </div>

    {{-- Verification / Feedback Modal --}}
    @auth
    <div x-show="verifyModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fadeIn"
         @keydown.escape.window="verifyModalOpen = false">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 border border-slate-200 shadow-2xl space-y-4"
             @click.away="verifyModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-black text-slate-900">Peer Verification & Feedback</h3>
                <button type="button" @click="verifyModalOpen = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold">
                    ✕
                </button>
            </div>

            <p class="text-xs text-slate-600">
                Verifying: <strong class="text-slate-900" x-text="selectedContribTitle"></strong>
            </p>

            <form :action="'/quran-research/contributions/' + selectedContribId + '/review'" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-2">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-700">Your Verdict</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 p-3 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-emerald-500 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <input type="radio" name="vote" value="approve" checked class="text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <strong class="text-xs font-bold text-slate-800 block">Approve 👍</strong>
                                <span class="text-[10px] text-slate-500 block">Sound & Beneficial</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-3 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-rose-500 has-[:checked]:border-rose-600 has-[:checked]:bg-rose-50/50">
                            <input type="radio" name="vote" value="downvote" class="text-rose-600 focus:ring-rose-500">
                            <div>
                                <strong class="text-xs font-bold text-slate-800 block">Downvote 👎</strong>
                                <span class="text-[10px] text-slate-500 block">Needs Revision / Questionable</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700">Verification Feedback / Notes (Optional)</label>
                    <textarea name="feedback" rows="3" placeholder="Explain your verdict or provide scholarly references for fellow researchers..."
                              class="w-full p-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="verifyModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider px-5 py-2.5 rounded-xl shadow-md transition-all">
                        Submit Verdict
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endauth

    {{-- Add Quran Research Category Modal --}}
    <div id="addResearchCategoryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-800">🔬 Add Quran Research Category</h3>
                    <p class="text-xs text-slate-500">Create a new topic for Quranic research & discoveries.</p>
                </div>
                <button type="button" onclick="document.getElementById('addResearchCategoryModal').classList.add('hidden'); document.getElementById('addResearchCategoryModal').classList.remove('flex');"
                    class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('categories.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="type" value="quran_research">

                <div class="grid grid-cols-4 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Emoji</label>
                        <input type="text" name="emoji" placeholder="🔬" maxlength="16"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-center">
                    </div>
                    <div class="col-span-3 space-y-1">
                        <label class="text-xs font-bold text-slate-700">Category Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required maxlength="100" placeholder="e.g. Linguistic Miracles, Medicine, Astronomy"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Short Description (Optional)</label>
                    <input type="text" name="description" maxlength="500" placeholder="What kind of Quranic research belongs in this category?"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                </div>

                @guest
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Your Name (Optional)</label>
                    <input type="text" name="submitter_name" maxlength="120" placeholder="Researcher Name"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                </div>
                @endguest

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('addResearchCategoryModal').classList.add('hidden'); document.getElementById('addResearchCategoryModal').classList.remove('flex');"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md">
                        + Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
