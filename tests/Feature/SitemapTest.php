<?php

namespace Tests\Feature;

use App\Models\Dua;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_xml_with_public_urls(): void
    {
        Dua::create([
            'title' => 'Dua for Guidance',
            'slug' => 'dua-for-guidance',
            'category' => 'Guidance',
            'arabic_text' => 'اللهم اهدني',
            'translation_en' => 'O Allah, guide me.',
            'status' => 'approved',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/xml', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString(route('donate'), $content);
        $this->assertStringContainsString(route('duas.index'), $content);
        $this->assertStringContainsString(route('daily.dua'), $content);
        $this->assertStringContainsString(route('duas.show', 'dua-for-guidance'), $content);
        $this->assertStringContainsString(route('quiz.learning'), $content);
        $this->assertStringContainsString(route('seerah'), $content);
        $this->assertStringContainsString(route('quran.history'), $content);
    }
}
