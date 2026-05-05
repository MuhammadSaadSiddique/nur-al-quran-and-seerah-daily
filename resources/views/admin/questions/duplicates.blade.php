@extends('layouts.app')

@section('title', 'Admin - Find Duplicates')

@section('content')
    <div class="max-w-7xl mx-auto animate-fadeIn" x-data="{ 
            tab: 'text',
            selectedCount: 0,
            updateCount() {
                this.selectedCount = document.querySelectorAll('.dup-checkbox:checked').length;
            },
            selectAllInGroup(groupIndex) {
                document.querySelectorAll(`.dup-checkbox[data-group='${groupIndex}']`).forEach(cb => {
                    cb.checked = true;
                });
                this.updateCount();
            },
            selectAllInAllGroups() {
                document.querySelectorAll(`.dup-checkbox`).forEach(cb => {
                    cb.checked = true;
                });
                this.updateCount();
            }
        }">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Duplicate Detection</h1>
                <p class="text-slate-500 mt-1">
                    Identifying redundant questions and themes in your database.
                </p>
            </div>
            <a href="{{ route('admin.questions.index') }}"
                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all inline-flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back to Questions</span>
            </a>
        </div>

        {{-- Admin Navigation Tabs --}}
        @include('admin.partials.tabs')

        @if(session('success'))
            <div
                class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium animate-fadeIn">
                {{ session('success') }}
            </div>
        @endif

        {{-- Sub-tabs for duplicate types --}}
        <div class="flex space-x-2 mb-6">
            <button @click="tab = 'text'"
                :class="tab === 'text' ? 'bg-rose-600 text-white shadow-lg' : 'bg-white text-slate-600 hover:bg-slate-50'"
                class="px-5 py-2 rounded-xl text-sm font-bold transition-all border border-slate-200">
                Duplicate Text ({{ count($duplicateTextGroups) }})
            </button>
            <button @click="tab = 'answers'"
                :class="tab === 'answers' ? 'bg-amber-500 text-white shadow-lg' : 'bg-white text-slate-600 hover:bg-slate-50'"
                class="px-5 py-2 rounded-xl text-sm font-bold transition-all border border-slate-200">
                Same Answers ({{ count($duplicateAnswerGroups) }})
            </button>
            <button @click="tab = 'themes'"
                :class="tab === 'themes' ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-slate-600 hover:bg-slate-50'"
                class="px-5 py-2 rounded-xl text-sm font-bold transition-all border border-slate-200">
                Duplicate Themes ({{ count($duplicateThemeGroups) }})
            </button>
        </div>

        {{-- TEXT DUPLICATES TAB --}}
        <div x-show="tab === 'text'">
            @if($duplicateTextGroups->isEmpty())
                <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl p-12 text-center">
                    <svg class="w-16 h-16 text-emerald-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-xl font-bold text-slate-700">No duplicate text found!</h3>
                </div>
            @else
                <form method="POST" action="{{ route('admin.duplicates.bulk-delete') }}"
                    onsubmit="return confirm('Are you sure?');">
                    @csrf
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm text-slate-500">Groups of questions with near-identical text.</p>
                        <button type="submit" x-show="selectedCount > 0"
                            class="px-5 py-2 bg-rose-600 text-white font-bold rounded-xl shadow-lg transition-all">
                            Delete <span x-text="selectedCount"></span> Duplicates
                        </button>
                    </div>

                    <div class="space-y-6">
                        @foreach($duplicateTextGroups as $groupIndex => $group)
                            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl overflow-hidden">
                                <div class="px-6 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-600">Group {{ $groupIndex + 1 }} ({{ count($group) }}
                                        matches)</span>
                                    <button type="button" @click="selectAllInGroup({{ $groupIndex }})"
                                        class="text-[10px] font-bold text-rose-600 hover:underline">Select All Except First</button>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    @foreach($group as $qIndex => $q)
                                        <div class="px-6 py-4 flex items-start space-x-4 {{ $qIndex === 0 ? 'bg-emerald-50/30' : '' }}">
                                            <label class="flex items-center mt-1">
                                                @if($qIndex === 0)
                                                    <span
                                                        class="w-5 h-5 flex items-center justify-center bg-emerald-100 text-emerald-600 rounded border border-emerald-300 text-[10px] font-bold">KEEP</span>
                                                @else
                                                    <input type="checkbox" name="ids[]" value="{{ $q->id }}"
                                                        class="dup-checkbox w-5 h-5 text-rose-600 rounded" data-group="{{ $groupIndex }}"
                                                        @change="updateCount()">
                                                @endif
                                            </label>
                                            <div class="flex-1 min-w-0">
                                                <div
                                                    class="flex items-center space-x-2 text-[10px] font-bold text-slate-400 mb-1 uppercase">
                                                    <span>#{{ $q->id }}</span>
                                                    <span class="px-1.5 py-0.5 bg-slate-100 rounded">{{ $q->type }}</span>
                                                    <span class="text-blue-500">{{ $q->theme }}</span>
                                                </div>
                                                <p class="text-sm text-slate-800">{{ $q->text }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>
            @endif
        </div>

        {{-- ANSWERS DUPLICATES TAB --}}
        <div x-show="tab === 'answers'">
            @if($duplicateAnswerGroups->isEmpty())
                <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl p-12 text-center">
                    <h3 class="text-xl font-bold text-slate-700">No overlapping answers found!</h3>
                </div>
            @else
                <form method="POST" action="{{ route('admin.duplicates.bulk-delete') }}"
                    onsubmit="return confirm('Are you sure?');">
                    @csrf
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm text-slate-500">Questions sharing the exact same set of options (normalized).</p>
                        <button type="submit" x-show="selectedCount > 0"
                            class="px-5 py-2 bg-rose-600 text-white font-bold rounded-xl shadow-lg transition-all">
                            Delete <span x-text="selectedCount"></span> Selected
                        </button>
                    </div>

                    <div class="space-y-6">
                        @foreach($duplicateAnswerGroups as $groupIndex => $group)
                            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl overflow-hidden">
                                <div class="px-6 py-3 bg-amber-50 border-b border-amber-100 flex items-center justify-between">
                                    <span class="text-xs font-bold text-amber-700">Options Set {{ $groupIndex + 1 }}
                                        ({{ count($group) }} questions)</span>
                                    <div class="text-[10px] text-amber-600 font-medium italic">
                                        {{ implode(', ', $group[0]->options) }}
                                    </div>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    @foreach($group as $qIndex => $q)
                                        <div class="px-6 py-4 flex items-start space-x-4">
                                            <label class="flex items-center mt-1">
                                                <input type="checkbox" name="ids[]" value="{{ $q->id }}"
                                                    class="dup-checkbox w-5 h-5 text-amber-500 rounded" @change="updateCount()">
                                            </label>
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2 text-[10px] font-bold text-slate-400 mb-1">
                                                    <span>#{{ $q->id }}</span>
                                                    <span class="text-emerald-600">CORRECT:
                                                        {{ $q->options[$q->correct_answer_index] }}</span>
                                                </div>
                                                <p class="text-sm text-slate-800">{{ $q->text }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>
            @endif
        </div>

        {{-- THEMES DUPLICATES TAB --}}
        <div x-show="tab === 'themes'" x-data="{ mergeMode: 'grouped', bulkSearch: '', selectedMergeIds: [] }">
            <div class="flex items-center space-x-4 mb-6 p-1 bg-slate-100 rounded-xl w-fit">
                <button @click="mergeMode = 'grouped'"
                    :class="mergeMode === 'grouped' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 rounded-lg text-xs font-bold transition-all">
                    By Similarity ({{ count($duplicateThemeGroups) }} Groups)
                </button>
                <button @click="mergeMode = 'bulk'"
                    :class="mergeMode === 'bulk' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 rounded-lg text-xs font-bold transition-all">
                    Universal Bulk Merge
                </button>
            </div>

            @if($duplicateThemeGroups->isEmpty() && count($allThemes) > 0)
                <div x-show="mergeMode === 'grouped'"
                    class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl p-12 text-center">
                    <h3 class="text-xl font-bold text-slate-700">No automatic duplicates found!</h3>
                    <p class="text-slate-400 mt-2">Use 'Universal Bulk Merge' to manually consolidate themes.</p>
                </div>
            @endif

            {{-- Grouped Merge View --}}
            <div x-show="mergeMode === 'grouped'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($duplicateThemeGroups as $groupIndex => $group)
                    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl overflow-hidden"
                        x-data="{ keepId: '{{ $group[0]->id }}' }">
                        <div class="px-6 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-700">Similarity Group {{ $groupIndex + 1 }}</span>
                        </div>
                        <form method="POST" action="{{ route('admin.themes.merge') }}" class="p-4 space-y-4">
                            @csrf
                            <div class="space-y-3">
                                @foreach($group as $theme)
                                    <div class="flex items-start space-x-3 p-2 rounded-lg transition-colors"
                                        :class="keepId == '{{ $theme->id }}' ? 'bg-emerald-50 border border-emerald-100' : 'hover:bg-slate-50'">
                                        <div class="flex items-center h-5 mt-0.5">
                                            <input type="radio" name="keep_id" value="{{ $theme->id }}" x-model="keepId"
                                                class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500">
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex justify-between">
                                                <span class="text-sm font-bold text-slate-800">{{ $theme->name }}</span>
                                                <span class="text-xs font-bold text-indigo-600">{{ $theme->questions()->count() }}
                                                    Qs</span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 block">ID: #{{ $theme->id }} | Type:
                                                {{ $theme->type }}</span>
                                            <template x-if="keepId != '{{ $theme->id }}'">
                                                <input type="hidden" name="merge_ids[]" value="{{ $theme->id }}">
                                            </template>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="submit"
                                class="w-full text-xs font-bold uppercase tracking-wider text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-2 rounded-xl shadow-md transition-all">Merge
                                into Selected</button>
                        </form>
                    </div>
                @endforeach
            </div>

            {{-- Bulk Merge View --}}
            <div x-show="mergeMode === 'bulk'"
                class="bg-white border border-slate-200 shadow-xl rounded-2xl overflow-hidden">
                <div class="p-6 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-800">Universal Theme Consolidation</h3>
                    <p class="text-xs text-slate-500 mt-1">Select multiple themes to merge and ONE target theme to keep.</p>
                </div>

                <form method="POST" action="{{ route('admin.themes.merge') }}" class="p-6" x-data="{ targetId: '' }">
                    @csrf
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {{-- Target Theme Selection --}}
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-400 mb-3 tracking-widest">1. Choose
                                Target (To Keep)</label>
                            <select name="keep_id" x-model="targetId"
                                class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-indigo-500"
                                required>
                                <option value="">-- Select Target Theme --</option>
                                @foreach($allThemes->sortBy('name') as $theme)
                                    <option value="{{ $theme->id }}">{{ $theme->name }} ({{ $theme->type }}) -
                                        {{ $theme->questions()->count() }} Qs
                                    </option>
                                @endforeach
                            </select>

                            <div x-show="targetId"
                                class="mt-4 p-4 bg-emerald-50 border border-emerald-100 rounded-xl animate-fadeIn">
                                <p class="text-xs text-emerald-700 font-medium">Selected target will be preserved. All other
                                    selected themes will be deleted and their questions moved here.</p>
                            </div>
                        </div>

                        {{-- Source Themes Selection --}}
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-400 mb-3 tracking-widest">2. Select
                                Themes to Merge (To Delete)</label>
                            <div class="relative mb-3">
                                <input type="text" x-model="bulkSearch" placeholder="Filter themes..."
                                    class="w-full pl-10 pr-4 py-2 bg-slate-100 border-none rounded-lg text-sm">
                                <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>

                            <div
                                class="max-h-[300px] overflow-y-auto border border-slate-100 rounded-xl divide-y divide-slate-50">
                                @foreach($allThemes->sortBy('name') as $theme)
                                    <label
                                        class="flex items-center space-x-3 p-3 hover:bg-slate-50 cursor-pointer transition-colors"
                                        x-show="{{ json_encode(strtolower($theme->name)) }}.includes(bulkSearch.toLowerCase()) && targetId != '{{ $theme->id }}'">
                                        <input type="checkbox" name="merge_ids[]" value="{{ $theme->id }}"
                                            @change="updateCount()"
                                            class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                        <div class="flex-1">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-bold text-slate-700">{{ $theme->name }}</span>
                                                <span
                                                    class="text-[10px] bg-slate-100 px-2 py-0.5 rounded-full text-slate-500">{{ $theme->questions()->count() }}
                                                    Qs</span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between">
                        <p class="text-xs text-slate-400 italic">Warning: This action is permanent and will move all
                            questions to the target theme.</p>
                        <button type="submit" :disabled="!targetId"
                            :class="!targetId ? 'opacity-50 cursor-not-allowed bg-slate-300' : 'bg-indigo-600 hover:bg-indigo-700 shadow-lg'"
                            class="px-8 py-3 text-white font-bold rounded-xl transition-all flex items-center space-x-2">
                            <span>Execute Bulk Merge</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection