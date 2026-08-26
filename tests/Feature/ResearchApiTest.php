<?php

namespace Tests\Feature;

use App\Models\QuranicLensAnalysis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResearchApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test token generation fails with invalid credentials.
     */
    public function test_token_generation_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/token', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['error']);
    }

    /**
     * Test token generation succeeds with valid credentials.
     */
    public function test_token_generation_succeeds_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('correctpassword'),
        ]);

        $response = $this->postJson('/api/token', [
            'email' => 'test@example.com',
            'password' => 'correctpassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    /**
     * Test guest can retrieve approved research content but not pending/rejected content.
     */
    public function test_guest_can_retrieve_approved_research_content_only(): void
    {
        $user = User::create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create approved analysis
        QuranicLensAnalysis::create([
            'user_id' => $user->id,
            'chapter_number' => 1,
            'verse_number' => 1,
            'lens_type' => 'science',
            'title' => 'Approved Science Analysis',
            'content' => 'This is the approved science analysis content.',
            'status' => 'approved',
        ]);

        // Create pending analysis
        QuranicLensAnalysis::create([
            'user_id' => $user->id,
            'chapter_number' => 2,
            'verse_number' => 5,
            'lens_type' => 'tafsir',
            'title' => 'Pending Tafsir Analysis',
            'content' => 'This is the pending tafsir analysis content.',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/research');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Approved Science Analysis')
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test research content filters (lens_type, chapter, verse).
     */
    public function test_research_content_filters_work(): void
    {
        $user = User::create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        QuranicLensAnalysis::create([
            'user_id' => $user->id,
            'chapter_number' => 2,
            'verse_number' => 10,
            'lens_type' => 'history',
            'title' => 'History Analysis',
            'content' => 'Content for history analysis.',
            'status' => 'approved',
        ]);

        QuranicLensAnalysis::create([
            'user_id' => $user->id,
            'chapter_number' => 3,
            'verse_number' => 15,
            'lens_type' => 'science',
            'title' => 'Science Analysis',
            'content' => 'Content for science analysis.',
            'status' => 'approved',
        ]);

        // Filter by lens_type
        $response = $this->getJson('/api/research?lens_type=history');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'History Analysis');

        // Filter by chapter_number
        $response2 = $this->getJson('/api/research?chapter_number=3');
        $response2->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Science Analysis');
    }

    /**
     * Test moderator can view pending research content.
     */
    public function test_moderator_can_view_pending_research_content(): void
    {
        $moderator = User::create([
            'name' => 'Moderator User',
            'email' => 'mod@example.com',
            'password' => bcrypt('password'),
            'is_researcher' => true,
        ]);

        $author = User::create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        QuranicLensAnalysis::create([
            'user_id' => $author->id,
            'chapter_number' => 2,
            'verse_number' => 10,
            'lens_type' => 'history',
            'title' => 'Pending Analysis',
            'content' => 'Content for pending analysis.',
            'status' => 'pending',
        ]);

        // Guest requesting status=pending should be ignored and get empty/approved list
        $guestResponse = $this->getJson('/api/research?status=pending');
        $guestResponse->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Authenticated moderator requesting status=pending
        Sanctum::actingAs($moderator);
        $modResponse = $this->getJson('/api/research?status=pending');
        $modResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Pending Analysis');
    }

    /**
     * Test submitting research fails when unauthenticated.
     */
    public function test_submitting_research_fails_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/research', [
            'chapter_number' => 1,
            'verse_number' => 1,
            'lens_type' => 'science',
            'title' => 'Unauthorized Post',
            'content' => 'This should fail because there is no auth token.',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test submitting research succeeds for a regular user and sets pending status.
     */
    public function test_submitting_research_succeeds_for_regular_user_sets_pending(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/research', [
            'chapter_number' => 1,
            'verse_number' => 1,
            'lens_type' => 'science',
            'title' => 'Valid Submission',
            'content' => 'This is a valid research submission content.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('quranic_lens_analyses', [
            'title' => 'Valid Submission',
            'status' => 'pending',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test submitting research succeeds for a researcher and sets approved status.
     */
    public function test_submitting_research_succeeds_for_researcher_sets_approved(): void
    {
        $researcher = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'password' => bcrypt('password'),
            'is_researcher' => true,
        ]);

        Sanctum::actingAs($researcher);

        $response = $this->postJson('/api/research', [
            'chapter_number' => 2,
            'verse_number' => 5,
            'lens_type' => 'history',
            'title' => 'Researcher Submission',
            'content' => 'This is a researcher research submission content.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('quranic_lens_analyses', [
            'title' => 'Researcher Submission',
            'status' => 'approved',
            'user_id' => $researcher->id,
        ]);
    }
}
