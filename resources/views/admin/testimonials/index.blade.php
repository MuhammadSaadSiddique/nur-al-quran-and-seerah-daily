@extends('layouts.app')

@section('title', 'Admin - Manage Testimonials')

@section('content')
<div class="max-w-7xl mx-auto animate-fadeIn">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Manage Testimonials</h1>
            <p class="text-slate-500 mt-1">Add and curate user feedback to display on the landing page.</p>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="flex space-x-4 mb-8 border-b border-slate-200">
        <a href="{{ route('admin.questions.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">Manage Questions</a>
        <a href="{{ route('admin.users.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">User Statistics</a>
        <a href="{{ route('admin.themes.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">Manage Themes</a>
        <a href="{{ route('admin.testimonials.index') }}" class="py-3 px-4 border-b-2 font-semibold text-sm border-emerald-600 text-emerald-600">Testimonials</a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl font-medium animate-fadeIn">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Add New Testimonial --}}
        <div class="lg:col-span-1">
            <div class="bg-white/70 backdrop-blur-xl border border-slate-200 rounded-3xl p-6 shadow-xl sticky top-8">
                <h2 class="text-xl font-black text-slate-800 mb-6">Add Testimonial</h2>
                <form action="{{ route('admin.testimonials.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400">User Name</label>
                        <input type="text" name="name" placeholder="John Doe" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-emerald-500 transition-all font-medium" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400">Feedback / Quote</label>
                        <textarea name="feedback" rows="4" placeholder="Their inspiring words here..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-emerald-500 transition-all font-medium resize-none" required></textarea>
                    </div>
                    <button type="submit" class="w-full bg-slate-900 text-white py-3 rounded-xl font-black shadow-lg hover:bg-emerald-900 transition-all transform hover:-translate-y-0.5 mt-2">Publish Testimonial</button>
                </form>
            </div>
        </div>

        {{-- Testimonials List --}}
        <div class="lg:col-span-2 space-y-4">
            @forelse($testimonials as $testimonial)
                <div class="bg-white/70 backdrop-blur-xl border border-slate-200 rounded-3xl p-6 shadow-lg group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-700 font-black text-sm">
                                {{ substr($testimonial->name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800">{{ $testimonial->name }}</h3>
                                <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">
                                    {{ $testimonial->created_at->format('M d, Y') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <form action="{{ route('admin.testimonials.update', $testimonial) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="name" value="{{ $testimonial->name }}">
                                <input type="hidden" name="feedback" value="{{ $testimonial->feedback }}">
                                <select name="is_active" onchange="this.form.submit()" class="bg-emerald-50 border border-emerald-100 rounded-lg text-[10px] font-black uppercase px-2 py-1 outline-none text-emerald-700">
                                    <option value="1" {{ $testimonial->is_active ? 'selected' : '' }}>Public</option>
                                    <option value="0" {{ !$testimonial->is_active ? 'selected' : '' }}>Hidden</option>
                                </select>
                            </form>
                            <form action="{{ route('admin.testimonials.destroy', $testimonial) }}" method="POST" onsubmit="return confirm('Delete this testimonial?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    <p class="text-slate-600 leading-relaxed italic">"{{ $testimonial->feedback }}"</p>
                </div>
            @empty
                <div class="bg-slate-50 border border-dashed border-slate-300 rounded-3xl p-12 text-center text-slate-500">
                    <p class="font-medium">No testimonials yet.</p>
                    <p class="text-sm">Start by adding your first curated user feedback!</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
