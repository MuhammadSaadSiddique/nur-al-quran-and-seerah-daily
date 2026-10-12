@extends('layouts.app')

@section('title', 'Admin Panel - Review & Manage Duas')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Duas Management & Approval Queue</h1>
            <p class="text-slate-500 mt-1">Review user-submitted supplications, verify citations, and publish authentic Duas.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.categories.index', ['type' => 'dua']) }}"
                class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold rounded-xl transition-all inline-flex items-center space-x-2 text-sm">
                <span>🏷️</span>
                <span>Manage Categories</span>
            </a>
            <a href="{{ route('duas.index') }}" target="_blank"
                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all inline-flex items-center space-x-2 text-sm">
                <span>View Public Library</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
            <a href="{{ route('admin.duas.create') }}"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-emerald-500/30 transition-all transform hover:-translate-y-0.5 inline-flex items-center space-x-2 text-sm">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Authentic Dua</span>
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
        <a href="{{ route('admin.duas.index', ['status' => 'pending']) }}" 
           class="p-4 rounded-2xl border transition-all {{ $status === 'pending' ? 'bg-amber-500 text-white border-amber-600 shadow-md ring-2 ring-amber-400' : 'bg-amber-50/70 border-amber-200 text-amber-900 hover:bg-amber-100' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $status === 'pending' ? 'text-amber-100' : 'text-amber-700' }}">Pending Review</span>
                @if($pendingCount > 0)
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                @endif
            </div>
            <div class="text-3xl font-black mt-2">{{ $pendingCount }}</div>
            <p class="text-xs mt-1 {{ $status === 'pending' ? 'text-amber-100' : 'text-amber-600' }}">Requires admin decision</p>
        </a>

        <a href="{{ route('admin.duas.index', ['status' => 'approved']) }}"
           class="p-4 rounded-2xl border transition-all {{ $status === 'approved' ? 'bg-emerald-600 text-white border-emerald-700 shadow-md ring-2 ring-emerald-400' : 'bg-emerald-50/70 border-emerald-200 text-emerald-900 hover:bg-emerald-100' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $status === 'approved' ? 'text-emerald-100' : 'text-emerald-700' }}">Approved & Live</span>
                <span>✨</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $approvedCount }}</div>
            <p class="text-xs mt-1 {{ $status === 'approved' ? 'text-emerald-100' : 'text-emerald-600' }}">Active on website & app</p>
        </a>

        <a href="{{ route('admin.duas.index', ['status' => 'rejected']) }}"
           class="p-4 rounded-2xl border transition-all {{ $status === 'rejected' ? 'bg-rose-600 text-white border-rose-700 shadow-md ring-2 ring-rose-400' : 'bg-rose-50/70 border-rose-200 text-rose-900 hover:bg-rose-100' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $status === 'rejected' ? 'text-rose-100' : 'text-rose-700' }}">Rejected</span>
                <span>🛑</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $rejectedCount }}</div>
            <p class="text-xs mt-1 {{ $status === 'rejected' ? 'text-rose-100' : 'text-rose-600' }}">Declined submissions</p>
        </a>

        <a href="{{ route('admin.duas.index', ['status' => 'all']) }}"
           class="p-4 rounded-2xl border transition-all {{ $status === 'all' ? 'bg-slate-800 text-white border-slate-900 shadow-md' : 'bg-white border-slate-200 text-slate-800 hover:bg-slate-50' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $status === 'all' ? 'text-slate-300' : 'text-slate-500' }}">All Duas</span>
                <span>📚</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $allCount }}</div>
            <p class="text-xs mt-1 {{ $status === 'all' ? 'text-slate-300' : 'text-slate-500' }}">Total database entries</p>
        </a>
    </div>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('admin.duas.index') }}"
        class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-wrap gap-3 items-end shadow-sm">
        
        <input type="hidden" name="status" value="{{ $status }}">

        <div class="flex-1 min-w-[200px] space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search title, Arabic, translation, citations..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm">
        </div>

        <div class="w-44 space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Category</label>
            <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium">
                <option value="all">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-36 space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Source</label>
            <select name="source_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium">
                <option value="all">All Sources</option>
                <option value="quran" {{ request('source_type') == 'quran' ? 'selected' : '' }}>Quran</option>
                <option value="hadith" {{ request('source_type') == 'hadith' ? 'selected' : '' }}>Hadith</option>
                <option value="both" {{ request('source_type') == 'both' ? 'selected' : '' }}>Both</option>
            </select>
        </div>

        <div class="flex items-center space-x-2">
            <button type="submit"
                class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">
                Filter
            </button>
            @if(request()->hasAny(['search', 'category', 'source_type']))
                <a href="{{ route('admin.duas.index', ['status' => $status]) }}"
                   class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800 font-semibold">
                    Clear
                </a>
            @endif
        </div>
    </form>

    {{-- Duas Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        @if($duas->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4">Dua Details & Arabic</th>
                            <th class="py-3.5 px-4">Source & Citation</th>
                            <th class="py-3.5 px-4">Submitted By</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($duas as $dua)
                            <tr class="hover:bg-slate-50/80 transition-colors {{ $dua->status === 'pending' ? 'bg-amber-50/20' : '' }}">
                                <td class="py-4 px-4 max-w-md">
                                    <div class="space-y-1">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('admin.duas.edit', $dua->id) }}" class="font-bold text-slate-800 hover:text-emerald-600 transition-colors">
                                                {{ $dua->title }}
                                            </a>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                                {{ $dua->category }}
                                            </span>
                                            @if($dua->is_featured)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">Featured</span>
                                            @endif
                                        </div>
                                        <p class="font-arabic text-base text-slate-900 leading-relaxed line-clamp-1" dir="rtl">
                                            {{ $dua->arabic_text }}
                                        </p>
                                        <p class="text-xs text-slate-500 line-clamp-2">
                                            {{ $dua->translation_en }}
                                        </p>
                                    </div>
                                </td>

                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <div class="flex items-center space-x-1.5">
                                            @if($dua->source_type === 'quran')
                                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200">Quran</span>
                                            @elseif($dua->source_type === 'hadith')
                                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Hadith</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">Both</span>
                                            @endif
                                        </div>
                                        <p class="text-xs font-medium text-slate-700 max-w-[200px] truncate" title="{{ $dua->quran_reference ?: $dua->hadith_reference }}">
                                            {{ $dua->quran_reference ?: ($dua->hadith_reference ?: 'General Prophetic Dua') }}
                                        </p>
                                        @if($dua->hadith_grading)
                                            <p class="text-[11px] text-slate-400">Grade: {{ $dua->hadith_grading }}</p>
                                        @endif
                                    </div>
                                </td>

                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="space-y-0.5">
                                        @if($dua->submitter)
                                            <p class="font-semibold text-slate-800 text-xs">{{ $dua->submitter->name }}</p>
                                            <p class="text-[11px] text-slate-400">{{ $dua->submitter->email }}</p>
                                        @elseif($dua->submitter_name)
                                            <p class="font-semibold text-slate-800 text-xs">{{ $dua->submitter_name }}</p>
                                            <p class="text-[11px] text-slate-400">{{ $dua->submitter_email ?: 'Guest Submission' }}</p>
                                        @else
                                            <p class="text-xs text-slate-400">Admin Seed / Direct</p>
                                        @endif
                                        <p class="text-[10px] text-slate-400">{{ $dua->created_at->diffForHumans() }}</p>
                                    </div>
                                </td>

                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if($dua->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                            Approved
                                        </span>
                                    @elseif($dua->status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200 animate-pulse">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                            Pending Review
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                            Rejected
                                        </span>
                                    @endif
                                    @if($dua->admin_notes)
                                        <p class="text-[10px] text-slate-500 mt-1 max-w-[150px] truncate" title="{{ $dua->admin_notes }}">
                                            Note: {{ $dua->admin_notes }}
                                        </p>
                                    @endif
                                </td>

                                <td class="py-4 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        {{-- Approve Button --}}
                                        @if($dua->status !== 'approved')
                                            <form method="POST" action="{{ route('admin.duas.approve', $dua->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                    class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm hover:shadow"
                                                    title="Approve and Publish to Website & Mobile App">
                                                    ✓ Approve
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Reject Button --}}
                                        @if($dua->status === 'pending')
                                            <form method="POST" action="{{ route('admin.duas.reject', $dua->id) }}" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to reject this Dua submission?');">
                                                @csrf
                                                <button type="submit" 
                                                    class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition-all"
                                                    title="Reject submission">
                                                    ✕ Reject
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Edit Button --}}
                                        <a href="{{ route('admin.duas.edit', $dua->id) }}"
                                            class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs transition-colors"
                                            title="Edit Dua">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>

                                        {{-- Delete Button --}}
                                        <form method="POST" action="{{ route('admin.duas.destroy', $dua->id) }}" class="inline"
                                              onsubmit="return confirm('Permanently delete Dua: {{ addslashes($dua->title) }}? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 bg-slate-100 hover:bg-rose-100 text-slate-500 hover:text-rose-600 rounded-lg text-xs transition-colors"
                                                title="Delete Permanently">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200">
                {{ $duas->links() }}
            </div>
        @else
            <div class="text-center py-16 px-4">
                <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4 text-2xl">
                    🤲
                </div>
                <h3 class="text-base font-bold text-slate-800">No Duas found</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                    No supplications match the selected status or filters. Check back later or create a new authentic Dua.
                </p>
                <div class="mt-6">
                    <a href="{{ route('admin.duas.create') }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-all shadow-md">
                        + Add Authentic Dua
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
