@extends('layouts.app')

@section('title', 'Admin Panel - Add Question')

@section('content')
<div class="max-w-4xl mx-auto animate-slideUp">
    <div class="mb-8 flex items-center space-x-4">
        <a href="{{ route('admin.questions.index') }}" class="p-2 bg-white text-slate-500 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Add New Question</h1>
            <p class="text-slate-500 mt-1">Insert a new manual question directly into the database.</p>
        </div>
    </div>

    <form action="{{ route('admin.questions.store') }}" method="POST" class="bg-white/70 backdrop-blur-xl border border-slate-200 shadow-xl rounded-3xl p-6 md:p-10 space-y-8">
        @csrf

        {{-- Meta Information --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label for="type" class="block text-sm font-bold text-slate-700">Quiz Type</label>
                <select id="type" name="type" required class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
                    <option value="PARA" {{ old('type') == 'PARA' ? 'selected' : '' }}>Quran Para Quiz</option>
                    <option value="SEERAH" {{ old('type') == 'SEERAH' ? 'selected' : '' }}>Seerah Quiz</option>
                </select>
                @error('type') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label for="difficulty" class="block text-sm font-bold text-slate-700">Difficulty</label>
                <select id="difficulty" name="difficulty" required class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
                    <option value="Easy" {{ old('difficulty') == 'Easy' ? 'selected' : '' }}>Easy</option>
                    <option value="Medium" {{ old('difficulty') == 'Medium' ? 'selected' : '' }}>Medium</option>
                    <option value="Hard" {{ old('difficulty') == 'Hard' ? 'selected' : '' }}>Hard</option>
                </select>
                @error('difficulty') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="space-y-2">
            <label for="theme" class="block text-sm font-bold text-slate-700">Theme (Optional)</label>
            <input type="text" id="theme" name="theme" value="{{ old('theme') }}" placeholder="e.g., Stories of Prophets" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
            @error('theme') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
        </div>

        {{-- Question Core --}}
        <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 space-y-6">
            <div class="space-y-2">
                <label for="text" class="block text-sm font-bold text-slate-700">Question Text</label>
                <textarea id="text" name="text" rows="3" required class="w-full bg-white border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm resize-none" placeholder="What is the meaning of...">{{ old('text') }}</textarea>
                @error('text') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @for($i = 0; $i < 4; $i++)
                <div class="space-y-2">
                    <label for="option_{{ $i }}" class="block text-sm font-bold text-slate-600">Option {{ $i + 1 }}</label>
                    <input type="text" id="option_{{ $i }}" name="option_{{ $i }}" value="{{ old('option_'.$i) }}" required class="w-full bg-white border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm" placeholder="Option {{ $i + 1 }} text">
                    @error('option_'.$i) <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
                </div>
                @endfor
            </div>

            <div class="space-y-2 border-t border-slate-200 pt-6 mt-6">
                <label for="correct_answer_index" class="block text-sm font-bold text-emerald-700">Which option is correct?</label>
                <div class="flex space-x-4">
                    @for($i = 0; $i < 4; $i++)
                    <label class="flex items-center space-x-2 cursor-pointer p-3 border border-slate-200 rounded-xl hover:bg-emerald-50 hover:border-emerald-200 transition-all bg-white flex-1 justify-center">
                        <input type="radio" name="correct_answer_index" value="{{ $i }}" {{ old('correct_answer_index') == (string)$i ? 'checked' : '' }} required class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500">
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
                <textarea id="explanation" name="explanation" rows="3" required class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm resize-none" placeholder="Explain why the correct answer is right...">{{ old('explanation') }}</textarea>
                @error('explanation') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label for="reference" class="block text-sm font-bold text-slate-700">Reference (e.g., Quran 2:255)</label>
                    <input type="text" id="reference" name="reference" value="{{ old('reference') }}" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
                    @error('reference') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2">
                    <label for="source_info" class="block text-sm font-bold text-slate-700">Source Info (e.g., Para 1)</label>
                    <input type="text" id="source_info" name="source_info" value="{{ old('source_info') }}" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-sm">
                    @error('source_info') <p class="text-rose-500 text-xs font-bold">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="pt-4 flex justify-end">
            <button type="submit" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg hover:shadow-emerald-500/30 transition-all transform hover:-translate-y-0.5">
                Save Question
            </button>
        </div>
    </form>
</div>
@endsection
