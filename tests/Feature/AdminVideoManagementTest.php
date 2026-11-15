<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\QuranResearchContribution;
use App\Models\QuranResearchReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminVideoManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('surahs')->insertOrIgnore([
            'id' => 1,
            'number' => 1,
            'name_arabic' => 'الفاتحة',
            'name_english' => 'The Opening',
            'name_transliteration' => 'Al-Fatihah',
            'revelation_type' => 'Meccan',
            'verse_count' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function guests_and_regular_users_cannot_access_admin_video_management()
    {
        $regularUser = User::factory()->create(['is_admin' => false]);

        $this->get(route('admin.videos.index'))->assertRedirect(route('login'));
        $this->actingAs($regularUser)->get(route('admin.videos.index'))->assertStatus(403);
        $this->actingAs($regularUser)->get(route('admin.videos.create'))->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_videos_index_with_metrics_and_queue()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $pending = QuranResearchContribution::create([
            'title' => 'Pending Tafsir Video',
            'surah_number' => 1,
            'verse_number' => 1,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'pending',
            'approvals_count' => 1,
        ]);

        $published = QuranResearchContribution::create([
            'title' => 'Live Approved Video',
            'surah_number' => 1,
            'verse_number' => 2,
            'category' => 'video',
            'video_url' => 'https://vimeo.com/12345678',
            'status' => 'published',
            'approvals_count' => 5,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.videos.index', ['status' => 'all']));
        $response->assertStatus(200);
        $response->assertSee('Pending Tafsir Video');
        $response->assertSee('Live Approved Video');
        $response->assertSee('Videos & Quran Research Management', false);
    }

    /** @test */
    public function admin_can_add_video_from_backend_with_immediate_publishing()
    {
        $admin = User::factory()->create([
            'name' => 'System Admin',
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.videos.store'), [
            'title' => 'Admin Curated Tafsir Lecture',
            'surah_number' => 1,
            'verse_number' => 1,
            'scope_mode' => 'single',
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'description' => 'Detailed verse by verse linguistic breakdown.',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.videos.index', ['status' => 'published']));

        $video = QuranResearchContribution::where('title', 'Admin Curated Tafsir Lecture')->firstOrFail();
        $this->assertEquals('published', $video->status);
        $this->assertEquals(5, $video->approvals_count);
        $this->assertNotNull($video->published_at);
        $this->assertEquals('youtube', $video->video_platform);
        $this->assertEquals('dQw4w9WgXcQ', $video->video_id);
        $this->assertEquals($admin->id, $video->user_id);

        $this->assertDatabaseHas('quran_research_reviews', [
            'contribution_id' => $video->id,
            'user_id' => $admin->id,
            'vote' => 'approve',
        ]);
    }

    /** @test */
    public function admin_can_approve_pending_video_from_backend()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $contributor = User::factory()->create(['is_admin' => false]);

        $video = QuranResearchContribution::create([
            'user_id' => $contributor->id,
            'title' => 'Community Submitted Video Pending Approval',
            'surah_number' => 1,
            'verse_number' => 1,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'pending',
            'approvals_count' => 2,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.videos.approve', $video->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $video->refresh();
        $this->assertEquals('published', $video->status);
        $this->assertNotNull($video->published_at);
        $this->assertGreaterThanOrEqual(5, $video->approvals_count);

        $this->assertDatabaseHas('quran_research_reviews', [
            'contribution_id' => $video->id,
            'user_id' => $admin->id,
            'vote' => 'approve',
        ]);
    }

    /** @test */
    public function admin_can_reject_video_with_reason()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $video = QuranResearchContribution::create([
            'title' => 'Misleading Video Claim',
            'surah_number' => 1,
            'verse_number' => 1,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.videos.reject', $video->id), [
            'reason' => 'Narrator is unverified and references lack chain of transmission.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('info');

        $video->refresh();
        $this->assertEquals('rejected', $video->status);

        $this->assertDatabaseHas('quran_research_reviews', [
            'contribution_id' => $video->id,
            'user_id' => $admin->id,
            'vote' => 'downvote',
            'feedback' => 'Narrator is unverified and references lack chain of transmission.',
        ]);
    }

    /** @test */
    public function admin_can_edit_and_update_video_details()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $video = QuranResearchContribution::create([
            'title' => 'Original Title',
            'surah_number' => 1,
            'verse_number' => 1,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get(route('admin.videos.edit', $video->id))->assertStatus(200);

        $response = $this->actingAs($admin)->put(route('admin.videos.update', $video->id), [
            'title' => 'Corrected & Enhanced Title',
            'surah_number' => 1,
            'is_full_surah' => 1,
            'scope_mode' => 'full_surah',
            'category' => 'video',
            'video_url' => 'https://vimeo.com/987654321',
            'description' => 'Updated notes.',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.videos.index', ['status' => 'published']));

        $video->refresh();
        $this->assertEquals('Corrected & Enhanced Title', $video->title);
        $this->assertEquals('published', $video->status);
        $this->assertEquals('vimeo', $video->video_platform);
        $this->assertEquals('987654321', $video->video_id);
    }

    /** @test */
    public function admin_can_delete_video()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $video = QuranResearchContribution::create([
            'title' => 'Video to Delete',
            'surah_number' => 1,
            'verse_number' => 1,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.videos.destroy', $video->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('quran_research_contributions', ['id' => $video->id]);
    }

    /** @test */
    public function admin_can_approve_video_via_api()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $video = QuranResearchContribution::create([
            'title' => 'Video For API Approval',
            'surah_number' => 1,
            'verse_number' => 1,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/research-contributions/{$video->id}/approve", [
            'feedback' => 'Approved via mobile admin interface.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $video->refresh();
        $this->assertEquals('published', $video->status);
        $this->assertNotNull($video->published_at);
        $this->assertGreaterThanOrEqual(5, $video->approvals_count);
    }
}
