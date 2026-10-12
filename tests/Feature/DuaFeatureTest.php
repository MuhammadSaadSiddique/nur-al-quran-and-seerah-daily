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

    public function test_user_can_submit_dua_which_defaults_to_pending(): void
    {
        $payload = [
            'title' => 'Dua for Knowledge and Understanding',
            'category' => 'Guidance & Faith',
            'arabic_text' => 'رَبِّ زِدْنِي عِلْمًا',
            'transliteration' => 'Rabbi zidnī \'ilmā',
            'translation_en' => 'My Lord, increase me in knowledge.',
            'source_type' => 'quran',
            'surah_number' => 20,
            'verse_number' => 114,
            'quran_reference' => 'Surah Ta-Ha 20:114',
            'submitter_name' => 'Bilal',
            'submitter_email' => 'bilal@example.com',
        ];

        $response = $this->post(route('duas.store'), $payload);

        $response->assertRedirect(route('duas.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('duas', [
            'title' => 'Dua for Knowledge and Understanding',
            'status' => 'pending',
            'submitter_name' => 'Bilal',
        ]);
    }

    public function test_pending_dua_is_hidden_from_public_list_and_api(): void
    {
        $pendingDua = Dua::create([
            'title' => 'Hidden Pending Dua Test',
            'slug' => 'hidden-pending-dua-test',
            'category' => 'Protection',
            'arabic_text' => 'أَعُوذُ بِكَلِمَاتِ اللَّهِ التَّامَّاتِ',
            'translation_en' => 'I seek refuge in the perfect words of Allah',
            'source_type' => 'hadith',
            'status' => 'pending',
        ]);

        // Public index check
        $publicResponse = $this->get(route('duas.index'));
        $publicResponse->assertStatus(200);
        $publicResponse->assertDontSee('Hidden Pending Dua Test');

        // Public API check
        $apiResponse = $this->getJson('/api/duas');
        $apiResponse->assertStatus(200);
        $apiResponse->assertDontSee('Hidden Pending Dua Test');

        // Public single show check
        $showResponse = $this->get(route('duas.show', $pendingDua->slug));
        $showResponse->assertStatus(404);
    }

    public function test_admin_can_view_and_approve_pending_dua(): void
    {
        $admin = \App\Models\User::factory()->create(['is_admin' => true]);

        $pendingDua = Dua::create([
            'title' => 'Dua Waiting for Approval',
            'slug' => 'dua-waiting-for-approval',
            'category' => 'Health & Healing',
            'arabic_text' => 'اللَّهُمَّ رَبَّ النَّاسِ أَذْهِبِ الْبَاسَ',
            'translation_en' => 'O Allah, Lord of the people, remove the affliction.',
            'source_type' => 'hadith',
            'status' => 'pending',
            'submitter_name' => 'Fatima',
        ]);

        // Admin queue index
        $adminQueue = $this->actingAs($admin)->get(route('admin.duas.index', ['status' => 'pending']));
        $adminQueue->assertStatus(200);
        $adminQueue->assertSee('Dua Waiting for Approval');
        $adminQueue->assertSee('Fatima');

        // Approve action
        $approveResponse = $this->actingAs($admin)->post(route('admin.duas.approve', $pendingDua->id));
        $approveResponse->assertSessionHas('success');

        $this->assertDatabaseHas('duas', [
            'id' => $pendingDua->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);

        // Now it must be visible publicly
        $publicResponse = $this->get(route('duas.index'));
        $publicResponse->assertSee('Dua Waiting for Approval');
    }

    public function test_admin_can_reject_pending_dua(): void
    {
        $admin = \App\Models\User::factory()->create(['is_admin' => true]);

        $pendingDua = Dua::create([
            'title' => 'Unverified Dua Submission',
            'slug' => 'unverified-dua-submission',
            'category' => 'General',
            'arabic_text' => 'نص تجريبي',
            'translation_en' => 'Unverified prayer',
            'source_type' => 'hadith',
            'status' => 'pending',
        ]);

        $rejectResponse = $this->actingAs($admin)->post(route('admin.duas.reject', $pendingDua->id), [
            'reason' => 'Citation could not be verified in authentic six books of hadith.',
        ]);

        $rejectResponse->assertSessionHas('info');

        $this->assertDatabaseHas('duas', [
            'id' => $pendingDua->id,
            'status' => 'rejected',
            'admin_notes' => 'Citation could not be verified in authentic six books of hadith.',
        ]);
    }

    public function test_api_client_can_submit_dua_with_pending_status(): void
    {
        $payload = [
            'title' => 'Mobile App User Dua Contribution',
            'category' => 'Forgiveness & Mercy',
            'arabic_text' => 'رَبَّنَا ظَلَمْنَا أَنفُسَنَا',
            'translation_en' => 'Our Lord, we have wronged ourselves.',
            'source_type' => 'quran',
            'surah_number' => 7,
            'verse_number' => 23,
            'quran_reference' => 'Surah Al-A\'raf 7:23',
            'submitter_name' => 'Mobile App User',
        ];

        $response = $this->postJson('/api/duas', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'title' => 'Mobile App User Dua Contribution',
                'status' => 'pending',
            ]
        ]);

        $this->assertDatabaseHas('duas', [
            'title' => 'Mobile App User Dua Contribution',
            'status' => 'pending',
        ]);
    }
}
