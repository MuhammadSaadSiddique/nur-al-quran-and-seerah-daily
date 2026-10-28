<?php

namespace App\Services;

use App\Models\BlogCategory;
use App\Models\BlogLink;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Support\Str;

class BlogTopicGenerator
{
    public function __construct(
        private GeminiService $gemini,
        private BlogThumbnailService $thumbnails,
    ) {}

    /**
     * @return array{error: ?string, created: array<int, string>, skipped: array<int, string>, failed: array<int, string>}
     */
    public function generateFromTopics(string $raw, ?int $authorId): array
    {
        $result = [
            'error' => null,
            'created' => [],
            'skipped' => [],
            'failed' => [],
        ];

        if (trim((string) config('services.gemini.api_key')) === '') {
            $result['error'] = 'Add GEMINI_API_KEY before generating drafts.';

            return $result;
        }

        $topics = $this->topics($raw);
        if ($topics === []) {
            $result['error'] = 'Enter at least one topic, with one topic on each line.';

            return $result;
        }

        if (count($topics) > 5) {
            $result['error'] = 'Generate up to 5 topics at a time.';

            return $result;
        }

        foreach ($topics as $topic) {
            $draft = $this->gemini->generateBlogDraft($topic);
            if (!is_array($draft) || trim((string) ($draft['body'] ?? '')) === '') {
                $result['failed'][] = $topic;
                continue;
            }

            $title = Str::limit(trim((string) ($draft['title'] ?? '')) ?: $topic, 180, '');
            $slug = Str::slug($title) ?: 'post';
            if (BlogPost::where('slug', $slug)->exists()) {
                $result['skipped'][] = $title;
                continue;
            }

            $body = $this->cleanBody((string) $draft['body']);
            if ($body === '') {
                $result['failed'][] = $topic;
                continue;
            }

            $category = $this->category((string) ($draft['category'] ?? 'Quran'));
            $post = BlogPost::create([
                'blog_category_id' => $category->id,
                'author_id' => $authorId,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => Str::limit(trim((string) ($draft['excerpt'] ?? '')) ?: strip_tags($body), 300, ''),
                'body' => $body,
                'seo_title' => $this->blankToNull(Str::limit(trim((string) ($draft['seo_title'] ?? '')), 70, '')),
                'meta_description' => $this->blankToNull(Str::limit(trim((string) ($draft['meta_description'] ?? '')), 160, '')),
                'status' => BlogPost::STATUS_DRAFT,
            ]);

            $this->syncTags($post, $draft['tags'] ?? []);
            $this->syncLinks($post, $draft['links'] ?? []);

            $post->load('category');
            $thumbnail = $this->thumbnails->generate($post);
            if ($thumbnail) {
                $post->thumbnail_path = $thumbnail;
                $post->thumbnail_is_generated = true;
                $post->save();
            }

            $result['created'][] = $post->title;
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function topics(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return collect($lines)
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique(fn ($line) => Str::lower($line))
            ->values()
            ->all();
    }

    private function category(string $name): BlogCategory
    {
        $name = trim(strip_tags($name));
        if ($name === '') {
            $name = 'Quran';
        }
        $name = Str::limit($name, 80, '');
        $slug = Str::slug($name) ?: 'quran';

        return BlogCategory::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );
    }

    /**
     * @param  mixed  $tags
     */
    private function syncTags(BlogPost $post, mixed $tags): void
    {
        $names = collect(is_array($tags) ? $tags : [])
            ->map(fn ($name) => trim(strip_tags((string) $name)))
            ->filter()
            ->unique(fn ($name) => Str::lower($name))
            ->take(5);

        $ids = [];
        foreach ($names as $name) {
            $slug = Str::slug($name) ?: 'tag';
            $tag = BlogTag::firstOrCreate(['slug' => $slug], ['name' => Str::limit($name, 80, '')]);
            $ids[] = $tag->id;
        }

        $post->tags()->sync($ids);
    }

    /**
     * @param  mixed  $links
     */
    private function syncLinks(BlogPost $post, mixed $links): void
    {
        if (!is_array($links)) {
            return;
        }

        $order = 0;
        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }
            $label = trim(strip_tags((string) ($link['label'] ?? '')));
            $url = trim((string) ($link['url'] ?? ''));
            if ($label === '' || !$this->allowedUrl($url)) {
                continue;
            }

            $post->links()->create([
                'label' => Str::limit($label, 120, ''),
                'url' => $url,
                'kind' => str_starts_with($url, '/') ? BlogLink::KIND_INTERNAL : BlogLink::KIND_EXTERNAL,
                'sort_order' => $order,
            ]);
            $order++;
            if ($order >= 8) {
                break;
            }
        }
    }

    private function allowedUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            if (str_contains($url, '..') || str_starts_with($url, '//')) {
                return false;
            }

            return (bool) preg_match('#^/(patterns|lens|duas|seerah|quran-research|quran-history|blog)(/|$)#', $url);
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return $scheme === 'https' && in_array($host, ['quran.com', 'www.quran.com', 'sunnah.com', 'www.sunnah.com'], true);
    }

    private function cleanBody(string $body): string
    {
        $body = strip_tags($body, '<p><br><h2><h3><ul><ol><li><a><strong><em><blockquote>');
        $body = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $body) ?? $body;
        $body = preg_replace_callback('/href\s*=\s*([\'"])(.*?)\1/i', function (array $matches): string {
            return $this->allowedUrl($matches[2]) ? $matches[0] : 'href="#"';
        }, $body) ?? $body;

        return trim($body);
    }

    private function blankToNull(string $value): ?string
    {
        return $value === '' ? null : $value;
    }
}
