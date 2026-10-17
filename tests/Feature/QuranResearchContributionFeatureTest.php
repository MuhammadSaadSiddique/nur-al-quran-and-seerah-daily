<?php

namespace Tests\Feature;

use App\Models\QuranResearchContribution;
use App\Models\QuranResearchReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuranResearchContributionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed basic Surah if necessary
        \Illuminate\Support\Facades\DB::table('surahs')->insertOrIgnore([
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
    public function guests_can_view_published_contributions_but_not_pending_items()
    {
        $published = QuranResearchContribution::create([
            'title' => 'Published Scientific Video',
            'surah_number' => 1,
            'verse_number' => 1,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'published',
            'approvals_count' => 5,
        ]);

        $pending = QuranResearchContribution::create([
            'title' => 'Pending Video Submission',
            'surah_number' => 1,
            'verse_number' => 2,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'pending',
            'approvals_count' => 2,
        ]);

        // Guest views published videos module in current tab
        $response = $this->get(route('welcome', ['tab' => 'videos']));
        $response->assertStatus(200);
        $response->assertSee('Published Scientific Video');
        $response->assertDontSee('Pending Video Submission');

        // Guest attempts to view pending tab in current tab -> redirected to login
        $responsePending = $this->get(route('welcome', ['tab' => 'pending']));
        $responsePending->assertRedirect(route('login'));
    }

    /** @test */
    public function logged_in_users_can_view_pending_items()
    {
        $user = User::factory()->create();

        $pending = QuranResearchContribution::create([
            'title' => 'Pending Video Submission',
            'surah_number' => 1,
            'verse_number' => 2,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'pending',
            'approvals_count' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('welcome', ['tab' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee('Pending Video Submission');
    }

    /** @test */
    public function user_can_submit_video_contribution_as_pending()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('research.contributions.store'), [
            'title' => 'Deep Tafsir of Surah Fatihah',
            'surah_number' => 1,
            'verse_number' => 1,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'description' => 'Comprehensive explanation of verse 1.',
        ]);

        $response->assertRedirect(route('welcome', ['tab' => 'pending']));
        $this->assertDatabaseHas('quran_research_contributions', [
            'title' => 'Deep Tafsir of Surah Fatihah',
            'category' => 'video',
            'status' => 'pending',
            'video_platform' => 'youtube',
            'video_id' => 'dQw4w9WgXcQ',
        ]);
    }

    /** @test */
    public function five_approvals_publish_and_downvotes_cannot_unpublish()
    {
        $contribution = QuranResearchContribution::create([
            'title' => 'Community Contribution',
            'surah_number' => 1,
            'verse_number' => 1,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'pending',
            'approvals_count' => 0,
            'downvotes_count' => 0,
        ]);

        $reviewers = User::factory()->count(6)->create([
            'password' => bcrypt('password'),
            'created_at' => now()->subDays(2),
        ]);

        // 4 users approve -> still pending
        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($reviewers[$i])->post(route('research.contributions.review', $contribution->id), [
                'vote' => 'approve',
                'feedback' => 'Looks authentic and verified.',
            ]);
        }

        $contribution->refresh();
        $this->assertEquals(4, $contribution->approvals_count);
        $this->assertEquals('pending', $contribution->status);

        // 5th user approves -> published!
        $this->actingAs($reviewers[4])->post(route('research.contributions.review', $contribution->id), [
            'vote' => 'approve',
            'feedback' => 'Fifth approval.',
        ]);

        $contribution->refresh();
        $this->assertEquals(5, $contribution->approvals_count);
        $this->assertEquals('published', $contribution->status);
        $this->assertNotNull($contribution->published_at);

        // Downvotes update counts but cannot unpublish (sticky publish).
        $downvoters = User::factory()->count(5)->create([
            'password' => bcrypt('password'),
            'created_at' => now()->subDays(2),
        ]);
        foreach ($downvoters as $downvoter) {
            $this->actingAs($downvoter)->post(route('research.contributions.review', $contribution->id), [
                'vote' => 'downvote',
                'feedback' => 'Found factual inaccuracies in reference.',
            ]);
        }

        $contribution->refresh();
        $this->assertEquals(5, $contribution->approvals_count);
        $this->assertEquals(5, $contribution->downvotes_count);
        $this->assertEquals('published', $contribution->status);
    }

    /** @test */
    public function api_endpoints_allow_retrieving_and_submitting_and_reviewing()
    {
        $user = User::factory()->create();

        // API store
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/research-contributions', [
            'title' => 'API Submitted Video',
            'surah_number' => 1,
            'verse_number' => 5,
            'category' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'description' => 'Test API contribution',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');
        $id = $response->json('data.id');

        // Review via API
        $reviewer = User::factory()->create([
            'password' => bcrypt('password'),
            'created_at' => now()->subDays(2),
        ]);
        $reviewRes = $this->actingAs($reviewer, 'sanctum')->postJson("/api/research-contributions/{$id}/review", [
            'vote' => 'approve',
            'feedback' => 'Verified through API',
        ]);

        $reviewRes->assertStatus(200);
        $reviewRes->assertJsonPath('data.approvals_count', 1);

        // List via API
        $listRes = $this->actingAs($user, 'sanctum')->getJson('/api/research-contributions?tab=pending');
        $listRes->assertStatus(200);
        $listRes->assertJsonPath('status', 'success');
        $this->assertNotEmpty($listRes->json('data'));
    }

    /** @test */
    public function user_can_add_full_surah_verse_range_and_multiple_surahs_and_all_video_platforms()
    {
        $user = User::factory()->create();

        // 1. Start to end of Surah (Full Surah) with TikTok video
        $resFull = $this->actingAs($user)->post(route('research.contributions.store'), [
            'title' => 'Complete Surah Al-Fatihah Reflection',
            'surah_number' => 1,
            'is_full_surah' => 1,
            'category' => 'video',
            'video_url' => 'https://www.tiktok.com/@scholar/video/7234567890123456789',
        ]);
        $resFull->assertRedirect(route('welcome', ['tab' => 'pending']));
        $fullContrib = QuranResearchContribution::where('title', 'Complete Surah Al-Fatihah Reflection')->firstOrFail();
        $this->assertTrue($fullContrib->is_full_surah);
        $this->assertEquals(1, $fullContrib->verse_number);
        $this->assertEquals(7, $fullContrib->end_verse_number);
        $this->assertEquals('tiktok', $fullContrib->video_platform);
        $this->assertEquals('7234567890123456789', $fullContrib->video_id);
        $this->assertEquals('Surah 1 (1–7 Full)', $fullContrib->scopeLabel());

        // 2. Verse range (start verse to end verse) with Vimeo video
        $resRange = $this->actingAs($user)->post(route('research.contributions.store'), [
            'title' => 'Embryology in Surah Al-Mu\'minun 12-14',
            'surah_number' => 23,
            'verse_number' => 12,
            'end_verse_number' => 14,
            'category' => 'video',
            'video_url' => 'https://vimeo.com/987654321',
        ]);
        $resRange->assertRedirect(route('welcome', ['tab' => 'pending']));
        $rangeContrib = QuranResearchContribution::where('title', 'Embryology in Surah Al-Mu\'minun 12-14')->firstOrFail();
        $this->assertEquals(23, $rangeContrib->surah_number);
        $this->assertEquals(12, $rangeContrib->verse_number);
        $this->assertEquals(14, $rangeContrib->end_verse_number);
        $this->assertEquals('vimeo', $rangeContrib->video_platform);
        $this->assertEquals('987654321', $rangeContrib->video_id);
        $this->assertEquals('Surah 23:12–14', $rangeContrib->scopeLabel());

        // 3. Multiple Surahs with Instagram Reel, Facebook Watch, Dailymotion, Google Drive, and external platforms
        $resMulti = $this->actingAs($user)->post(route('research.contributions.store'), [
            'title' => 'Three Quls Protection Series',
            'surah_number' => 112,
            'surah_numbers' => '112, 113, 114',
            'is_full_surah' => 1,
            'category' => 'video',
            'video_url' => 'https://www.instagram.com/reel/CxYz123AbCd/',
        ]);
        $resMulti->assertRedirect(route('welcome', ['tab' => 'pending']));
        $multiContrib = QuranResearchContribution::where('title', 'Three Quls Protection Series')->firstOrFail();
        $this->assertEquals([112, 113, 114], $multiContrib->surah_numbers);
        $this->assertEquals('instagram', $multiContrib->video_platform);
        $this->assertEquals('CxYz123AbCd', $multiContrib->video_id);
        $this->assertEquals('Surahs 112, 113, 114', $multiContrib->scopeLabel());

        // Verify platform parser for Facebook, Dailymotion, X/Twitter, Google Drive, Direct MP4, and any custom platform
        $this->assertEquals('facebook', QuranResearchContribution::parseVideoUrl('https://www.facebook.com/watch/?v=123456')['platform']);
        $this->assertEquals('dailymotion', QuranResearchContribution::parseVideoUrl('https://www.dailymotion.com/video/x8abcde')['platform']);
        $this->assertEquals('twitter', QuranResearchContribution::parseVideoUrl('https://x.com/user/status/1800000000000000000')['platform']);
        $this->assertEquals('gdrive', QuranResearchContribution::parseVideoUrl('https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view')['platform']);
        $this->assertEquals('direct_file', QuranResearchContribution::parseVideoUrl('https://cdn.example.org/lecture.mp4')['platform']);
        $this->assertEquals('quranreflect.com', QuranResearchContribution::parseVideoUrl('https://www.quranreflect.com/watch/999')['platform']);
    }
}

