@extends('layouts.app')

@section('title', 'Admin Panel - Edit Question')

@section('content')
<div class="max-w-4xl mx-auto animate-slideUp">
    <div class="mb-8 flex items-center space-x-4">
        <a href="{{ route('admin.questions.index') }}" class="p-2 bg-white text-slate-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Edit Question</h1>
            <p class="text-slate-500 mt-1">Modify question #{{ $question->id }} &mdash; <span class="font-semibold text-slate-600">{{ $question->question_id }}</span></p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.questions.update', $question) }}" method="POST" class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-3xl p-6 md:p-10 space-y-8">
        @csrf
        @method('PUT')

        {{-- Meta Information --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="space-y-2">
                <label for="type" class="block text-sm font-bold text-slate-700">Quiz Type</label>
                <select id="type" name="type" required class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
                    <option value="PARA" {{ old('type', $question->type) == 'PARA' ? 'selected' : '' }}>Quran Para Quiz</option>
                    <option value="SEERAH" {{ old('type', $question->type) == 'SEERAH' ? 'selected' : '' }}>Seerah Quiz</option>
                </select>
                @error('type') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label for="difficulty" class="block text-sm font-bold text-slate-700">Difficulty</label>
                <select id="difficulty" name="difficulty" required class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
                    <option value="Easy" {{ old('difficulty', $question->difficulty) == 'Easy' ? 'selected' : '' }}>Easy</option>
                    <option value="Medium" {{ old('difficulty', $question->difficulty) == 'Medium' ? 'selected' : '' }}>Medium</option>
                    <option value="Hard" {{ old('difficulty', $question->difficulty) == 'Hard' ? 'selected' : '' }}>Hard</option>
                </select>
                @error('difficulty') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label for="source_info" class="block text-sm font-bold text-slate-700">Source Info (e.g., Para 1)</label>
                <input type="text" id="source_info" name="source_info" value="{{ old('source_info', $question->source_info) }}" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
                @error('source_info') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="space-y-2">
            <label for="theme_id" class="block text-sm font-bold text-slate-700">Theme (Select from managed themes)</label>
            <select id="theme_id" name="theme_id" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
                <option value="">No Theme / General</option>
                <optgroup label="Quran Themes">
                @foreach($themes->where('type', 'PARA') as $t)
                    <option value="{{ $t->id }}" {{ old('theme_id', $question->theme_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
                </optgroup>
                <optgroup label="Seerah Themes">
                @foreach($themes->where('type', 'SEERAH') as $t)
                    <option value="{{ $t->id }}" {{ old('theme_id', $question->theme_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
                </optgroup>
            </select>
            @error('theme_id') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Manage these in <a href="{{ route('admin.themes.index') }}" class="text-emerald-600 hover:underline">Theme Settings</a></p>
        </div>

        {{-- Question Core --}}
        <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 space-y-6">
            <div class="space-y-2">
                <label for="text" class="block text-sm font-bold text-slate-700">Question Text</label>
                <textarea id="text" name="text" rows="3" required class="w-full bg-white border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm resize-none">{{ old('text', $question->text) }}</textarea>
                @error('text') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @for($i = 0; $i < 4; $i++)
                <div class="space-y-2">
                    <label for="option_{{ $i }}" class="block text-sm font-bold text-slate-600">Option {{ $i + 1 }}</label>
                    <input type="text" id="option_{{ $i }}" name="option_{{ $i }}" value="{{ old('option_'.$i, $question->options[$i] ?? '') }}" required class="w-full bg-white border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
                    @error('option_'.$i) <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
                </div>
                @endfor
            </div>

            <div class="space-y-2 border-t border-slate-200 pt-6 mt-6">
                <label class="block text-sm font-bold text-emerald-700">Which option is correct?</label>
                <div class="flex space-x-4">
                    @for($i = 0; $i < 4; $i++)
                    <label class="flex items-center space-x-2 cursor-pointer p-3 border border-slate-200 rounded-xl hover:bg-emerald-50 hover:border-emerald-200 transition-all bg-white flex-1 justify-center">
                        <input type="radio" name="correct_answer_index" value="{{ $i }}" {{ old('correct_answer_index', $question->correct_answer_index) == $i ? 'checked' : '' }} required class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500">
                        <span class="font-bold text-slate-700">Option {{ $i + 1 }}</span>
                    </label>
                    @endfor
                </div>
                @error('correct_answer_index') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Educational Information --}}
        <div class="space-y-6">
            <div class="space-y-2">
                <label for="explanation" class="block text-sm font-bold text-slate-700">Explanation</label>
                <textarea id="explanation" name="explanation" rows="3" required class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm resize-none">{{ old('explanation', $question->explanation) }}</textarea>
                @error('explanation') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label for="reference" class="block text-sm font-bold text-slate-700">Reference (e.g., Quran 2:255)</label>
                <input type="text" id="reference" name="reference" value="{{ old('reference', $question->reference) }}" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
                @error('reference') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Stats (read only) --}}
        <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 flex items-center justify-between">
            <div class="text-sm text-blue-800">
                <span class="font-bold">Stats:</span>
                Answered {{ $question->times_answered }} times &middot;
                Correct {{ $question->times_correct }} times &middot;
                Accuracy {{ $question->accuracy_percent }}%
            </div>
        </div>

        <div class="pt-4 flex justify-between items-center">
            <form action="{{ route('admin.questions.destroy', $question) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this question? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-3 bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold rounded-xl transition-all inline-flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Delete Question</span>
                </button>
            </form>
            <button type="submit" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg hover:shadow-emerald-500/30 transition-all transform hover:-translate-y-0.5">
                Update Question
            </button>
        </div>
    </form>
</div>
@endsection
