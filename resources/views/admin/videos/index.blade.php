@extends('layouts.app')

@section('title', 'Admin Panel - Review & Manage Videos')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn space-y-6"
     x-data="{
        videoModalOpen: false,
        videoEmbedUrl: '',
        videoTitle: '',
        rejectModalOpen: false,
        rejectVideoId: null,
        rejectVideoTitle: ''
     }">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Videos & Quran Research Management</h1>
            <p class="text-slate-500 mt-1">Review community submissions, approve explanatory videos, and publish authentic research connections.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.categories.index', ['type' => 'quran_research']) }}"
                class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold rounded-xl transition-all inline-flex items-center space-x-2 text-sm">
                <span>🏷️</span>
                <span>Manage Categories</span>
            </a>
            <a href="{{ route('welcome', ['tab' => 'videos']) }}" target="_blank"
                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all inline-flex items-center space-x-2 text-sm">
                <span>View Public Gallery</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
            <a href="{{ route('admin.videos.create') }}"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-emerald-500/30 transition-all transform hover:-translate-y-0.5 inline-flex items-center space-x-2 text-sm">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Video from Backend</span>
            </a>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    @include('admin.partials.tabs')

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl font-medium flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('info'))
        <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl font-medium flex items-center space-x-2">
            <span>⚠️</span>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    {{-- Metric Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.videos.index', ['status' => 'pending']) }}" 
           class="p-4 rounded-2xl border transition-all {{ $status === 'pending' ? 'bg-amber-500 text-white border-amber-600 shadow-md ring-2 ring-amber-400' : 'bg-amber-50/70 border-amber-200 text-amber-900 hover:bg-amber-100' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $status === 'pending' ? 'text-amber-100' : 'text-amber-700' }}">Pending Verification</span>
                @if($pendingCount > 0)
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                @endif
            </div>
            <div class="text-3xl font-black mt-2">{{ $pendingCount }}</div>
            <p class="text-xs mt-1 {{ $status === 'pending' ? 'text-amber-100' : 'text-amber-600' }}">Awaiting admin approval</p>
        </a>

        <a href="{{ route('admin.videos.index', ['status' => 'published']) }}"
           class="p-4 rounded-2xl border transition-all {{ $status === 'published' ? 'bg-emerald-600 text-white border-emerald-700 shadow-md ring-2 ring-emerald-400' : 'bg-emerald-50/70 border-emerald-200 text-emerald-900 hover:bg-emerald-100' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $status === 'published' ? 'text-emerald-100' : 'text-emerald-700' }}">Approved & Live</span>
                <span>✨</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $publishedCount }}</div>
            <p class="text-xs mt-1 {{ $status === 'published' ? 'text-emerald-100' : 'text-emerald-600' }}">Published in gallery & app</p>
        </a>

        <a href="{{ route('admin.videos.index', ['status' => 'rejected']) }}"
           class="p-4 rounded-2xl border transition-all {{ $status === 'rejected' ? 'bg-rose-600 text-white border-rose-700 shadow-md ring-2 ring-rose-400' : 'bg-rose-50/70 border-rose-200 text-rose-900 hover:bg-rose-100' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $status === 'rejected' ? 'text-rose-100' : 'text-rose-700' }}">Rejected</span>
                <span>🛑</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $rejectedCount }}</div>
            <p class="text-xs mt-1 {{ $status === 'rejected' ? 'text-rose-100' : 'text-rose-600' }}">Declined submissions</p>
        </a>

        <a href="{{ route('admin.videos.index', ['status' => 'all']) }}"
           class="p-4 rounded-2xl border transition-all {{ $status === 'all' ? 'bg-slate-800 text-white border-slate-900 shadow-md' : 'bg-white border-slate-200 text-slate-800 hover:bg-slate-50' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $status === 'all' ? 'text-slate-300' : 'text-slate-500' }}">All Entries</span>
                <span>🎬</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $allCount }}</div>
            <p class="text-xs mt-1 {{ $status === 'all' ? 'text-slate-300' : 'text-slate-500' }}">Total database entries</p>
        </a>
    </div>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('admin.videos.index') }}"
        class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-wrap gap-3 items-end shadow-sm">
        
        <input type="hidden" name="status" value="{{ $status }}">

        <div class="flex-1 min-w-[200px] space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search title, description, submitter..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
        </div>

        <div class="w-48 space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Category</label>
            <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium focus:border-indigo-500 focus:outline-none">
                <option value="all">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>
                        {{ $cat->emoji ?? '🔬' }} {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-48 space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Surah (Chapter)</label>
            <select name="surah_number" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium focus:border-indigo-500 focus:outline-none">
                <option value="">All Surahs</option>
                @foreach($surahs as $s)
                    <option value="{{ $s->number }}" {{ request('surah_number') == $s->number ? 'selected' : '' }}>
                        {{ $s->number }}. {{ $s->name_transliteration }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center space-x-2">
            <button type="submit"
                class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">
                Filter
            </button>
            @if(request()->hasAny(['search', 'category', 'surah_number']))
                <a href="{{ route('admin.videos.index', ['status' => $status]) }}"
                   class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800 font-semibold">
                    Clear
                </a>
            @endif
        </div>
    </form>

    {{-- Videos Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        @if($videos->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4">Video Details</th>
                            <th class="py-3.5 px-4">Quran Scope</th>
                            <th class="py-3.5 px-4">Platform & Submitter</th>
                            <th class="py-3.5 px-4 text-center">Community Votes</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($videos as $item)
                            <tr class="hover:bg-slate-50/80 transition-colors {{ $item->status === 'pending' ? 'bg-amber-50/20' : '' }}">
                                <td class="py-4 px-4 max-w-sm">
                                    <div class="flex items-start gap-3">
                                        <button type="button"
                                                @click="videoModalOpen = true; videoEmbedUrl = '{{ $item->embed_url }}'; videoTitle = '{{ addslashes($item->title) }}'"
                                                class="flex-shrink-0 w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center hover:bg-indigo-600 transition-colors shadow-sm group"
                                                title="Preview video">
                                            <span class="text-lg group-hover:scale-110 transition-transform">▶️</span>
                                        </button>
                                        <div class="min-w-0">
                                            <h3 class="font-bold text-slate-900 text-sm leading-tight hover:text-indigo-600 cursor-pointer"
                                                @click="videoModalOpen = true; videoEmbedUrl = '{{ $item->embed_url }}'; videoTitle = '{{ addslashes($item->title) }}'">
                                                {{ $item->title }}
                                            </h3>
                                            @if($item->description)
                                                <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $item->description }}</p>
                                            @endif
                                            <div class="flex items-center gap-2 mt-2">
                                                <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                                    🏷️ {{ ucfirst($item->category) }}
                                                </span>
                                                <a href="{{ $item->video_url }}" target="_blank" class="text-[10px] text-indigo-600 hover:underline flex items-center gap-0.5">
                                                    <span>Open link</span>
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        📖 {{ $item->scope_label }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-xs">
                                    <div class="font-bold text-slate-800 capitalize flex items-center gap-1">
                                        <span>📺</span>
                                        <span>{{ $item->video_platform ?: 'Video' }}</span>
                                    </div>
                                    <div class="text-slate-500 mt-0.5">
                                        By: {{ $item->submitter_name ?: ($item->user ? $item->user->name : 'Community') }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        {{ $item->created_at->format('M d, Y') }}
                                    </div>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-center">
                                    <div class="inline-flex items-center gap-2 font-mono text-xs">
                                        <span class="text-emerald-600 font-bold" title="Approvals">👍 {{ $item->approvals_count }}</span>
                                        <span class="text-slate-300">/</span>
                                        <span class="text-rose-500 font-bold" title="Downvotes">👎 {{ $item->downvotes_count }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if($item->status === 'published')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800">
                                            ● Published
                                        </span>
                                    @elseif($item->status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800">
                                            ⏳ Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800">
                                            ✕ Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap text-right text-xs">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- Approve Button --}}
                                        @if($item->status !== 'published')
                                            <form method="POST" action="{{ route('admin.videos.approve', $item->id) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    onclick="return confirm('Approve and publish this video now?')"
                                                    class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition-colors flex items-center gap-1 shadow-sm">
                                                    <span>✓</span>
                                                    <span>Approve</span>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Reject Button --}}
                                        @if($item->status !== 'rejected')
                                            <button type="button"
                                                @click="rejectModalOpen = true; rejectVideoId = {{ $item->id }}; rejectVideoTitle = '{{ addslashes($item->title) }}'"
                                                class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-lg transition-colors flex items-center gap-1">
                                                <span>✕</span>
                                                <span>Reject</span>
                                            </button>
                                        @endif

                                        {{-- Edit Button --}}
                                        <a href="{{ route('admin.videos.edit', $item->id) }}"
                                           class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors"
                                           title="Edit Video Details">
                                            ✏️
                                        </a>

                                        {{-- Delete Button --}}
                                        <form method="POST" action="{{ route('admin.videos.destroy', $item->id) }}" class="inline"
                                              onsubmit="return confirm('Are you sure you want to permanently delete this video?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-600 rounded-lg transition-colors"
                                                title="Delete Video">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $videos->links() }}
            </div>
        @else
            <div class="text-center py-16 px-4">
                <span class="text-5xl block mb-3">🎬</span>
                <h3 class="text-lg font-bold text-slate-800">No videos found</h3>
                <p class="text-sm text-slate-500 max-w-sm mx-auto mt-1 mb-6">
                    {{ $status === 'pending' ? 'No video submissions are currently awaiting approval.' : 'No videos match your search or filter criteria.' }}
                </p>
                <a href="{{ route('admin.videos.create') }}"
                    class="inline-flex items-center space-x-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-all shadow-md">
                    <span>➕ Add Video from Backend</span>
                </a>
            </div>
        @endif
    </div>

    {{-- Video Preview Modal --}}
    <div x-show="videoModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm p-4 animate-fadeIn"
         @keydown.escape.window="videoModalOpen = false; videoEmbedUrl = ''">
        <div class="bg-slate-900 rounded-3xl max-w-3xl w-full p-4 border border-slate-700 shadow-2xl space-y-3"
             @click.away="videoModalOpen = false; videoEmbedUrl = ''">
            <div class="flex items-center justify-between text-white px-2">
                <h3 class="text-sm font-bold truncate max-w-xl" x-text="videoTitle"></h3>
                <button type="button" @click="videoModalOpen = false; videoEmbedUrl = ''" class="text-slate-400 hover:text-white text-lg font-bold">
                    ✕
                </button>
            </div>
            <div class="relative w-full aspect-video bg-black rounded-2xl overflow-hidden shadow-inner">
                <template x-if="videoEmbedUrl">
                    <iframe :src="videoEmbedUrl"
                            class="w-full h-full border-0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </template>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div x-show="rejectModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fadeIn"
         @keydown.escape.window="rejectModalOpen = false">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 border border-slate-200 shadow-2xl space-y-4"
             @click.away="rejectModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-black text-slate-900">Reject Video Submission</h3>
                <button type="button" @click="rejectModalOpen = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold">
                    ✕
                </button>
            </div>

            <p class="text-xs text-slate-600">
                You are about to reject: <strong class="text-slate-900" x-text="rejectVideoTitle"></strong>
            </p>

            <form :action="'{{ url('/admin/videos') }}/' + rejectVideoId + '/reject'" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700">Rejection Reason / Notes (Optional)</label>
                    <textarea name="reason" rows="3"
                        placeholder="Explain why this video is rejected (e.g. unverified narrator, audio quality, copyright)..."
                        class="w-full p-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="rejectModalOpen = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider px-5 py-2.5 rounded-xl shadow-md transition-all">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
