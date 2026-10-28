<?php

namespace App\Services;

use App\Models\BlogPost;
use Illuminate\Http\UploadedFile;

class BlogThumbnailService
{
    public function generate(BlogPost $post): ?string
    {
        $directory = public_path('blog-thumbnails');
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return null;
        }

        $palette = $this->palette($post->category?->name);
        $category = htmlspecialchars(strtoupper($post->category?->name ?: 'The Eternal Echo'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $lines = $this->lines(trim($post->title));
        $size = count($lines) > 3 ? 42 : 52;
        $title = '';
        foreach ($lines as $index => $line) {
            $safe = htmlspecialchars($line, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $dy = $index === 0 ? 0 : $size + 16;
            $title .= '<tspan x="72" dy="'.$dy.'">'.$safe.'</tspan>';
        }

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <rect width="1200" height="630" fill="{$palette}"/>
  <rect width="18" height="630" fill="#d4af5f"/>
  <circle cx="1040" cy="150" r="105" fill="none" stroke="#d4af5f" stroke-width="3"/>
  <circle cx="1040" cy="150" r="66" fill="none" stroke="#d4af5f" stroke-width="3"/>
  <text x="72" y="168" fill="#a7f3d0" font-family="Segoe UI, Arial, sans-serif" font-size="18" font-weight="700" letter-spacing="3">{$category}</text>
  <text x="72" y="270" fill="#f8fafc" font-family="Segoe UI, Arial, sans-serif" font-size="{$size}" font-weight="800">{$title}</text>
  <text x="72" y="560" fill="#d4af5f" font-family="Segoe UI, Arial, sans-serif" font-size="22" font-weight="700">The Eternal Echo</text>
</svg>
SVG;

        $relative = 'blog-thumbnails/'.$post->slug.'.svg';
        if (file_put_contents(public_path($relative), $svg) === false) {
            return null;
        }

        return $relative;
    }

    public function storeUpload(BlogPost $post, UploadedFile $file): string
    {
        $directory = public_path('blog-thumbnails');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }

        $relative = 'blog-thumbnails/'.$post->slug.'-upload.'.$extension;
        $file->move($directory, basename($relative));

        return $relative;
    }

    public function delete(?string $path): void
    {
        if (!$path || !str_starts_with($path, 'blog-thumbnails/')) {
            return;
        }

        $full = public_path($path);
        if (is_file($full)) {
            unlink($full);
        }
    }

    private function palette(?string $category): string
    {
        return $category === 'The Human Being' ? '#451a03' : '#022c22';
    }

    /**
     * @return array<int, string>
     */
    private function lines(string $title): array
    {
        $words = preg_split('/\s+/', $title) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $attempt = $line === '' ? $word : $line.' '.$word;
            if ($line !== '' && mb_strlen($attempt) > 26) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $attempt;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return array_slice($lines === [] ? ['Essay'] : $lines, 0, 4);
    }
}
