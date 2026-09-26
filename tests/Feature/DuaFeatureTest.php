<?php

namespace Tests\Feature;

use App\Models\Dua;
use Database\Seeders\DuaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuaFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DuaSeeder::class);
    }
    public function test_duas_index_page_returns_successful_response(): void
    {
        $response = $this->get(route('duas.index'));

        $response->assertStatus(200);
        $response->assertSee('Authentic Duas');
        $response->assertSee('Meaning');
    }

    public function test_duas_category_filtering(): void
    {
        $response = $this->get(route('duas.index', ['category' => 'Forgiveness & Tawbah']));

        $response->assertStatus(200);
        $response->assertSee('Sayyid al-Istighfar');
    }

    public function test_duas_source_type_filtering_quran(): void
    {
        $response = $this->get(route('duas.index', ['source_type' => 'quran']));

        $response->assertStatus(200);
        $response->assertSee('Surah');
    }

    public function test_duas_source_type_filtering_hadith(): void
    {
        $response = $this->get(route('duas.index', ['source_type' => 'hadith']));

        $response->assertStatus(200);
        $response->assertSee('Sahih');
    }

    public function test_duas_search_by_meaning_and_keywords(): void
    {
        // Search by meaning keyword
        $response = $this->get(route('duas.index', ['search' => 'anxiety']));

        $response->assertStatus(200);
        $response->assertSee('Anxiety');

        // Search by Arabic / title
        $response2 = $this->get(route('duas.index', ['search' => 'Yunus']));
        $response2->assertStatus(200);
        $response2->assertSee('Ayat e Karima');
    }

    public function test_dua_show_page_renders_meaning_and_breakdown(): void
    {
        $dua = Dua::where('slug', 'sayyid-al-istighfar')->first();
        $this->assertNotNull($dua);

        $response = $this->get(route('duas.show', $dua->slug));

        $response->assertStatus(200);
        $response->assertSee($dua->title);
        $response->assertSee($dua->arabic_text);
        $response->assertSee($dua->translation_en);
        $response->assertSee('Interactive Word-by-Word Lexical Breakdown');
        $response->assertSee('Sunnah Tasbih Counter');
        $response->assertSee('Sahih al-Bukhari');
    }

    public function test_daily_dua_redirects_to_dua_show(): void
    {
        $response = $this->get(route('daily.dua'));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_api_duas_returns_valid_json(): void
    {
        $response = $this->getJson('/api/duas');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'category',
                        'arabic_text',
                        'translation_en',
                        'word_by_word',
                        'source_type',
                    ]
                ]
            ]
        ]);
    }

    public function test_api_dua_single_lookup(): void
    {
        $response = $this->getJson('/api/duas/sayyid-al-istighfar');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'slug' => 'sayyid-al-istighfar',
            ]
        ]);
    }
}
