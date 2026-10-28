@extends('layouts.app')

@section('title', $post->seoTitle().' — The Eternal Echo')
@section('meta_description', $post->seoDescription())
@section('canonical', route('blog.show', $post))
@section('robots', !empty($preview) ? 'noindex, nofollow' : 'index, follow')
@section('og_type', 'article')
@section('og_image', $post->thumbnailUrl())
@section('keywords', $post->tags->pluck('name')->implode(', '))

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@type": "Article",
    "headline": @json($post->title),
    "description": @json($post->seoDescription()),
    "image": @json($post->thumbnailUrl()),
    "datePublished": "{{ optional($post->published_at ?? $post->created_at)->toAtomString() }}",
    "dateModified": "{{ optional($post->updated_at)->toAtomString() }}",
    "author": {
        "@type": "Organization",
        "name": "The Eternal Echo"
    },
    "publisher": {
        "@type": "Organization",
        "name": "The Eternal Echo",
        "logo": {
            "@type": "ImageObject",
            "url": "{{ asset('eternal.png') }}"
        }
    },
    "mainEntityOfPage": "{{ route('blog.show', $post) }}"
}
</script>
@endpush

@section('content')
<article class="max-w-3xl mx-auto pb-16 space-y-8">
    @if(!empty($preview))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
            This is a preview. Visitors cannot see this page until you publish it.
            <a href="{{ route('admin.blog.edit', $post) }}" class="underline">Return to the editor</a>
        </div>
    @endif

    <header class="space-y-4">
        <div class="flex flex-wrap items-center gap-2 text-xs font-black uppercase tracking-wider">
            @if($post->category)
                <a href="{{ route('blog.category', $post->category) }}" class="text-emerald-700">{{ $post->category->name }}</a>
            @endif
            <span class="text-slate-300">·</span>
            <span class="text-slate-400">{{ optional($post->published_at ?? $post->created_at)->format('j M Y') }}</span>
            <span class="text-slate-300">·</span>
            <span class="text-slate-400">{{ $post->readingMinutes() }} min read</span>
        </div>
        <h1 class="text-3xl md:text-5xl font-black tracking-tight text-slate-900 leading-tight">{{ $post->title }}</h1>
        <p class="text-lg text-slate-600 leading-relaxed">{{ $post->excerpt }}</p>
    </header>

    <img src="{{ $post->thumbnailUrl() }}" alt="" class="w-full rounded-[1.75rem] border border-slate-200 bg-slate-900 object-cover max-h-[420px]">

    <div class="blog-body text-slate-700 text-[17px] leading-8">
        {!! $post->body !!}
    </div>

    @if($post->tags->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @foreach($post->tags as $tag)
                <a href="{{ route('blog.tag', $tag) }}" class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-600 text-xs font-bold hover:bg-emerald-50 hover:text-emerald-700">#{{ $tag->name }}</a>
            @endforeach
        </div>
    @endif

    @if($post->links->isNotEmpty())
        <section class="rounded-3xl border border-emerald-100 bg-emerald-50/70 p-6 space-y-4">
            <h2 class="text-lg font-black text-slate-900">Continue from here</h2>
            <ul class="space-y-2">
                @foreach($post->links as $link)
                    <li>
                        <a href="{{ $link->url }}"
                            @if($link->isExternal()) target="_blank" rel="noopener noreferrer" @endif
                            class="font-semibold text-emerald-800 hover:underline">
                            {{ $link->label }}
                            @if($link->isExternal())
                                <span class="text-xs font-bold uppercase tracking-wide text-emerald-600">Source</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if($related->isNotEmpty())
        <section class="space-y-3">
            <h2 class="text-lg font-black text-slate-900">More in this category</h2>
            <div class="grid sm:grid-cols-3 gap-3">
                @foreach($related as $item)
                    <a href="{{ route('blog.show', $item) }}" class="rounded-2xl border border-slate-200 bg-white p-4 hover:border-emerald-300">
                        <p class="font-bold text-slate-800 leading-snug">{{ $item->title }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</article>

<style>
    .blog-body h2 { margin-top: 2rem; margin-bottom: 0.75rem; font-size: 1.5rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
    .blog-body h3 { margin-top: 1.5rem; margin-bottom: 0.5rem; font-size: 1.15rem; font-weight: 800; color: #0f172a; }
    .blog-body p { margin-bottom: 1rem; }
    .blog-body ul, .blog-body ol { margin: 0 0 1rem 1.25rem; }
    .blog-body li { margin: 0.35rem 0; }
    .blog-body a { color: #047857; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; }
    .blog-body blockquote { margin: 1.25rem 0; padding: 0.9rem 1rem; border-left: 4px solid #059669; background: #ecfdf5; border-radius: 0 1rem 1rem 0; color: #064e3b; }
</style>
@endsection
