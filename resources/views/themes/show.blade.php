@extends('layouts.app')

@section('title', $theme->name . ' - Islamic Knowledge & Quranic Insights')
@section('meta_description', $theme->description ?? 'Explore in-depth questions and knowledge regarding ' . $theme->name . '. Build your understanding of this Islamic theme with our AI-powered learning platform.')

@push('styles')
<style>
    .theme-header {
        background: linear-gradient(135deg, {{ $theme->type === 'PARA' ? '#0c4a6e 0%, #075985 100%' : '#78350f 0%, #92400e 100%' }});
    }
    .question-card {
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .question-card:hover {
        transform: translateY(-8px) scale(1.02);
    }
    .arabic-flourish {
        font-family: 'Amiri', serif;
    }
</style>
@endpush

@section('content')
<div class="animate-fadeIn">
    {{-- Dynamic Theme Header --}}
    <div class="relative rounded-[3.5rem] overflow-hidden mb-12 shadow-2xl theme-header text-white">
        <div class="absolute inset-0 opacity-10 hero-pattern"></div>
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-[80px] -mr-32 -mt-32"></div>
        
        <div class="relative z-10 p-8 md:p-16">
            <nav class="flex mb-8 text-[10px] font-black uppercase tracking-[0.2em] text-white/60" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-3">
                    <li><a href="{{ route('themes.index') }}" class="hover:text-white transition-colors">Themes</a></li>
                    <li><span class="text-white/20">/</span></li>
                    <li class="text-white">{{ $theme->name }}</li>
                </ol>
            </nav>

            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-8">
                <div class="max-w-3xl">
                    <span class="inline-block px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-white/10 border border-white/20 mb-6">
                        {{ $theme->type === 'PARA' ? 'Al-Quran Al-Kareem' : 'As-Seerah An-Nabawiyyah' }}
                    </span>
                    <h1 class="text-5xl md:text-6xl font-black tracking-tight leading-tight mb-6">
                        {{ $theme->name }}
                    </h1>
                    <p class="text-white/80 text-lg md:text-xl leading-relaxed font-medium">
                        {{ $theme->description ?? 'Delve into the profound wisdom and historical context of ' . $theme->name . '. A dedicated module designed to deepen your spiritual and intellectual understanding.' }}
                    </p>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-6 rounded-[2.5rem] text-center min-w-[140px]">
                        <div class="text-4xl font-black mb-1">{{ $theme->questions()->count() }}</div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-white/50">Questions</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter/Sort Bar (Elegance touch) --}}
    <div class="flex flex-col md:flex-row items-center justify-between mb-10 px-4">
        <h2 class="text-xl font-black text-slate-800 tracking-tight flex items-center space-x-3 mb-4 md:mb-0">
            <span class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 6h16M4 12h16m-7 6h7"/></svg>
            </span>
            <span>Knowledge Challenges</span>
        </h2>
        <div class="flex items-center space-x-2 text-xs font-bold text-slate-400">
            <span>Showing {{ $questions->firstItem() }}-{{ $questions->lastItem() }} of {{ $questions->total() }}</span>
        </div>
    </div>

    @auth
        {{-- Questions Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
            @foreach($questions as $question)
                <div class="group question-card bg-white border border-slate-100 p-8 rounded-[2.5rem] shadow-sm hover:shadow-2xl transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 rounded-full {{ $question->difficulty === 'Easy' ? 'bg-emerald-400' : ($question->difficulty === 'Medium' ? 'bg-amber-400' : 'bg-rose-400') }}"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $question->difficulty }}</span>
                            </div>
                            <span class="text-[10px] font-black text-slate-300">REF #{{ $question->id }}</span>
                        </div>
                        <p class="text-slate-800 text-lg font-bold leading-relaxed mb-8 group-hover:text-emerald-900 transition-colors">
                            {{ $question->text }}
                        </p>
                    </div>
                    
                    <div class="pt-6 border-t border-slate-50">
                        <a href="{{ route('questions.show', $question) }}" class="flex items-center justify-between group/link">
                            <span class="text-sm font-black text-emerald-600">Analyze Question</span>
                            <div class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center group-hover/link:bg-emerald-600 group-hover/link:text-white transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Custom Pagination --}}
        <div class="mb-24 flex justify-center">
            {{ $questions->links() }}
        </div>
    @else
        {{-- Locked State for Public --}}
        <div class="relative bg-white border border-slate-200 rounded-[4rem] p-12 md:p-24 text-center mb-16 overflow-hidden shadow-xl">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-50/50 to-white"></div>
            <div class="relative z-10 flex flex-col items-center">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-3xl flex items-center justify-center mb-8 animate-bounce shadow-lg shadow-emerald-200/50">
                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <h3 class="text-3xl font-black text-slate-800 mb-4 tracking-tight">Premium Knowledge Content</h3>
                <p class="text-slate-500 max-w-lg mx-auto mb-10 text-lg leading-relaxed">
                    The questions and detailed insights for this theme are reserved for our community of learners. Sign in to access over <span class="font-bold text-emerald-600">{{ $questions->total() }} challenges</span> related to {{ $theme->name }}.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-4">
                    <a href="{{ route('login') }}" class="px-12 py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black transition-all shadow-xl shadow-emerald-500/20">
                        Sign In to Unlock
                    </a>
                    <a href="{{ route('login') }}" class="px-12 py-5 bg-white text-slate-700 border border-slate-200 rounded-2xl font-black hover:bg-slate-50 transition-all">
                        Join Our Community
                    </a>
                </div>
            </div>
            
            {{-- Blurred Background Cards (Visual Hint) --}}
            <div class="absolute -bottom-24 left-1/2 transform -translateX-1/2 w-full max-w-5xl opacity-20 blur-md pointer-events-none grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($questions->take(3) as $q)
                <div class="bg-slate-100 h-64 rounded-[2.5rem]"></div>
                @endforeach
            </div>
        </div>
    @endauth

    {{-- Bottom Elegant Call to Action --}}
    <div class="bg-emerald-50 rounded-[4rem] p-12 md:p-20 text-center relative overflow-hidden border border-emerald-100 shadow-inner">
        <div class="absolute top-0 left-0 w-32 h-32 bg-emerald-200/50 rounded-br-full"></div>
        <div class="absolute bottom-0 right-0 w-32 h-32 bg-emerald-200/50 rounded-tl-full"></div>
        
        <h3 class="text-3xl md:text-4xl font-black text-emerald-900 mb-6 tracking-tight">Ready to test your knowledge?</h3>
        <p class="text-emerald-700/70 max-w-2xl mx-auto mb-10 text-lg font-medium">
            Take a personalized quiz on <span class="font-black text-emerald-800">{{ $theme->name }}</span> and track your progress against thousands of other learners.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('login') }}" class="w-full sm:w-auto px-10 py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black transition-all shadow-xl shadow-emerald-600/20">
                Start Theme Quiz
            </a>
            <a href="{{ route('themes.index') }}" class="w-full sm:w-auto px-10 py-5 bg-white text-emerald-700 border border-emerald-200 rounded-2xl font-black hover:bg-emerald-100 transition-all">
                Back to All Themes
            </a>
        </div>
    </div>
</div>

{{-- Structured Data remains the same --}}
@push('scripts')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Article",
  "headline": "{{ $theme->name }} - Islamic Theme Guide",
  "description": "{{ $theme->description ?? 'Study and practice questions related to ' . $theme->name }}",
  "author": { "@@type": "Organization", "name": "The Eternal Echo" },
  "publisher": {
    "@@type": "Organization",
    "name": "The Eternal Echo",
    "logo": { "@@type": "ImageObject", "url": "{{ url('/logo.png') }}" }
  },
  "mainEntityOfPage": { "@@type": "WebPage", "@@id": "{{ url()->current() }}" }
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "BreadcrumbList",
  "itemListElement": [{
    "@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"
  },{
    "@@type": "ListItem", "position": 2, "name": "Themes", "item": "{{ route('themes.index') }}"
  },{
    "@@type": "ListItem", "position": 3, "name": "{{ $theme->name }}", "item": "{{ url()->current() }}"
  }]
}
</script>
@endpush
@endsection
