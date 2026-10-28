@extends('layouts.app')

@section('title', 'Admin — Blog')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Blog</h1>
            <p class="text-slate-500 mt-1">Drafts stay private. Publish a post when you want it on the site and in the sitemap.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('blog.index') }}" target="_blank" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-semibold text-sm">View public blog</a>
            <a href="{{ route('admin.blog.create') }}" class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold text-sm">New post</a>
        </div>
    </div>

    @include('admin.partials.tabs')

    <div class="grid grid-cols-2 gap-4">
        <a href="{{ route('admin.blog.index', ['status' => 'draft']) }}" class="p-4 rounded-2xl border {{ $status === 'draft' ? 'bg-amber-500 text-white border-amber-600' : 'bg-amber-50 border-amber-200 text-amber-900' }}">
            <div class="text-xs font-bold uppercase tracking-wider">Drafts</div>
            <div class="text-3xl font-black mt-2">{{ $draftCount }}</div>
        </a>
        <a href="{{ route('admin.blog.index', ['status' => 'published']) }}" class="p-4 rounded-2xl border {{ $status === 'published' ? 'bg-emerald-600 text-white border-emerald-700' : 'bg-emerald-50 border-emerald-200 text-emerald-900' }}">
            <div class="text-xs font-bold uppercase tracking-wider">Published</div>
            <div class="text-3xl font-black mt-2">{{ $publishedCount }}</div>
        </a>
    </div>

    <form method="POST" action="{{ route('admin.blog.generate') }}" class="bg-white border border-slate-200 rounded-3xl p-5 space-y-3">
        @csrf
        <div>
            <h2 class="text-lg font-black text-slate-800">Generate drafts from topics</h2>
            <p class="text-sm text-slate-500 mt-1">One topic per line, up to five. Each line becomes a private draft with a thumbnail, category, tags, and links. Read it, then publish it yourself.</p>
        </div>
        <textarea name="topics" rows="5" required maxlength="2000" placeholder="Patience in the Quran&#10;The Night of Power" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('topics') }}</textarea>
        <button class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold text-sm">Generate drafts</button>
    </form>

    <form method="GET" action="{{ route('admin.blog.index') }}" class="flex flex-wrap gap-3">
        <input type="search" name="search" value="{{ $search }}" placeholder="Search title or excerpt" class="flex-1 min-w-[16rem] rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
        <select name="status" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
            <option value="all" @selected($status === 'all')>All statuses</option>
            <option value="draft" @selected($status === 'draft')>Drafts</option>
            <option value="published" @selected($status === 'published')>Published</option>
        </select>
        <button class="px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-semibold">Filter</button>
    </form>

    <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-semibold">Post</th>
                    <th class="px-4 py-3 font-semibold hidden md:table-cell">Category</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $post)
                    <tr class="border-t border-slate-100 align-top">
                        <td class="px-4 py-4">
                            <div class="font-bold text-slate-800">{{ $post->title }}</div>
                            <div class="text-slate-500 mt-1">{{ $post->excerpt }}</div>
                            @if($post->tags->isNotEmpty())
                                <div class="text-xs text-slate-400 mt-2">{{ $post->tags->pluck('name')->implode(', ') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-4 hidden md:table-cell text-slate-600">{{ $post->category?->name ?: '—' }}</td>
                        <td class="px-4 py-4">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-black {{ $post->isPublished() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $post->isPublished() ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('admin.blog.preview', $post) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-semibold">Preview</a>
                                <a href="{{ route('admin.blog.edit', $post) }}" class="px-3 py-1.5 rounded-lg bg-slate-900 text-white font-semibold">Edit</a>
                                @if($post->isPublished())
                                    <form method="POST" action="{{ route('admin.blog.unpublish', $post) }}">
                                        @csrf
                                        <button class="px-3 py-1.5 rounded-lg bg-amber-100 text-amber-900 font-semibold">Unpublish</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.blog.publish', $post) }}">
                                        @csrf
                                        <button class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-semibold">Publish</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" onsubmit="return confirm('Delete this post?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 font-semibold">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-slate-500">No posts yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $posts->links() }}
</div>
@endsection
