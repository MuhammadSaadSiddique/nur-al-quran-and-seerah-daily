<?php

namespace Tests\Feature;

use App\Services\QuranTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuranTranslationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_surah_and_verse_pages_show_translations_from_the_web(): void
    {
        Http::fake([
            'api.alquran.cloud/*' => Http::response([
                'code' => 200,
                'data' => [
                    [
                        'edition' => ['identifier' => 'en.sahih'],
                        'ayahs' => [
                            ['numberInSurah' => 1, 'text' => 'In the name of Allah, the Entirely Merciful.'],
                        ],
                    ],
                    [
                        'edition' => ['identifier' => 'en.pickthall'],
                        'ayahs' => [
                            ['numberInSurah' => 1, 'text' => 'In the name of Allah, the Beneficent, the Merciful.'],
                        ],
                    ],
                    [
                        'edition' => ['identifier' => 'ur.jalandhry'],
                        'ayahs' => [
                            ['numberInSurah' => 1, 'text' => 'شروع الله کا نام لے کر'],
                        ],
                    ],
                ],
            ]),
        ]);

        $surahId = DB::table('surahs')->insertGetId([
            'number' => 1,
            'name_arabic' => 'الفاتحة',
            'name_transliteration' => 'Al-Fatihah',
            'name_english' => 'The Opening',
            'revelation_type' => 'Meccan',
            'verse_count' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('verses')->insert([
            'surah_id' => $surahId,
            'verse_number' => 1,
            'text_arabic' => 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ',
            'text_transliteration' => 'Bismillah',
            'juz' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $surah = $this->get(route('lens.surah', 1));
        $surah->assertOk();
        $surah->assertSee('Saheeh International');
        $surah->assertSee('Jalandhry (Urdu)');
        $surah->assertSee('In the name of Allah, the Entirely Merciful.');
        $surah->assertSee('شروع الله کا نام لے کر');
        $surah->assertSee('setEdition');

        $verse = $this->get(route('lens.verse', [1, 1]));
        $verse->assertOk();
        $verse->assertSee('Pickthall');
        $verse->assertSee('In the name of Allah, the Beneficent, the Merciful.');
        $verse->assertSee('direction');

        $this->assertNotEmpty(app(QuranTranslationService::class)->forVerse(1, 1));
    }
}
