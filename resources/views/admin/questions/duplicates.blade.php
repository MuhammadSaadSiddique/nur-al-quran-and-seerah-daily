@extends('layouts.app')

@section('title', 'Admin - Find Duplicates')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn" x-data="dupManager()">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Duplicate Questions</h1>
            <p class="text-slate-500 mt-1">
                Found <span class="font-bold text-rose-600">{{ $totalDuplicates }}</span> duplicate questions across
                <span class="font-bold">{{ count($duplicateGroups) }}</span> groups.
            </p>
        </div>
        <a href="{{ route('admin.questions.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-all inline-flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Questions</span>
        </a>
    </div>

    {{-- Navigation Tabs --}}
    <div class="flex space-x-4 mb-6 border-b border-slate-200">
        <a href="{{ route('admin.questions.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">Manage Questions</a>
        <a href="{{ route('admin.users.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">User Statistics</a>
        <a href="{{ route('admin.feedback.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">Feedback</a>
        <a href="{{ route('admin.duplicates') }}" class="py-3 px-4 border-b-2 font-semibold text-sm border-rose-600 text-rose-600">Duplicates</a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium animate-fadeIn">{{ session('success') }}</div>
    @endif

    @if($duplicateGroups->isEmpty())
        <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl p-12 text-center">
            <svg class="w-16 h-16 text-emerald-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-xl font-bold text-slate-700">No duplicates found!</h3>
            <p class="text-slate-400 mt-2">Your question bank is clean.</p>
        </div>
    @else
        {{-- Bulk Delete Form --}}
        <form method="POST" action="{{ route('admin.duplicates.bulk-delete') }}" id="bulkDeleteForm"
            onsubmit="return confirm('Are you sure you want to delete ' + selectedCount + ' selected duplicate questions? This cannot be undone.');">
            @csrf

            <div class="mb-4 flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 mb-2">
                        Tip: For each group, the <span class="font-bold text-emerald-600">first question</span> (best candidate) is kept unchecked. Check the duplicates you want to <span class="font-bold text-rose-600">delete</span>.
                    </p>
                    <button type="button" @click="selectAllInAllGroups()" class="text-sm font-semibold text-rose-700 bg-rose-100 hover:bg-rose-200 border border-rose-200 px-4 py-2 rounded-lg transition-all shadow-sm">
                        Select All Except First (Across All Groups)
                    </button>
                </div>
                <div class="flex items-center">
                    <button type="submit" x-show="selectedCount > 0"
                        class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-xl shadow-lg transition-all inline-flex items-center space-x-2 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span x-text="'Delete ' + selectedCount + ' Duplicates'"></span>
                    </button>
                </div>
            </div>

            <div class="space-y-6">
                @foreach($duplicateGroups as $groupIndex => $group)
                    <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-lg rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 bg-rose-50 border-b border-rose-100 flex items-center justify-between">
                            <div>
                                <span class="text-sm font-bold text-rose-700">Group {{ $groupIndex + 1 }}</span>
                                <span class="text-xs text-rose-500 ml-2">({{ count($group) }} copies)</span>
                            </div>
                            <button type="button" @click="selectAllInGroup({{ $groupIndex }})"
                                class="text-xs font-bold text-rose-600 hover:text-rose-800 bg-rose-100 hover:bg-rose-200 px-3 py-1 rounded-lg transition-all">
                                Select All Except First
                            </button>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach($group as $qIndex => $q)
                                <div class="px-6 py-4 flex items-start space-x-4 {{ $qIndex === 0 ? 'bg-emerald-50/50' : 'hover:bg-rose-50/30' }} transition-colors">
                                    <label class="flex items-center mt-1 cursor-pointer">
                                        @if($qIndex === 0)
                                            <span class="w-5 h-5 flex items-center justify-center bg-emerald-100 text-emerald-600 rounded border border-emerald-300 text-xs font-bold">✓</span>
                                        @else
                                            <input type="checkbox" name="ids[]" value="{{ $q->id }}"
                                                class="dup-checkbox w-5 h-5 text-rose-600 border-slate-300 rounded focus:ring-rose-500 cursor-pointer"
                                                data-group="{{ $groupIndex }}"
                                                @change="updateCount()">
                                        @endif
                                    </label>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2 mb-1">
                                            @if($qIndex === 0)
                                                <span class="px-2 py-0.5 text-[9px] font-black uppercase bg-emerald-100 text-emerald-700 rounded-full border border-emerald-200">Keep</span>
                                            @endif
                                            <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded-full {{ $q->type === 'PARA' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700' }}">{{ $q->type }}</span>
                                            <span class="text-[9px] text-slate-400 font-mono">#{{ $q->id }}</span>
                                            <span class="text-[9px] text-slate-400">{{ $q->source_info }}</span>
                                            @if($q->theme)
                                                <span class="text-[9px] text-blue-500">{{ $q->theme }}</span>
                                            @endif
                                        </div>
                                        <p class="text-sm font-medium text-slate-800">{{ $q->text }}</p>
                                        <div class="mt-1 flex items-center space-x-3 text-xs text-slate-400">
                                            <span>{{ $q->difficulty }}</span>
                                            <span>·</span>
                                            <span class="text-emerald-600 font-bold">Answer: {{ $q->options[$q->correct_answer_index] ?? '?' }}</span>
                                            <span>·</span>
                                            <span>{{ $q->times_answered }} answered, {{ $q->accuracy_percent }}% accuracy</span>
                                            <a href="{{ route('admin.questions.edit', $q) }}" class="text-blue-500 hover:underline">Edit</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Bottom delete button --}}
            <div class="mt-6 flex justify-end" x-show="selectedCount > 0">
                <button type="submit" class="px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl shadow-lg transition-all inline-flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span x-text="'Delete ' + selectedCount + ' Selected Duplicates'"></span>
                </button>
            </div>
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
function dupManager() {
    return {
        selectedCount: 0,
        updateCount() {
            this.selectedCount = document.querySelectorAll('.dup-checkbox:checked').length;
        },
        selectAllInGroup(groupIndex) {
            document.querySelectorAll(`.dup-checkbox[data-group="${groupIndex}"]`).forEach(cb => {
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
    };
}
</script>
@endpush
