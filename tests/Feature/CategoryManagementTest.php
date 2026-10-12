<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dua;
use App\Models\QuranResearchContribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_dua_category_and_quran_research_category(): void
    {
        $user = User::factory()->create();

        // 1. User adds a Dua category
        $resDua = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Parents & Elders',
            'type' => Category::TYPE_DUA,
            'emoji' => '🤲',
            'description' => 'Supplications for parents and elders',
        ]);

        $resDua->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'name' => 'Parents & Elders',
            'slug' => 'parents-elders',
            'type' => Category::TYPE_DUA,
            'is_active' => true,
        ]);

        // 2. User adds a Quran Research category
        $resResearch = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Linguistic Miracles',
            'type' => Category::TYPE_QURAN_RESEARCH,
            'emoji' => '📖',
            'description' => 'Rhetorical and linguistic harmonies of the Quran',
        ]);

        $resResearch->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'name' => 'Linguistic Miracles',
            'slug' => 'linguistic-miracles',
            'type' => Category::TYPE_QURAN_RESEARCH,
            'is_active' => true,
        ]);
    }

    public function test_submitting_dua_or_research_with_custom_category_registers_category(): void
    {
        // Submit Dua with a new category
        $this->post(route('duas.store'), [
            'title' => 'Dua for Righteous Offspring',
            'category' => 'Righteous Offspring',
            'arabic_text' => 'رَبِّ هَبْ لِي مِنَ الصَّالِحِينَ',
            'translation_en' => 'My Lord, grant me [a child] from among the righteous.',
            'source_type' => 'quran',
            'quran_reference' => 'Surah As-Saffat 37:100',
        ])->assertRedirect(route('duas.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Righteous Offspring',
            'type' => Category::TYPE_DUA,
        ]);

        // Submit Quran Research contribution with custom_category
        $this->post(route('research.contributions.store'), [
            'category' => 'custom',
            'custom_category' => 'Numerical Harmonies',
            'category_emoji' => '🔢',
            'surah_number' => 1,
            'verse_number' => 1,
            'title' => 'Mathematical Symmetry in Surah Al-Fatihah',
            'description' => 'Analysis of word and letter frequencies.',
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Numerical Harmonies',
            'slug' => 'numerical-harmonies',
            'type' => Category::TYPE_QURAN_RESEARCH,
        ]);

        $this->assertDatabaseHas('quran_research_contributions', [
            'title' => 'Mathematical Symmetry in Surah Al-Fatihah',
            'category' => 'numerical-harmonies',
        ]);
    }

    public function test_admin_can_view_and_manage_categories(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);

        // Non-admin cannot access admin categories
        $this->actingAs($regularUser)
            ->get(route('admin.categories.index'))
            ->assertStatus(403);

        // Admin can view categories index
        $viewRes = $this->actingAs($admin)->get(route('admin.categories.index'));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('Manage Categories');

        // Admin can create a category
        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Hajj & Umrah',
            'type' => Category::TYPE_DUA,
            'emoji' => '🕋',
            'description' => 'Supplications for pilgrimage',
        ])->assertRedirect();

        $category = Category::where('type', Category::TYPE_DUA)->where('name', 'Hajj & Umrah')->firstOrFail();

        // Create a Dua linked to this category
        $dua = Dua::create([
            'title' => 'Talbiyah',
            'slug' => 'talbiyah',
            'category' => 'Hajj & Umrah',
            'arabic_text' => 'لَبَّيْكَ اللَّهُمَّ لَبَّيْكَ',
            'translation_en' => 'Here I am, O Allah, here I am.',
            'source_type' => 'hadith',
            'status' => 'approved',
        ]);

        // Admin renames the category -> cascades to Dua
        $this->actingAs($admin)->put(route('admin.categories.update', $category->id), [
            'name' => 'Hajj, Umrah & Makkah',
            'type' => Category::TYPE_DUA,
            'emoji' => '🕋',
            'description' => 'Updated description',
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertEquals('Hajj, Umrah & Makkah', $dua->fresh()->category);

        // Admin toggles active status
        $this->actingAs($admin)->post(route('admin.categories.toggle-active', $category->id))->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);

        // Admin merges two categories
        $targetCat = Category::ensureCategory('Travel & Journey', Category::TYPE_DUA);
        $this->actingAs($admin)->post(route('admin.categories.merge'), [
            'source_id' => $category->id,
            'target_id' => $targetCat->id,
            'delete_source' => 1,
        ])->assertRedirect();

        $this->assertEquals('Travel & Journey', $dua->fresh()->category);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_api_categories_endpoints_list_and_create_categories(): void
    {
        $postRes = $this->postJson('/api/categories', [
            'name' => 'Astronomy & Cosmology',
            'type' => 'quran_research',
            'emoji' => '🌌',
        ]);

        $postRes->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.slug', 'astronomy-cosmology');

        $getRes = $this->getJson('/api/categories?type=quran_research');
        $getRes->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }
}
