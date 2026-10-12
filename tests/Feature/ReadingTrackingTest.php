<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_surah_as_read(): void
    {
        $user = User::factory()->create([
            'email' => 'reader@example.com',
            'para_mastery' => [],
        ]);

        $this->actingAs($user);

        // Mark Surah 1 as read
        $response = $this->postJson(route('reading.toggle-surah'), [
            'surah' => 1,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_read' => true,
                'surah' => 1,
                'total_completed' => 1,
            ]);

        $user->refresh();
        $this->assertContains(1, $user->getCompletedSurahs());

        // Toggle again to unmark
        $response2 = $this->postJson(route('reading.toggle-surah'), [
            'surah' => 1,
        ]);

        $response2->assertOk()
            ->assertJson([
                'success' => true,
                'is_read' => false,
                'surah' => 1,
                'total_completed' => 0,
            ]);

        $user->refresh();
        $this->assertNotContains(1, $user->getCompletedSurahs());
    }

    public function test_user_can_toggle_para_as_read(): void
    {
        $user = User::factory()->create([
            'email' => 'reader2@example.com',
            'completed_paras' => [],
        ]);

        $this->actingAs($user);

        // Mark Para 5 as completed
        $response = $this->postJson(route('reading.toggle-para'), [
            'para' => 5,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_completed' => true,
                'para' => 5,
                'total_completed' => 1,
            ]);

        $user->refresh();
        $this->assertContains(5, array_map('intval', $user->completed_paras ?? []));

        // Toggle again to unmark
        $response2 = $this->postJson(route('reading.toggle-para'), [
            'para' => 5,
        ]);

        $response2->assertOk()
            ->assertJson([
                'success' => true,
                'is_completed' => false,
                'para' => 5,
                'total_completed' => 0,
            ]);

        $user->refresh();
        $this->assertNotContains(5, array_map('intval', $user->completed_paras ?? []));
    }

    public function test_user_can_mark_seerah_and_history_insights_as_read(): void
    {
        $user = User::factory()->create([
            'email' => 'insight_reader@example.com',
            'seerah_read_count' => 0,
            'quran_history_read_count' => 0,
        ]);

        $this->actingAs($user);

        // Mark Seerah insight as read
        $response = $this->postJson(route('reading.mark-insight'), [
            'type' => 'SEERAH',
            'insight_id' => 'q-seerah-101',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'type' => 'SEERAH',
                'seerah_read_count' => 1,
            ]);

        $user->refresh();
        $this->assertEquals(1, $user->seerah_read_count);
        $this->assertTrue($user->isInsightRead('SEERAH', 'q-seerah-101'));

        // Marking the exact same insight again does not double count
        $responseDuplicate = $this->postJson(route('reading.mark-insight'), [
            'type' => 'SEERAH',
            'insight_id' => 'q-seerah-101',
        ]);

        $responseDuplicate->assertOk()
            ->assertJson([
                'success' => true,
                'already_read' => true,
            ]);

        $user->refresh();
        $this->assertEquals(1, $user->seerah_read_count);

        // Mark Quran History insight as read
        $responseHistory = $this->postJson(route('reading.mark-insight'), [
            'type' => 'QURAN_HISTORY',
            'insight_id' => 'q-qh-202',
        ]);

        $responseHistory->assertOk()
            ->assertJson([
                'success' => true,
                'type' => 'QURAN_HISTORY',
                'quran_history_read_count' => 1,
            ]);

        $user->refresh();
        $this->assertEquals(1, $user->quran_history_read_count);
        $this->assertTrue($user->isInsightRead('QURAN_HISTORY', 'q-qh-202'));
    }

    public function test_guest_cannot_toggle_reading_status(): void
    {
        $response = $this->postJson(route('reading.toggle-surah'), [
            'surah' => 1,
        ]);

        $response->assertUnauthorized();
    }
}
