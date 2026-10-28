<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogLink;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\BlogThumbnailService;
use App\Services\BlogTopicGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private BlogThumbnailService $thumbnails) {}

    public function index(Request $request): View
    {
        $status = $request->input('status', 'all');
        $search = trim((string) $request->input('search'));

        $posts = BlogPost::with(['category', 'tags'])
            ->when(in_array($status, [BlogPost::STATUS_DRAFT, BlogPost::STATUS_PUBLISHED], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            })
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.blog.index', [
            'posts' => $posts,
            'status' => $status,
            'search' => $search,
            'draftCount' => BlogPost::draft()->count(),
            'publishedCount' => BlogPost::published()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.form', [
            'post' => new BlogPost(['status' => BlogPost::STATUS_DRAFT]),
            'categories' => BlogCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $post = new BlogPost($data);
        $post->author_id = Auth::id();
        $post->slug = $this->uniqueSlug($data['title']);
        $this->applyPublication($post, $data['status']);
        $post->save();

        $post->blog_category_id = $this->resolveCategory($request)?->id;
        $post->save();
        $this->syncTags($post, $request->input('tags'));
        $this->syncLinks($post, $request->input('links'));
        $this->applyThumbnail($post, $request);

        return redirect()->route('admin.blog.index')
            ->with('success', "“{$post->title}” was saved as a {$post->status}.");
    }

    public function generate(Request $request, BlogTopicGenerator $generator): RedirectResponse
    {
        $request->validate([
            'topics' => ['required', 'string', 'max:2000'],
        ]);

        set_time_limit(180);

        $result = $generator->generateFromTopics($request->input('topics'), Auth::id());
        if ($result['error']) {
            return back()->withInput()->with('error', $result['error']);
        }

        if ($result['created'] === []) {
            $detail = $result['failed'] !== []
                ? 'Could not write: '.implode(', ', $result['failed']).'.'
                : 'Those topics already have drafts.';

            return back()->withInput()->with('error', $detail);
        }

        $message = 'Saved '.count($result['created']).' draft'.(count($result['created']) === 1 ? '' : 's').': '.implode(', ', $result['created']).'.';
        if ($result['skipped'] !== []) {
            $message .= ' Already had: '.implode(', ', $result['skipped']).'.';
        }
        if ($result['failed'] !== []) {
            $message .= ' Could not write: '.implode(', ', $result['failed']).'.';
        }

        return redirect()
            ->route('admin.blog.index', ['status' => 'draft'])
            ->with('success', $message);
    }

    public function edit(BlogPost $post): View
    {
        $post->load(['category', 'tags', 'links']);

        return view('admin.blog.form', [
            'post' => $post,
            'categories' => BlogCategory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $data = $this->validated($request);
        $previousThumbnail = $post->thumbnail_path;
        $post->fill($data);
        if ($post->isDirty('title')) {
            $post->slug = $this->uniqueSlug($data['title'], $post->id);
        }
        $post->blog_category_id = $this->resolveCategory($request)?->id;
        $this->applyPublication($post, $data['status']);
        $post->save();

        $this->syncTags($post, $request->input('tags'));
        $this->syncLinks($post, $request->input('links'));
        $this->applyThumbnail($post, $request, $previousThumbnail);

        return redirect()->route('admin.blog.edit', $post)
            ->with('success', "“{$post->title}” was updated.");
    }

    public function publish(BlogPost $post): RedirectResponse
    {
        $post->status = BlogPost::STATUS_PUBLISHED;
        $post->published_at = $post->published_at ?? now();
        $post->save();

        return back()->with('success', "“{$post->title}” is now public.");
    }

    public function unpublish(BlogPost $post): RedirectResponse
    {
        $post->status = BlogPost::STATUS_DRAFT;
        $post->save();

        return back()->with('success', "“{$post->title}” was moved back to drafts.");
    }

    public function preview(BlogPost $post): View
    {
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
            'preview' => true,
        ]);
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $this->thumbnails->delete($post->thumbnail_path);
        $title = $post->title;
        $post->delete();

        return redirect()->route('admin.blog.index')
            ->with('success', "“{$title}” was deleted.");
    }

    /**
     * @return array{title: string, excerpt: string, body: string, seo_title: ?string, meta_description: ?string, status: string}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'excerpt' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:100000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'status' => ['required', 'in:draft,published'],
            'new_category' => ['nullable', 'string', 'max:80'],
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'tags' => ['nullable', 'string', 'max:300'],
            'links' => ['nullable', 'string', 'max:4000'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        return [
            'title' => trim($validated['title']),
            'excerpt' => trim($validated['excerpt']),
            'body' => $this->cleanBody($validated['body']),
            'seo_title' => $this->blankToNull($validated['seo_title'] ?? null),
            'meta_description' => $this->blankToNull($validated['meta_description'] ?? null),
            'status' => $validated['status'],
        ];
    }

    private function applyPublication(BlogPost $post, string $status): void
    {
        $post->status = $status;
        if ($status === BlogPost::STATUS_PUBLISHED) {
            $post->published_at = $post->published_at ?? now();
        }
    }

    private function resolveCategory(Request $request): ?BlogCategory
    {
        $name = trim((string) $request->input('new_category'));
        if ($name !== '') {
            $slug = Str::slug($name) ?: 'category';
            $existing = BlogCategory::where('slug', $slug)->first();
            if ($existing) {
                return $existing;
            }

            return BlogCategory::create([
                'name' => $name,
                'slug' => $slug,
            ]);
        }

        if ($request->filled('blog_category_id')) {
            return BlogCategory::find($request->integer('blog_category_id'));
        }

        return null;
    }

    private function syncTags(BlogPost $post, ?string $raw): void
    {
        $names = collect(explode(',', (string) $raw))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique(fn ($name) => Str::lower($name))
            ->take(12);

        $ids = [];
        foreach ($names as $name) {
            $slug = Str::slug($name) ?: 'tag';
            $tag = BlogTag::where('slug', $slug)->first();
            if (!$tag) {
                $tag = BlogTag::create(['name' => $name, 'slug' => $slug]);
            }
            $ids[] = $tag->id;
        }

        $post->tags()->sync($ids);
    }

    private function syncLinks(BlogPost $post, ?string $raw): void
    {
        $post->links()->delete();
        $lines = preg_split('/\r\n|\r|\n/', (string) $raw) ?: [];
        $order = 0;

        foreach ($lines as $line) {
            if (!str_contains($line, '|')) {
                continue;
            }

            [$label, $url] = array_map('trim', explode('|', $line, 2));
            if ($label === '' || $url === '' || !preg_match('#^(https?://|/)#i', $url)) {
                continue;
            }

            $kind = str_starts_with($url, '/') ? BlogLink::KIND_INTERNAL : BlogLink::KIND_EXTERNAL;
            $host = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($kind === BlogLink::KIND_EXTERNAL && $host && str_contains(parse_url($url, PHP_URL_HOST) ?: '', $host)) {
                $kind = BlogLink::KIND_INTERNAL;
            }

            $post->links()->create([
                'label' => Str::limit($label, 120, ''),
                'url' => $url,
                'kind' => $kind,
                'sort_order' => $order,
            ]);
            $order++;
            if ($order >= 12) {
                break;
            }
        }
    }

    private function applyThumbnail(BlogPost $post, Request $request, ?string $previousPath = null): void
    {
        $post->load('category');

        if ($request->hasFile('thumbnail')) {
            if ($previousPath && $previousPath !== $post->thumbnail_path) {
                $this->thumbnails->delete($previousPath);
            }
            $this->thumbnails->delete($post->thumbnail_path);
            $post->thumbnail_path = $this->thumbnails->storeUpload($post, $request->file('thumbnail'));
            $post->thumbnail_is_generated = false;
            $post->save();

            return;
        }

        if ($post->thumbnail_path && !$post->thumbnail_is_generated) {
            return;
        }

        $generated = $this->thumbnails->generate($post);
        if (!$generated) {
            return;
        }

        if ($previousPath && $previousPath !== $generated) {
            $this->thumbnails->delete($previousPath);
        }

        $post->thumbnail_path = $generated;
        $post->thumbnail_is_generated = true;
        $post->save();
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $suffix = 2;

        while (BlogPost::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function cleanBody(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        if (!str_contains($body, '<')) {
            $parts = preg_split("/\n\s*\n/", $body) ?: [];

            return collect($parts)
                ->map(fn ($part) => trim($part))
                ->filter()
                ->map(fn ($part) => '<p>'.e($part).'</p>')
                ->implode('');
        }

        $body = strip_tags($body, '<p><br><h2><h3><ul><ol><li><a><strong><em><blockquote>');
        $body = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $body) ?? $body;
        $body = preg_replace('/href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', 'href=$1#$1', $body) ?? $body;

        return trim($body);
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
