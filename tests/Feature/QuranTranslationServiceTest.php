<?php

namespace Tests\Feature;

use App\Services\QuranTranslationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuranTranslationServiceTest extends TestCase
{
    public function test_surah_translations_are_grouped_by_verse_and_cached(): void
    {
        Http::fake([
            'api.alquran.cloud/*' => Http::response([
                'code' => 200,
                'data' => [
                    [
                        'edition' => [
                            'identifier' => 'en.sahih',
                            'language' => 'en',
                            'englishName' => 'Saheeh International',
                        ],
                        'ayahs' => [
                            ['numberInSurah' => 1, 'text' => 'In the name of Allah, the Entirely Merciful.'],
                            ['numberInSurah' => 2, 'text' => '<b>All praise</b> is due to Allah.'],
                        ],
                    ],
                    [
                        'edition' => [
                            'identifier' => 'ur.jalandhry',
                            'language' => 'ur',
                        ],
                        'ayahs' => [
                            ['numberInSurah' => 1, 'text' => 'شروع الله کا نام لے کر'],
                        ],
                    ],
                    [
                        'edition' => [
                            'identifier' => 'unknown.edition',
                        ],
                        'ayahs' => [
                            ['numberInSurah' => 1, 'text' => 'Should be ignored'],
                        ],
                    ],
                ],
            ]),
        ]);

        $service = app(QuranTranslationService::class);
        $first = $service->forSurah(1);
        $second = $service->forSurah(1);

        $this->assertSame('In the name of Allah, the Entirely Merciful.', $first[1][0]['text']);
        $this->assertSame('en.sahih', $first[1][0]['id']);
        $this->assertSame('ltr', $first[1][0]['direction']);
        $this->assertSame('Jalandhry (Urdu)', $first[1][1]['name']);
        $this->assertSame('rtl', $first[1][1]['direction']);
        $this->assertSame('All praise is due to Allah.', $first[2][0]['text']);
        $this->assertCount(1, $first[2]);
        $this->assertSame($first, $second);
        $this->assertSame($first[1], $service->forVerse(1, 1));

        Http::assertSentCount(1);
    }

    public function test_failed_request_returns_empty_list(): void
    {
        Http::fake([
            'api.alquran.cloud/*' => Http::response(['code' => 500], 500),
        ]);

        $service = app(QuranTranslationService::class);

        $this->assertSame([], $service->forSurah(2));
        $this->assertSame([], $service->forSurah(0));
        $this->assertSame([], $service->forVerse(2, 1));
    }

    public function test_local_translations_are_kept_and_remote_ones_are_appended(): void
    {
        $service = app(QuranTranslationService::class);

        $merged = $service->mergeWithLocal(
            [
                ['author_name' => 'Local Scholar', 'text' => 'Local meaning', 'language' => 'en'],
                ['author_name' => 'Empty', 'text' => '   '],
            ],
            [
                [
                    'id' => 'en.sahih',
                    'name' => 'Saheeh International',
                    'language' => 'en',
                    'direction' => 'ltr',
                    'text' => 'Local meaning',
                ],
                [
                    'id' => 'ur.maududi',
                    'name' => 'Maududi (Urdu)',
                    'language' => 'ur',
                    'direction' => 'rtl',
                    'text' => 'اللہ کے نام سے',
                ],
            ]
        );

        $this->assertCount(2, $merged);
        $this->assertSame('Local Scholar', $merged[0]['author_name']);
        $this->assertSame('Maududi (Urdu)', $merged[1]['author_name']);
        $this->assertSame('rtl', $merged[1]['direction']);
    }
}
