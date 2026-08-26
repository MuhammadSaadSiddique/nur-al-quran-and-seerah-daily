<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuranicLensRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_approvals(): void
    {
        $response = $this->get(route('admin.lens.approvals.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_approvals(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_researcher' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.lens.approvals.index'));
        $response->assertStatus(403);
    }

    public function test_researcher_can_access_approvals_but_not_admin_dashboard(): void
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_researcher' => true,
        ]);

        // Access approvals
        $response = $this->actingAs($researcher)->get(route('admin.lens.approvals.index'));
        $response->assertStatus(200);

        // Try accessing admin general dashboard users page
        $responseAdmin = $this->actingAs($researcher)->get(route('admin.users.index'));
        $responseAdmin->assertStatus(403);
    }

    public function test_admin_can_access_both_approvals_and_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
            'is_researcher' => false,
        ]);

        // Access approvals
        $responseLens = $this->actingAs($admin)->get(route('admin.lens.approvals.index'));
        $responseLens->assertStatus(200);

        // Access admin dashboard
        $responseAdmin = $this->actingAs($admin)->get(route('admin.users.index'));
        $responseAdmin->assertStatus(200);
    }

    public function test_guest_can_view_researchers_page(): void
    {
        $response = $this->get(route('researchers.index'));
        $response->assertStatus(200);
    }

    public function test_guest_cannot_join_researchers_team_without_login(): void
    {
        $response = $this->post(route('researchers.join'));
        $response->assertRedirect(route('login'));
    }

    public function test_logged_in_user_can_join_researchers_team(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_researcher' => false,
        ]);

        $response = $this->actingAs($user)->post(route('researchers.join'));
        $response->assertRedirect(route('researchers.index'));

        $user->refresh();
        $this->assertTrue($user->is_researcher);
    }

    public function test_researcher_expert_category_restriction_on_approvals_page(): void
    {
        // Create a category
        $category = \App\Models\ScienceCategory::create([
            'name' => 'Geology',
            'slug' => 'geology',
            'emoji' => '🪨',
            'mapped_fields' => 'geology, earth',
        ]);

        $researcher = User::create([
            'name' => 'Expert Researcher',
            'email' => 'expert@example.com',
            'is_researcher' => true,
            'expert_category_id' => $category->id,
        ]);

        // Create a matching tag (Geology)
        \App\Models\QuranicLensVerseTag::create([
            'user_id' => $researcher->id,
            'chapter_number' => 2,
            'verse_number' => 255,
            'tag_type' => 'science',
            'tag_value' => 'Geology',
            'explanation' => 'Geology details.',
            'status' => 'pending',
        ]);

        // Create a non-matching tag (Astronomy)
        \App\Models\QuranicLensVerseTag::create([
            'user_id' => $researcher->id,
            'chapter_number' => 2,
            'verse_number' => 255,
            'tag_type' => 'science',
            'tag_value' => 'Astronomy / Cosmology',
            'explanation' => 'Astronomy details.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($researcher)->get(route('admin.lens.approvals.index', ['tab' => 'verses']));

        $response->assertStatus(200);
        $response->assertSee('Geology details.');
        $response->assertDontSee('Astronomy details.');
    }

    public function test_admin_category_filter_on_approvals_page(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $category = \App\Models\ScienceCategory::create([
            'name' => 'Geology',
            'slug' => 'geology',
            'emoji' => '🪨',
            'mapped_fields' => 'geology, earth',
        ]);

        // Create a matching tag (Geology)
        \App\Models\QuranicLensVerseTag::create([
            'user_id' => $admin->id,
            'chapter_number' => 2,
            'verse_number' => 255,
            'tag_type' => 'science',
            'tag_value' => 'Geology',
            'explanation' => 'Geology details.',
            'status' => 'pending',
        ]);

        // Create a non-matching tag (Astronomy)
        \App\Models\QuranicLensVerseTag::create([
            'user_id' => $admin->id,
            'chapter_number' => 2,
            'verse_number' => 255,
            'tag_type' => 'science',
            'tag_value' => 'Astronomy / Cosmology',
            'explanation' => 'Astronomy details.',
            'status' => 'pending',
        ]);

        // Request with category filter
        $response = $this->actingAs($admin)->get(route('admin.lens.approvals.index', [
            'tab' => 'verses',
            'category_id' => $category->id
        ]));

        $response->assertStatus(200);
        $response->assertSee('Geology details.');
        $response->assertDontSee('Astronomy details.');
    }

    public function test_quranic_research_landing_page_accessible_to_guest_and_optimized(): void
    {
        $response = $this->get(route('lens.landing'));
        $response->assertStatus(200);
        $response->assertSee('Quranic Research');
        $metaDescription = 'Explore analytical connections between the Quran and science';
        $response->assertSee($metaDescription);
    }
}
