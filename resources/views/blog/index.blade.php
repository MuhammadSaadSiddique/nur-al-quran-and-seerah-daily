@extends('layouts.app')

@section('title', $tag ? $tag->name.' — Blog' : ($category ? $category->name.' — Blog' : 'Blog — Quran, Seerah, and the Human Being'))
@section('meta_description', 'Essays from The Eternal Echo on the Quran’s structure, the care of the heart, and the purpose of human life, with links back into the reader.')
@section('canonical', $tag ? route('blog.tag', $tag) : ($category ? route('blog.category', $category) : route('blog.index')))

@section('content')
<div class="space-y-8 pb-12">
    <div class="relative overflow-hidden rounded-[2rem] bg-slate-950 text-white px-6 py-10 md:px-12 md:py-14 border border-slate-800">
        <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="relative max-w-3xl space-y-4">
            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-300">The Eternal Echo · Essays</p>
            <h1 class="text-3xl md:text-5xl font-black tracking-tight">
                @if($tag)
                    {{ $tag->name }}
                @elseif($category)
                    {{ $category->name }}
                @else
                    Notes on the Quran and the human being
                @endif
            </h1>
            <p class="text-slate-300 text-base md:text-lg leading-relaxed">
                Short essays you can read here, then continue in the reader, the pattern pages, the duas, or the Seerah.
            </p>
        </div>
    </div>

    @if($categories->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('blog.index') }}"
                class="px-4 py-2 rounded-full text-sm font-bold {{ !$category && !$tag ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:border-emerald-300' }}">
                All
            </a>
            @foreach($categories as $item)
                <a href="{{ route('blog.category', $item) }}"
                    class="px-4 py-2 rounded-full text-sm font-bold {{ $category && $category->is($item) ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:border-emerald-300' }}">
                    {{ $item->name }}
                </a>
            @endforeach
        </div>
    @endif

    @if($posts->isEmpty())
        <div class="bg-white border border-slate-200 rounded-3xl p-10 text-center space-y-3">
            <h2 class="text-2xl font-black text-slate-800">Essays are being prepared</h2>
            <p class="text-slate-500 max-w-xl mx-auto">Published posts will appear here. Drafts stay in the admin until you choose to post them.</p>
            @auth
                @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.blog.index') }}" class="inline-flex mt-2 px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-bold">Open drafts</a>
                @endif
            @endauth
        </div>
    @else
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <a href="{{ route('blog.show', $post) }}" class="group bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                    <img src="{{ $post->thumbnailUrl() }}" alt="" class="h-44 w-full object-cover bg-slate-900">
                    <div class="p-5 space-y-3">
                        <div class="flex items-center justify-between text-[11px] font-black uppercase tracking-wider text-emerald-700">
                            <span>{{ $post->category?->name ?: 'Essay' }}</span>
                            <span class="text-slate-400">{{ $post->readingMinutes() }} min</span>
                        </div>
                        <h2 class="text-xl font-black text-slate-900 group-hover:text-emerald-700 leading-snug">{{ $post->title }}</h2>
                        <p class="text-sm text-slate-600 leading-relaxed">{{ $post->excerpt }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div>{{ $posts->links() }}</div>
    @endif
</div>
@endsection
