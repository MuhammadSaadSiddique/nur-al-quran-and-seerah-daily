<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return $this->listing();
    }

    public function category(BlogCategory $category): View
    {
        return $this->listing($category);
    }

    public function tag(BlogTag $tag): View
    {
        return $this->listing(null, $tag);
    }

    public function show(BlogPost $post): View
    {
        abort_unless($post->isPublished(), 404);

        $post->load(['category', 'tags', 'links', 'author']);

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn ($query) => $query->where('blog_category_id', $post->blog_category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('blog.show', [
            'post' => $post,
            'related' => $related,
            'preview' => false,
        ]);
    }

    private function listing(?BlogCategory $category = null, ?BlogTag $tag = null): View
    {
        $posts = BlogPost::published()
            ->with(['category', 'tags'])
            ->when($category, fn ($query) => $query->where('blog_category_id', $category->id))
            ->when($tag, fn ($query) => $query->whereHas('tags', fn ($tags) => $tags->where('blog_tags.id', $tag->id)))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $categories = BlogCategory::whereHas('posts', fn ($query) => $query->published())
            ->orderBy('name')
            ->get();

        return view('blog.index', compact('posts', 'categories', 'category', 'tag'));
    }
}
