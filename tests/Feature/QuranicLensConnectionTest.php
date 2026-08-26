<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuranicLensConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed necessary database tables
        DB::table('surahs')->insert([
            'id' => 2,
            'number' => 2,
            'name_arabic' => 'البقرة',
            'name_simple' => 'Al-Baqarah',
            'name_complex' => 'Al-Baqarah',
            'name_translated' => 'The Cow',
            'name_transliteration' => 'Al-Baqarah',
            'revelation_place' => 'madinah',
            'verses_count' => 286,
        ]);

        DB::table('verses')->insert([
            'id' => 1,
            'surah_id' => 2,
            'verse_number' => 255,
            'verse_key' => '2:255',
            'juz_number' => 3,
            'text_arabic' => 'الله لا إله إلا هو الحي القيوم',
            'text_transliteration' => 'Allahu la ilaha illa huwal hayyul qayyum',
        ]);

        DB::table('hadith_collections')->insert([
            'id' => 1,
            'name' => 'Sahih al-Bukhari',
        ]);
    }

    /** @test */
    public function researcher_can_create_new_connection_link()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $response = $this->actingAs($researcher)->post(route('admin.lens.approvals.create-link'), [
            'chapter_number' => 2,
            'verse_number' => 255,
            'link_type' => 'science',
            'title' => 'Cosmological expansion of orbits',
            'content' => 'The stars orbit in defined celestial orbits.',
            'extra_info' => 'Astronomy',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'New connection link was successfully mapped. Data duplication check verified.');

        $this->assertDatabaseHas('science_facts', [
            'title' => 'Cosmological expansion of orbits',
        ]);

        $fact = DB::table('science_facts')->where('title', 'Cosmological expansion of orbits')->first();

        $this->assertDatabaseHas('quran_science_links', [
            'verse_id' => 1,
            'science_fact_id' => $fact->id,
        ]);
    }

    /** @test */
    public function system_prevents_duplicate_connection_links()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        // Insert first mapping
        $this->actingAs($researcher)->post(route('admin.lens.approvals.create-link'), [
            'chapter_number' => 2,
            'verse_number' => 255,
            'link_type' => 'science',
            'title' => 'Unique Science Fact',
            'content' => 'Description of science fact',
            'extra_info' => 'Astronomy',
        ]);

        // Try mapping the same science fact again
        $response = $this->actingAs($researcher)->post(route('admin.lens.approvals.create-link'), [
            'chapter_number' => 2,
            'verse_number' => 255,
            'link_type' => 'science',
            'title' => 'Unique Science Fact',
            'content' => 'Description of science fact',
            'extra_info' => 'Astronomy',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'This Science connection already exists for this verse. Data duplication was prevented.');

        // Verify we only have one link inside quran_science_links
        $count = DB::table('quran_science_links')->where('verse_id', 1)->count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function researcher_can_approve_analysis_and_link_quiz_theme()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $theme = \App\Models\Theme::create([
            'name' => 'Astronomy in Quran',
            'slug' => 'astronomy-in-quran',
            'description' => 'Quiz on astronomy verses',
            'type' => 'PARA',
            'is_active' => true,
        ]);

        $analysis = \App\Models\QuranicLensAnalysis::create([
            'user_id' => $researcher->id,
            'chapter_number' => 2,
            'verse_number' => 255,
            'lens_type' => 'science',
            'title' => 'Orbiting Bodies',
            'content' => 'The orbits are expanding.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($researcher)->post(route('admin.lens.approvals.approve', ['analysis', $analysis->id]), [
            'theme_id' => $theme->id,
        ]);

        $response->assertStatus(302);
        
        $analysis->refresh();
        $this->assertEquals('approved', $analysis->status);
        $this->assertEquals($theme->id, $analysis->theme_id);
    }

    /** @test */
    public function researcher_can_delete_analysis()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $analysis = \App\Models\QuranicLensAnalysis::create([
            'user_id' => $researcher->id,
            'chapter_number' => 2,
            'verse_number' => 255,
            'lens_type' => 'science',
            'title' => 'Orbiting Bodies',
            'content' => 'The orbits are expanding.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($researcher)->delete(route('admin.lens.approvals.analysis.destroy', $analysis->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Analysis record has been deleted successfully.');

        $this->assertSoftDeleted('quranic_lens_analyses', [
            'id' => $analysis->id,
        ]);
    }

    /** @test */
    public function researcher_can_submit_word_tag_with_auto_approval()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $response = $this->actingAs($researcher)->post(route('lens.tag.word.store'), [
            'chapter_number' => 2,
            'verse_number' => 255,
            'word_position' => 3,
            'word_text' => 'الحي',
            'tag_type' => 'grammar',
            'tag_value' => 'Ism',
            'explanation' => 'Determined noun structure.',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Your word tag has been added successfully.');

        $this->assertDatabaseHas('quranic_lens_word_tags', [
            'chapter_number' => 2,
            'verse_number' => 255,
            'word_position' => 3,
            'word_text' => 'الحي',
            'tag_type' => 'grammar',
            'tag_value' => 'Ism',
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function regular_user_submits_word_tag_as_pending()
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'is_researcher' => false,
        ]);

        $response = $this->actingAs($user)->post(route('lens.tag.word.store'), [
            'chapter_number' => 2,
            'verse_number' => 255,
            'word_position' => 3,
            'word_text' => 'الحي',
            'tag_type' => 'grammar',
            'tag_value' => 'Ism',
            'explanation' => 'Determined noun structure.',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Your word tag has been submitted for review.');

        $this->assertDatabaseHas('quranic_lens_word_tags', [
            'chapter_number' => 2,
            'verse_number' => 255,
            'word_position' => 3,
            'word_text' => 'الحي',
            'tag_type' => 'grammar',
            'tag_value' => 'Ism',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function researcher_can_submit_verse_tag_with_auto_approval()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $response = $this->actingAs($researcher)->post(route('lens.tag.verse.store'), [
            'chapter_number' => 2,
            'verse_number' => 255,
            'tag_type' => 'theme',
            'tag_value' => 'Monotheism',
            'explanation' => 'Verse of the throne represents pure monotheism.',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Your verse tag has been added successfully.');

        $this->assertDatabaseHas('quranic_lens_verse_tags', [
            'chapter_number' => 2,
            'verse_number' => 255,
            'tag_type' => 'theme',
            'tag_value' => 'Monotheism',
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function regular_user_submits_verse_tag_as_pending()
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'is_researcher' => false,
        ]);

        $response = $this->actingAs($user)->post(route('lens.tag.verse.store'), [
            'chapter_number' => 2,
            'verse_number' => 255,
            'tag_type' => 'theme',
            'tag_value' => 'Monotheism',
            'explanation' => 'Verse of the throne represents pure monotheism.',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Your verse tag has been submitted for review.');

        $this->assertDatabaseHas('quranic_lens_verse_tags', [
            'chapter_number' => 2,
            'verse_number' => 255,
            'tag_type' => 'theme',
            'tag_value' => 'Monotheism',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function researcher_can_create_science_category()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $response = $this->actingAs($researcher)->post(route('admin.lens.approvals.science-category.store'), [
            'name' => 'Oceanography & Seas',
            'slug' => 'oceanography_seas',
            'emoji' => '🌊',
            'mapped_fields' => 'oceanography,marine',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Science category created successfully.');

        $this->assertDatabaseHas('science_categories', [
            'name' => 'Oceanography & Seas',
            'slug' => 'oceanography_seas',
            'emoji' => '🌊',
            'mapped_fields' => 'oceanography,marine',
        ]);
    }

    /** @test */
    public function researcher_can_update_science_category()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $cat = \App\Models\ScienceCategory::create([
            'name' => 'Oceanography & Seas',
            'slug' => 'oceanography_seas',
            'emoji' => '🌊',
            'mapped_fields' => 'oceanography,marine',
        ]);

        $response = $this->actingAs($researcher)->put(route('admin.lens.approvals.science-category.update', $cat->id), [
            'name' => 'Oceanography / Marine Biology',
            'slug' => 'oceanography_marine_biology',
            'emoji' => '🐬',
            'mapped_fields' => 'oceanography,marine,biology',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Science category updated successfully.');

        $this->assertDatabaseHas('science_categories', [
            'id' => $cat->id,
            'name' => 'Oceanography / Marine Biology',
            'slug' => 'oceanography_marine_biology',
            'emoji' => '🐬',
            'mapped_fields' => 'oceanography,marine,biology',
        ]);
    }

    /** @test */
    public function researcher_can_delete_science_category()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $cat = \App\Models\ScienceCategory::create([
            'name' => 'Oceanography & Seas',
            'slug' => 'oceanography_seas',
            'emoji' => '🌊',
            'mapped_fields' => 'oceanography,marine',
        ]);

        $response = $this->actingAs($researcher)->delete(route('admin.lens.approvals.science-category.destroy', $cat->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Science category deleted successfully.');

        $this->assertDatabaseMissing('science_categories', [
            'id' => $cat->id,
        ]);
    }

    /** @test */
    public function researcher_can_submit_word_tag_with_science_category()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $response = $this->actingAs($researcher)->post(route('lens.tag.word.store'), [
            'chapter_number' => 2,
            'verse_number' => 255,
            'word_position' => 4,
            'word_text' => 'القيوم',
            'tag_type' => 'science',
            'tag_value' => 'Neuroscience / Psychology',
            'explanation' => 'Mentions attributes of sleep and consciousness.',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Your word tag has been added successfully.');

        $this->assertDatabaseHas('quranic_lens_word_tags', [
            'chapter_number' => 2,
            'verse_number' => 255,
            'word_position' => 4,
            'word_text' => 'القيوم',
            'tag_type' => 'science',
            'tag_value' => 'Neuroscience / Psychology',
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function researcher_can_submit_verse_tag_with_science_category()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $response = $this->actingAs($researcher)->post(route('lens.tag.verse.store'), [
            'chapter_number' => 2,
            'verse_number' => 255,
            'tag_type' => 'science',
            'tag_value' => 'Astronomy / Cosmology',
            'explanation' => 'Verse mentions heaven and earth dominion.',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Your verse tag has been added successfully.');

        $this->assertDatabaseHas('quranic_lens_verse_tags', [
            'chapter_number' => 2,
            'verse_number' => 255,
            'tag_type' => 'science',
            'tag_value' => 'Astronomy / Cosmology',
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function researcher_can_validate_connection_link_approval()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        // Insert a pending link
        $linkId = \Illuminate\Support\Facades\DB::table('quran_science_links')->insertGetId([
            'verse_id' => 1,
            'science_fact_id' => 1,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($researcher)->post(route('admin.lens.approvals.connection.approve', ['quran_science_links', $linkId]));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Connection link has been approved and published.');

        $this->assertDatabaseHas('quran_science_links', [
            'id' => $linkId,
            'status' => 'approved'
        ]);
    }

    /** @test */
    public function researcher_can_validate_connection_link_rejection()
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        // Insert a pending link
        $linkId = \Illuminate\Support\Facades\DB::table('quran_science_links')->insertGetId([
            'verse_id' => 1,
            'science_fact_id' => 1,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($researcher)->post(route('admin.lens.approvals.connection.reject', ['quran_science_links', $linkId]));

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Connection link has been rejected and deleted.');

        $this->assertDatabaseMissing('quran_science_links', [
            'id' => $linkId
        ]);
    }

    /** @test */
    public function admin_can_assign_category_expert_to_researcher()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'is_researcher' => true,
        ]);

        $category = \App\Models\ScienceCategory::create([
            'name' => 'Geology',
            'slug' => 'geology',
            'emoji' => '🪨',
            'mapped_fields' => 'geology, earth',
        ]);

        $response = $this->actingAs($admin)->post(route('researchers.update-expert', $researcher->id), [
            'expert_category_id' => $category->id
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Expert category updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $researcher->id,
            'expert_category_id' => $category->id
        ]);
    }
}
