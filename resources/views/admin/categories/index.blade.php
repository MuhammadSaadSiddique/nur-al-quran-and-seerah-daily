@extends('layouts.app')

@section('title', 'Admin Panel - Manage Dua & Quran Research Categories')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Manage Categories</h1>
            <p class="text-slate-500 mt-1">View, create, edit, merge, and manage Dua & Quran Research categories created by users and admins.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" onclick="openMergeModal()"
                class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold rounded-xl transition-all inline-flex items-center space-x-2 text-sm">
                <span>🔀</span>
                <span>Merge Categories</span>
            </button>
            <button type="button" onclick="openCreateModal()"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-emerald-500/30 transition-all transform hover:-translate-y-0.5 inline-flex items-center space-x-2 text-sm">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add New Category</span>
            </button>
        </div>
    </div>

    {{-- Admin Navigation Tabs --}}
    @include('admin.partials.tabs')

    {{-- Alerts --}}
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

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm">
            <p class="font-bold mb-1">Please fix the following issues:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Metric Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.categories.index', ['type' => 'all']) }}"
           class="p-4 rounded-2xl border transition-all {{ $type === 'all' ? 'bg-slate-800 text-white border-slate-900 shadow-md' : 'bg-white border-slate-200 text-slate-800 hover:bg-slate-50' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $type === 'all' ? 'text-slate-300' : 'text-slate-500' }}">All Categories</span>
                <span>🏷️</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $totalCount }}</div>
            <p class="text-xs mt-1 {{ $type === 'all' ? 'text-slate-300' : 'text-slate-500' }}">Dua + Quran Research</p>
        </a>

        <a href="{{ route('admin.categories.index', ['type' => 'dua']) }}"
           class="p-4 rounded-2xl border transition-all {{ $type === 'dua' ? 'bg-emerald-600 text-white border-emerald-700 shadow-md ring-2 ring-emerald-400' : 'bg-emerald-50/70 border-emerald-200 text-emerald-900 hover:bg-emerald-100' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $type === 'dua' ? 'text-emerald-100' : 'text-emerald-700' }}">Dua Categories</span>
                <span>🤲</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $duaCategoriesCount }}</div>
            <p class="text-xs mt-1 {{ $type === 'dua' ? 'text-emerald-100' : 'text-emerald-600' }}">Supplication topics</p>
        </a>

        <a href="{{ route('admin.categories.index', ['type' => 'quran_research']) }}"
           class="p-4 rounded-2xl border transition-all {{ $type === 'quran_research' ? 'bg-indigo-600 text-white border-indigo-700 shadow-md ring-2 ring-indigo-400' : 'bg-indigo-50/70 border-indigo-200 text-indigo-900 hover:bg-indigo-100' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $type === 'quran_research' ? 'text-indigo-100' : 'text-indigo-700' }}">Quran Research</span>
                <span>🔬</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $researchCategoriesCount }}</div>
            <p class="text-xs mt-1 {{ $type === 'quran_research' ? 'text-indigo-100' : 'text-indigo-600' }}">Research & lens topics</p>
        </a>

        <a href="{{ route('admin.categories.index', ['type' => $type, 'active' => $activeFilter === 'inactive' ? '' : 'inactive']) }}"
           class="p-4 rounded-2xl border transition-all {{ $activeFilter === 'inactive' ? 'bg-amber-600 text-white border-amber-700 shadow-md' : 'bg-amber-50/70 border-amber-200 text-amber-900 hover:bg-amber-100' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider {{ $activeFilter === 'inactive' ? 'text-amber-100' : 'text-amber-700' }}">Hidden / Inactive</span>
                <span>🙈</span>
            </div>
            <div class="text-3xl font-black mt-2">{{ $inactiveCount }}</div>
            <p class="text-xs mt-1 {{ $activeFilter === 'inactive' ? 'text-amber-100' : 'text-amber-600' }}">Click to filter inactive</p>
        </a>
    </div>

    {{-- Quick Inline Add Category Bar --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                <span>⚡ Quick Add Category</span>
            </h2>
            <span class="text-xs text-slate-400">Available immediately across Web & Mobile API</span>
        </div>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            @csrf
            <div class="md:col-span-3 space-y-1">
                <label class="text-[11px] font-bold uppercase text-slate-500">Category Type <span class="text-rose-500">*</span></label>
                <select name="type" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 focus:bg-white focus:border-emerald-500">
                    <option value="dua" {{ $type === 'dua' ? 'selected' : '' }}>🤲 Dua Category</option>
                    <option value="quran_research" {{ $type === 'quran_research' ? 'selected' : '' }}>🔬 Quran Research Category</option>
                </select>
            </div>
            <div class="md:col-span-1 space-y-1">
                <label class="text-[11px] font-bold uppercase text-slate-500">Emoji</label>
                <input type="text" name="emoji" placeholder="🤲" maxlength="16"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-center focus:bg-white focus:border-emerald-500">
            </div>
            <div class="md:col-span-3 space-y-1">
                <label class="text-[11px] font-bold uppercase text-slate-500">Category Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required maxlength="100" placeholder="e.g. Parents & Elders or Linguistic Miracles"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500">
            </div>
            <div class="md:col-span-3 space-y-1">
                <label class="text-[11px] font-bold uppercase text-slate-500">Description (Optional)</label>
                <input type="text" name="description" maxlength="500" placeholder="Short description of this category..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:border-emerald-500">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-sm transition-all">
                    + Add Category
                </button>
            </div>
        </form>
    </div>

    {{-- Search & Filter Bar --}}
    <form method="GET" action="{{ route('admin.categories.index') }}"
        class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-wrap gap-3 items-end shadow-sm">
        <div class="flex-1 min-w-[220px] space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Search Categories</label>
            <input type="text" name="search" value="{{ $search }}"
                placeholder="Search by name, slug, description, or contributor..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-sm focus:bg-white focus:border-emerald-500 focus:outline-none">
        </div>

        <div class="w-48 space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Type</label>
            <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:bg-white focus:border-emerald-500 focus:outline-none">
                <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Types</option>
                <option value="dua" {{ $type === 'dua' ? 'selected' : '' }}>🤲 Dua Categories</option>
                <option value="quran_research" {{ $type === 'quran_research' ? 'selected' : '' }}>🔬 Quran Research</option>
            </select>
        </div>

        <div class="w-40 space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Visibility</label>
            <select name="active" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:bg-white focus:border-emerald-500 focus:outline-none">
                <option value="" {{ !$activeFilter ? 'selected' : '' }}>All Statuses</option>
                <option value="active" {{ $activeFilter === 'active' ? 'selected' : '' }}>Active Only</option>
                <option value="inactive" {{ $activeFilter === 'inactive' ? 'selected' : '' }}>Hidden / Inactive</option>
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-xl transition-all">
            Filter
        </button>

        @if($search || $type !== 'all' || $activeFilter)
            <a href="{{ route('admin.categories.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-700 text-sm font-medium">
                Reset
            </a>
        @endif
    </form>

    {{-- Categories Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        @if($categories->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="text-4xl">🏷️</div>
                <h3 class="text-lg font-bold text-slate-700">No categories match your current filter</h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto">Try clearing your search filters or add a new category above.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-4">Category</th>
                            <th class="py-3.5 px-4">Type</th>
                            <th class="py-3.5 px-4">Slug / Key</th>
                            <th class="py-3.5 px-4">Items Linked</th>
                            <th class="py-3.5 px-4">Added By</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm">
                        @foreach($categories as $category)
                            @php
                                $usageCount = $category->type === 'dua'
                                    ? ($duaUsageCounts[$category->name] ?? 0)
                                    : ($researchUsageCounts[$category->slug] ?? 0);
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors {{ !$category->is_active ? 'opacity-60 bg-slate-50/40' : '' }}">
                                <td class="py-4 px-4">
                                    <div class="flex items-start space-x-3">
                                        <span class="text-2xl leading-none mt-0.5">{{ $category->emoji ?: ($category->type === 'dua' ? '🤲' : '🔬') }}</span>
                                        <div>
                                            <div class="font-bold text-slate-800">{{ $category->name }}</div>
                                            @if($category->description)
                                                <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $category->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4">
                                    @if($category->type === 'dua')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            🤲 Dua
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            🔬 Quran Research
                                        </span>
                                    @endif
                                </td>

                                <td class="py-4 px-4">
                                    <code class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-xs font-mono">{{ $category->slug }}</code>
                                </td>

                                <td class="py-4 px-4">
                                    @if($category->type === 'dua')
                                        <a href="{{ route('admin.duas.index', ['category' => $category->name, 'status' => 'all']) }}"
                                           class="inline-flex items-center gap-1.5 font-bold text-slate-700 hover:text-emerald-600 transition-colors">
                                            <span>{{ $usageCount }}</span>
                                            <span class="text-xs font-normal text-slate-400">Duas</span>
                                        </a>
                                    @else
                                        <a href="{{ route('research.contributions.index', ['category' => $category->slug]) }}" target="_blank"
                                           class="inline-flex items-center gap-1.5 font-bold text-slate-700 hover:text-indigo-600 transition-colors">
                                            <span>{{ $usageCount }}</span>
                                            <span class="text-xs font-normal text-slate-400">Research Items</span>
                                        </a>
                                    @endif
                                </td>

                                <td class="py-4 px-4 text-xs text-slate-600">
                                    <div class="font-semibold text-slate-700">
                                        {{ $category->creator ? $category->creator->name : ($category->submitter_name ?: 'System Default') }}
                                    </div>
                                    <div class="text-slate-400">{{ $category->created_at ? $category->created_at->format('M d, Y') : '' }}</div>
                                </td>

                                <td class="py-4 px-4">
                                    @if($category->is_active)
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-700">
                                            Hidden
                                        </span>
                                    @endif
                                </td>

                                <td class="py-4 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        {{-- Edit Button --}}
                                        <button type="button"
                                            onclick='openEditModal(@json($category))'
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition-all">
                                            Edit
                                        </button>

                                        {{-- Toggle Active/Hidden --}}
                                        <form method="POST" action="{{ route('admin.categories.toggle-active', $category->id) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="px-2.5 py-1.5 {{ $category->is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' }} rounded-lg text-xs font-semibold transition-all">
                                                {{ $category->is_active ? 'Hide' : 'Activate' }}
                                            </button>
                                        </form>

                                        {{-- Delete Button --}}
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}" class="inline"
                                            onsubmit="return confirm('Delete category \'{{ addslashes($category->name) }}\'? @if($usageCount > 0) Note: {{ $usageCount }} linked items will keep their category text unless you merge them first. @endif')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition-all">
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

            @if($categories->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50">
                    {{ $categories->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

{{-- Create Category Modal --}}
<div id="createCategoryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-extrabold text-slate-800">Add New Category</h3>
            <button type="button" onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2 space-y-1">
                    <label class="text-xs font-bold text-slate-700">Category Type <span class="text-rose-500">*</span></label>
                    <select name="type" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                        <option value="dua">🤲 Dua Category</option>
                        <option value="quran_research">🔬 Quran Research Category</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Icon / Emoji</label>
                    <input type="text" name="emoji" placeholder="🤲" maxlength="16"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-center">
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Category Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required maxlength="100" placeholder="e.g. Marriage & Righteous Spouse"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Description (Optional)</label>
                <textarea name="description" rows="2" maxlength="500" placeholder="Brief description of what belongs in this category..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-md">Create Category</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Category Modal --}}
<div id="editCategoryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-extrabold text-slate-800">Edit Category</h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form id="editCategoryForm" method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2 space-y-1">
                    <label class="text-xs font-bold text-slate-700">Category Type <span class="text-rose-500">*</span></label>
                    <select id="edit_type" name="type" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold">
                        <option value="dua">🤲 Dua Category</option>
                        <option value="quran_research">🔬 Quran Research Category</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700">Icon / Emoji</label>
                    <input type="text" id="edit_emoji" name="emoji" maxlength="16"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-center">
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Category Name <span class="text-rose-500">*</span></label>
                <input type="text" id="edit_name" name="name" required maxlength="100"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                <p class="text-[11px] text-slate-400">Renaming automatically updates all linked Duas or Quran Research records.</p>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Description</label>
                <textarea id="edit_description" name="description" rows="2" maxlength="500"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm"></textarea>
            </div>

            <div class="flex items-center space-x-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <label for="edit_is_active" class="text-sm font-semibold text-slate-700">Active & Visible to Users</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-md">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Merge Categories Modal --}}
<div id="mergeCategoryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-extrabold text-slate-800">🔀 Merge Duplicate Categories</h3>
            <button type="button" onclick="closeMergeModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.categories.merge') }}" class="space-y-4">
            @csrf
            <p class="text-xs text-slate-500">Move all Duas or Quran Research items from one category into another, and optionally delete the duplicate source category.</p>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Source Category (Merge From) <span class="text-rose-500">*</span></label>
                <select name="source_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                    @foreach(\App\Models\Category::orderBy('type')->orderBy('name')->get() as $cat)
                        <option value="{{ $cat->id }}">[{{ $cat->type === 'dua' ? 'Dua' : 'Research' }}] {{ $cat->emoji }} {{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700">Target Category (Merge Into) <span class="text-rose-500">*</span></label>
                <select name="target_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm">
                    @foreach(\App\Models\Category::orderBy('type')->orderBy('name')->get() as $cat)
                        <option value="{{ $cat->id }}">[{{ $cat->type === 'dua' ? 'Dua' : 'Research' }}] {{ $cat->emoji }} {{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <input type="hidden" name="delete_source" value="0">
                <input type="checkbox" id="delete_source" name="delete_source" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <label for="delete_source" class="text-sm font-semibold text-slate-700">Delete source category after merging</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeMergeModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md">Merge Categories</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        const modal = document.getElementById('createCategoryModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeCreateModal() {
        const modal = document.getElementById('createCategoryModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    function openEditModal(category) {
        const modal = document.getElementById('editCategoryModal');
        const form = document.getElementById('editCategoryForm');
        form.action = '/admin/categories/' + category.id;
        document.getElementById('edit_type').value = category.type;
        document.getElementById('edit_emoji').value = category.emoji || '';
        document.getElementById('edit_name').value = category.name || '';
        document.getElementById('edit_description').value = category.description || '';
        document.getElementById('edit_is_active').checked = !!category.is_active;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeEditModal() {
        const modal = document.getElementById('editCategoryModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    function openMergeModal() {
        const modal = document.getElementById('mergeCategoryModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeMergeModal() {
        const modal = document.getElementById('mergeCategoryModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
