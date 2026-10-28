@extends('layouts.app')

@php
    $editing = $post->exists;
    $linkText = old('links', $editing ? $post->links->map(fn ($link) => $link->label.' | '.$link->url)->implode("\n") : '');
    $tagText = old('tags', $editing ? $post->tags->pluck('name')->implode(', ') : '');
@endphp

@section('title', $editing ? 'Edit blog post' : 'New blog post')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.blog.index') }}" class="text-sm font-bold text-emerald-700">Back to blog</a>
            <h1 class="text-3xl font-extrabold text-slate-800 mt-2">{{ $editing ? 'Edit post' : 'New post' }}</h1>
        </div>
        @if($editing)
            <a href="{{ route('admin.blog.preview', $post) }}" target="_blank" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-semibold text-sm">Preview</a>
        @endif
    </div>

    @include('admin.partials.tabs')

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.blog.update', $post) : route('admin.blog.store') }}" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-3xl p-6 space-y-5">
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        <label class="block space-y-1.5">
            <span class="text-sm font-bold text-slate-700">Title</span>
            <input type="text" name="title" value="{{ old('title', $post->title) }}" required maxlength="180" class="w-full rounded-xl border border-slate-200 px-4 py-2.5">
        </label>

        <label class="block space-y-1.5">
            <span class="text-sm font-bold text-slate-700">Excerpt</span>
            <textarea name="excerpt" rows="3" required maxlength="300" class="w-full rounded-xl border border-slate-200 px-4 py-2.5">{{ old('excerpt', $post->excerpt) }}</textarea>
        </label>

        <div class="grid sm:grid-cols-2 gap-4">
            <label class="block space-y-1.5">
                <span class="text-sm font-bold text-slate-700">Category</span>
                <select name="blog_category_id" class="w-full rounded-xl border border-slate-200 px-4 py-2.5">
                    <option value="">None</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('blog_category_id', $post->blog_category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block space-y-1.5">
                <span class="text-sm font-bold text-slate-700">Or add a category</span>
                <input type="text" name="new_category" value="{{ old('new_category') }}" maxlength="80" placeholder="Quran" class="w-full rounded-xl border border-slate-200 px-4 py-2.5">
            </label>
        </div>

        <label class="block space-y-1.5">
            <span class="text-sm font-bold text-slate-700">Tags</span>
            <input type="text" name="tags" value="{{ $tagText }}" maxlength="300" placeholder="ring composition, ayat al-kursi" class="w-full rounded-xl border border-slate-200 px-4 py-2.5">
            <span class="text-xs text-slate-400">Separate tags with commas.</span>
        </label>

        <label class="block space-y-1.5">
            <span class="text-sm font-bold text-slate-700">Body</span>
            <textarea name="body" rows="18" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 font-mono text-sm">{{ old('body', $post->body) }}</textarea>
            <span class="text-xs text-slate-400">HTML paragraphs are fine. Plain text with blank lines becomes paragraphs.</span>
        </label>

        <label class="block space-y-1.5">
            <span class="text-sm font-bold text-slate-700">Links</span>
            <textarea name="links" rows="6" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 font-mono text-sm">{{ $linkText }}</textarea>
            <span class="text-xs text-slate-400">One per line: Label | /patterns/2/255 or Label | https://quran.com/2/255</span>
        </label>

        <label class="block space-y-1.5">
            <span class="text-sm font-bold text-slate-700">Thumbnail</span>
            @if($editing && $post->thumbnail_path)
                <img src="{{ $post->thumbnailUrl() }}" alt="" class="h-32 w-full object-cover rounded-2xl border border-slate-200 bg-slate-900">
            @endif
            <input type="file" name="thumbnail" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm">
            <span class="text-xs text-slate-400">Leave this empty to keep the branded card with the title.</span>
        </label>

        <div class="grid sm:grid-cols-2 gap-4">
            <label class="block space-y-1.5">
                <span class="text-sm font-bold text-slate-700">SEO title</span>
                <input type="text" name="seo_title" value="{{ old('seo_title', $post->seo_title) }}" maxlength="70" class="w-full rounded-xl border border-slate-200 px-4 py-2.5">
            </label>
            <label class="block space-y-1.5">
                <span class="text-sm font-bold text-slate-700">Status</span>
                <select name="status" class="w-full rounded-xl border border-slate-200 px-4 py-2.5">
                    <option value="draft" @selected(old('status', $post->status) === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                </select>
            </label>
        </div>

        <label class="block space-y-1.5">
            <span class="text-sm font-bold text-slate-700">Meta description</span>
            <textarea name="meta_description" rows="2" maxlength="160" class="w-full rounded-xl border border-slate-200 px-4 py-2.5">{{ old('meta_description', $post->meta_description) }}</textarea>
        </label>

        <button class="px-5 py-3 rounded-xl bg-emerald-600 text-white font-bold">Save</button>
    </form>
</div>
@endsection
