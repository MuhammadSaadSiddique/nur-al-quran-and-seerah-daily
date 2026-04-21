@extends('layouts.app')

@section('title', 'Admin Panel - Question Dashboard')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Admin Dashboard</h1>
            <p class="text-slate-500 mt-1">Platform management and insights.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.duplicates') }}" class="px-5 py-2.5 bg-rose-100 hover:bg-rose-200 text-rose-700 font-semibold rounded-xl transition-all inline-flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span>Find Duplicates</span>
            </a>
            <a href="{{ route('admin.questions.create') }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-emerald-500/30 transition-all transform hover:-translate-y-0.5 inline-flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Question</span>
            </a>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="flex space-x-4 mb-6 border-b border-slate-200">
        <a href="{{ route('admin.questions.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.questions.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Manage Questions</a>
        <a href="{{ route('admin.users.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.users.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">User Statistics</a>
        <a href="{{ route('admin.themes.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.themes.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Manage Themes</a>
        <a href="{{ route('admin.testimonials.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.testimonials.*') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Testimonials</a>
        <a href="{{ route('admin.feedback.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.feedback.index') ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Feedback</a>
        <a href="{{ route('admin.duplicates') }}" class="py-3 px-4 border-b-2 font-semibold text-sm {{ request()->routeIs('admin.duplicates') ? 'border-rose-600 text-rose-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">Duplicates</a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium animate-fadeIn">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.questions.index') }}" class="mb-6 bg-white/70 backdrop-blur-xl border border-slate-200 rounded-2xl p-4 flex flex-wrap gap-3 items-end">
        <div class="space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Type</label>
            <select name="type" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm font-medium">
                <option value="">All Types</option>
                <option value="PARA" {{ request('type') == 'PARA' ? 'selected' : '' }}>PARA</option>
                <option value="SEERAH" {{ request('type') == 'SEERAH' ? 'selected' : '' }}>SEERAH</option>
            </select>
        </div>
        <div class="space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Difficulty</label>
            <select name="difficulty" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm font-medium">
                <option value="">All</option>
                <option value="Easy" {{ request('difficulty') == 'Easy' ? 'selected' : '' }}>Easy</option>
                <option value="Medium" {{ request('difficulty') == 'Medium' ? 'selected' : '' }}>Medium</option>
                <option value="Hard" {{ request('difficulty') == 'Hard' ? 'selected' : '' }}>Hard</option>
            </select>
        </div>
        <div class="space-y-1">
            <label class="text-[10px] font-bold uppercase text-slate-400">Theme</label>
            <select name="theme_id" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm font-medium">
                <option value="">All Themes</option>
                @foreach($themes as $t)
                    <option value="{{ $t->id }}" {{ request('theme_id') == $t->id ? 'selected' : '' }}>{{ $t->name }} ({{ $t->type }})</option>
                @endforeach
            </select>
        </div>
        <div class="space-y-1 flex-1 min-w-[200px]">
            <label class="text-[10px] font-bold uppercase text-slate-400">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search question text..." class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-bold hover:bg-slate-700 transition-colors">Filter</button>
        <a href="{{ route('admin.questions.index') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-200 transition-colors">Clear</a>
    </form>

    {{-- Questions Table --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 uppercase text-xs font-bold tracking-wider">
                    <tr>
                        <th scope="col" class="px-4 py-4">Type</th>
                        <th scope="col" class="px-4 py-4">Source</th>
                        <th scope="col" class="px-4 py-4">Difficulty</th>
                        <th scope="col" class="px-4 py-4">Theme</th>
                        <th scope="col" class="px-4 py-4">Question Text</th>
                        <th scope="col" class="px-4 py-4">Correct</th>
                        <th scope="col" class="px-4 py-4">Stats</th>
                        <th scope="col" class="px-4 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($questions as $q)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-lg {{ $q->type === 'PARA' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $q->type }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs font-medium text-slate-500">{{ $q->source_info }}</td>
                            <td class="px-4 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-lg 
                                    {{ $q->difficulty === 'Easy' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                    {{ $q->difficulty === 'Medium' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $q->difficulty === 'Hard' ? 'bg-rose-100 text-rose-700' : '' }}">
                                    {{ $q->difficulty }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs">
                                @if($q->theme_id && $q->themeRecord)
                                    <span class="font-bold text-slate-700">{{ $q->themeRecord->name }}</span>
                                @elseif($q->theme)
                                    <span class="text-slate-400">{{ $q->theme }} (Legacy)</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 font-medium text-slate-800 whitespace-normal min-w-[250px]">
                                {{ Str::limit($q->text, 70) }}
                            </td>
                            <td class="px-4 py-4 text-emerald-600 font-bold text-xs">
                                Option {{ $q->correct_answer_index + 1 }}
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-400">
                                {{ $q->times_answered }} ans · {{ $q->accuracy_percent }}%
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.questions.edit', $q) }}" class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('admin.questions.destroy', $q) }}" method="POST" onsubmit="return confirm('Delete this question?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-100 transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    <p class="font-medium">No questions found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Add your first manual question to get started.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($questions->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
            {{ $questions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
