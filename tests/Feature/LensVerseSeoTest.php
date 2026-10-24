<?php

namespace Tests\Feature;

use App\Support\LensVerseSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LensVerseSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_and_description_use_the_meaning_and_arabic(): void
    {
        $arabic = 'قُلْ يَا عِبَادِ الَّذِينَ آمَنُوا اتَّقُوا رَبَّكُمْ';
        $translation = 'Say, "O My servants who have believed, fear your Lord. For those who do good in this world is good."';

        $title = LensVerseSeo::title('Az-Zumar', 39, 10, $translation);
        $description = LensVerseSeo::description(
            'Az-Zumar',
            'The Troops',
            39,
            10,
            $arabic,
            $translation
        );

        $this->assertSame('Surah Az-Zumar 39:10 — Say, O My servants who have believed', $title);
        $this->assertStringContainsString('Say, O My servants who have believed', $description);
        $this->assertStringContainsString('Arabic: قُلْ يَا عِبَادِ', $description);
        $this->assertStringContainsString('Surah Az-Zumar (The Troops) 39:10.', $description);
        $this->assertLessThanOrEqual(65, mb_strlen($title));
        $this->assertLessThanOrEqual(160, mb_strlen($description));
        $this->assertStringNotContainsString('"', $title);
        $this->assertStringNotContainsString('"', $description);
    }

    public function test_verse_page_schema_is_valid_json_and_shows_the_translation(): void
    {
        Http::fake([
            'api.quran.com/*' => Http::response([
                'verses' => [
                    [
                        'verse_number' => 10,
                        'translations' => [
                            ['resource_id' => 20, 'text' => 'Say,1 "O My servants who have believed, fear your Lord."'],
                            ['resource_id' => 19, 'text' => 'Say: O My bondmen who believe! Observe your duty to Allah.'],
                        ],
                    ],
                ],
            ]),
        ]);

        $surahId = DB::table('surahs')->insertGetId([
            'number' => 39,
            'name_arabic' => 'الزمر',
            'name_transliteration' => 'Az-Zumar',
            'name_english' => 'The Troops',
            'revelation_type' => 'Meccan',
            'verse_count' => 75,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('verses')->insert([
            'surah_id' => $surahId,
            'verse_number' => 10,
            'text_arabic' => 'قُلْ يَا عِبَادِ الَّذِينَ آمَنُوا اتَّقُوا رَبَّكُمْ',
            'juz' => 23,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('lens.verse', [39, 10]));
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringNotContainsString('\&quot;', $html);
        $this->assertStringNotContainsString('Say,1', $html);
        $response->assertSee('Surah Az-Zumar 39:10 — Say, O My servants who have believed', false);
        $response->assertSee('Arabic: قُلْ يَا عِبَادِ', false);
        $response->assertSee('Say, "O My servants who have believed, fear your Lord."');
        $response->assertSee('The Troops', false);

        preg_match_all('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $html, $matches);
        $this->assertNotEmpty($matches[1]);

        $decoded = [];
        foreach ($matches[1] as $json) {
            $item = json_decode($json, true);
            $this->assertIsArray($item, json_last_error_msg().' in '.$json);
            $decoded[] = $item;
        }

        $article = collect($decoded)->firstWhere('@type', 'Article');
        $this->assertNotNull($article);
        $this->assertSame('Surah Az-Zumar 39:10 — Say, O My servants who have believed', $article['headline']);
        $this->assertStringContainsString('قُلْ يَا عِبَادِ', $article['description']);
        $this->assertSame('ar', $article['about']['inLanguage']);
        $this->assertStringContainsString('قُلْ يَا عِبَادِ', $article['about']['text']);
    }
}
