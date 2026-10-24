<?php

namespace Tests\Feature;

use App\Models\QuranPatternCard;
use App\Services\GeminiService;
use App\Services\QuranPatternService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuranPatternTest extends TestCase
{
    use RefreshDatabase;

    private string $arabic = 'والسماء والطارق وما';

    public function test_verse_key_returns_a_cached_card_with_counts_from_the_arabic(): void
    {
        $this->seedVerse(86, 1, 'At-Tariq', 17, $this->arabic, 'By the sky and the night comer');

        $math = app(QuranPatternService::class)->countsFor($this->arabic, 17, null, [$this->arabic]);
        $stripped = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $this->arabic);
        preg_match_all('/\p{Arabic}/u', (string) $stripped, $letters);

        $this->assertSame(3, $math['words_in_verse']);
        $this->assertSame(17, $math['ayat_in_surah']);
        $this->assertSame(count($letters[0]), $math['letters_in_verse']);
        $this->assertNull($math['focus_position']);

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('generatePatternNotes')->once()->andReturn([
                'gloss' => 'The night comer',
                'arabic_term' => 'والطارق',
                'ring_note' => 'Local symmetry in this short surah.',
                'linguistic_note' => 'والطارق is the night comer.',
                'topics' => ['Cosmology', 'Astrology'],
            ]);
            $mock->shouldNotReceive('suggestPatternVerses');
        });

        $this->get('/patterns?q=86:1')->assertRedirect(route('patterns.show', [86, 1]));

        $first = $this->get('/patterns/86/1');
        $first->assertOk();
        $first->assertSee('The night comer');
        $first->assertSee('86:1');
        $first->assertSee('Cosmology');
        $first->assertDontSee('Astrology');
        $first->assertSee('Counts come from the stored text. Notes are study observations.');
        $first->assertSee($math['summary']);

        $stored = QuranPatternCard::query()->first();
        $this->assertNotNull($stored);
        $this->assertSame($math['words_in_verse'], $stored->math['words_in_verse']);
        $this->assertSame($math['letters_in_verse'], $stored->math['letters_in_verse']);
        $this->assertSame('والطارق', $stored->arabic_term);

        $second = $this->get('/patterns/86/1');
        $second->assertOk();
        $second->assertSee('The night comer');
        $this->assertSame(1, QuranPatternCard::query()->count());
    }

    public function test_a_query_with_no_literal_hit_only_shows_verse_keys_that_exist(): void
    {
        $this->seedVerse(36, 40, 'Ya-Sin', 83, 'كل في فلك', 'Each in an orbit is swimming');
        $this->seedVerse(86, 1, 'At-Tariq', 17, $this->arabic, 'By the sky and the night comer');

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('suggestPatternVerses')->once()->andReturn(['36:40', '999:1', 'not-a-verse', '86:1']);
            $mock->shouldNotReceive('generatePatternNotes');
        });

        $response = $this->get('/patterns?q=pulsar');
        $response->assertOk();
        $response->assertSee('36:40');
        $response->assertSee('86:1');
        $response->assertDontSee('999:1');
        $this->assertSame(0, QuranPatternCard::query()->count());
    }

    public function test_ayat_al_kursi_search_shows_the_nine_line_ring_without_gemini(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldNotReceive('generatePatternNotes');
            $mock->shouldNotReceive('suggestPatternVerses');
        });

        $this->get('/patterns?q=ayat+ul+kursi')
            ->assertRedirect(route('patterns.show', ['chapter' => 2, 'verse' => 255, 'focus' => 'ayat ul kursi']));

        $response = $this->get('/patterns/2/255');
        $response->assertOk();
        $response->assertSee('Ayat al-Kursi, the Verse of the Throne');
        $response->assertSee('He knows what is before them and what will be after them.');
        $response->assertSee('Center');
        $response->assertSee('And He is the Most High, the Most Great.');
        $response->assertSee('كرسيه');
    }

    public function test_an_impossible_verse_key_is_rejected(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldNotReceive('generatePatternNotes');
            $mock->shouldNotReceive('suggestPatternVerses');
        });

        $this->get('/patterns/999/1')->assertNotFound();
        $this->get('/patterns?q=999:1')->assertOk()->assertSee('That verse was not found.');
    }

    private function seedVerse(int $chapter, int $verse, string $name, int $verseCount, string $arabic, string $translation): void
    {
        $surahId = DB::table('surahs')->where('number', $chapter)->value('id');
        if (!$surahId) {
            $surahId = DB::table('surahs')->insertGetId([
                'number' => $chapter,
                'name_arabic' => $name,
                'name_transliteration' => $name,
                'name_english' => $name,
                'revelation_type' => 'Meccan',
                'verse_count' => $verseCount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $verseId = DB::table('verses')->insertGetId([
            'surah_id' => $surahId,
            'verse_number' => $verse,
            'text_arabic' => $arabic,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('translations')->insert([
            'verse_id' => $verseId,
            'language' => 'en',
            'translator' => 'Test',
            'text' => $translation,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
