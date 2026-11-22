<?php

namespace Tests\Feature;

use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\RequirementSurvey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequirementSurveyTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'goals' => ['quran_understanding', 'seerah'],
            'role' => 'student',
            'daily_time' => '10min',
            'features' => ['para_quizzes' => 'love', 'salah' => 'try', 'leaderboard' => 'meh'],
            'wishlist' => ['audio_recitation', 'hifz', 'tafsir'],
            'languages' => ['english', 'urdu'],
            'platform' => 'android',
            'nps' => 9,
            'wish' => 'Word-by-word Urdu translation',
            'name' => 'Aisha',
            'email' => 'aisha@example.com',
            'notify' => true,
            'source' => 'android',
            'duration_seconds' => 95,
        ], $overrides);
    }

    public function test_survey_page_renders_for_guests_with_features(): void
    {
        $this->get(route('survey.show'))
            ->assertOk()
            ->assertSee('Customize my')
            ->assertSee('Para-by-para quizzes')
            ->assertSee('Salah times')
            // Android phones with the app installed are sent to eternalecho://survey.
            // ?web=1 is the fallback when the app is not installed, so the page does not loop.
            ->assertSee('intent://survey#Intent;scheme=eternalecho;package=com.asloobulhayat.eternalecho;', false)
            ->assertSee('Open in the app', false);
    }

    public function test_guest_can_submit_a_complete_survey(): void
    {
        $this->postJson(route('survey.store'), $this->payload())
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $response = SurveyResponse::sole();
        $this->assertSame(RequirementSurvey::KEY, $response->survey_key);
        $this->assertNull($response->user_id);
        $this->assertSame(['quran_understanding', 'seerah'], $response->answers['goals']);
        $this->assertSame('try', $response->answers['features']['salah']);
        $this->assertSame(9, $response->nps);
        $this->assertSame('android', $response->source);
        $this->assertTrue($response->notify);
    }

    public function test_unknown_options_and_over_limit_picks_are_rejected(): void
    {
        $this->postJson(route('survey.store'), $this->payload([
            'role' => 'astronaut',
            'wishlist' => ['audio_recitation', 'hifz', 'tafsir', 'offline'],
            'features' => ['para_quizzes' => 'adore'],
            'nps' => 11,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['role', 'wishlist', 'features.para_quizzes', 'nps']);

        $this->assertSame(0, SurveyResponse::count());
    }

    public function test_reacting_to_no_feature_cards_is_allowed(): void
    {
        $this->postJson(route('survey.store'), $this->payload(['features' => []]))->assertOk();

        $this->assertSame([], SurveyResponse::sole()->answers['features']);
    }

    public function test_signed_in_user_updates_their_single_response(): void
    {
        $user = User::factory()->create(['name' => 'Bilal']);

        $this->actingAs($user)->postJson(route('survey.store'), $this->payload(['name' => null, 'email' => null, 'nps' => 6]))->assertOk();
        $this->actingAs($user)->postJson(route('survey.store'), $this->payload(['name' => null, 'email' => null, 'nps' => 10]))->assertOk();

        $response = SurveyResponse::sole();
        $this->assertSame($user->id, $response->user_id);
        $this->assertSame(10, $response->nps);
        $this->assertSame($user->email, $response->email);
    }

    public function test_admin_sees_aggregated_results_and_can_export(): void
    {
        $this->postJson(route('survey.store'), $this->payload());
        $this->postJson(route('survey.store'), $this->payload(['nps' => 3, 'role' => 'parent', 'wish' => '=HYPERLINK("x")']));

        $summary = RequirementSurvey::summarize(SurveyResponse::all());
        $this->assertSame(2, $summary['total']);
        $this->assertSame(0, $summary['nps']['score']); // one promoter, one detractor
        $this->assertSame('audio_recitation', $summary['questions']['wishlist']['rows'][0]['value']);
        $this->assertSame(100, $summary['questions']['wishlist']['rows'][0]['percent']);

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.survey.index'))
            ->assertOk()
            ->assertSee('Requirements Survey')
            ->assertSee('Word-by-word Urdu translation');

        $this->actingAs($admin)->get(route('admin.survey.index', ['role' => 'parent']))
            ->assertOk()
            ->assertDontSee('Word-by-word Urdu translation');

        $csv = $this->actingAs($admin)->get(route('admin.survey.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('feature_para_quizzes', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_admin_sees_each_participant_and_can_search(): void
    {
        // No wishes, so the emails only appear in the participants table.
        $this->postJson(route('survey.store'), $this->payload(['wish' => null]));
        $this->postJson(route('survey.store'), $this->payload(['name' => 'Bilal', 'email' => 'bilal@example.com', 'wish' => null]));

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.survey.index'))
            ->assertOk()
            ->assertSee('Participants')
            ->assertSee('aisha@example.com')
            ->assertSee('bilal@example.com')
            ->assertSee('Audio recitation');

        $this->actingAs($admin)->get(route('admin.survey.index', ['q' => 'bilal']))
            ->assertOk()
            ->assertSee('bilal@example.com')
            ->assertDontSee('aisha@example.com');
    }

    public function test_app_config_names_an_in_app_screen_for_every_feature(): void
    {
        $config = RequirementSurvey::clientConfig();

        $this->assertCount(count(RequirementSurvey::features()), $config['features']);
        foreach ($config['features'] as $feature) {
            $this->assertContains($feature['app'], ['quiz', 'quran', 'seerah', 'duas', 'home', 'salah', 'leaderboard', 'research'], $feature['key']);
        }
        $this->assertSame('single', $config['questions']['role']['type']);
        $this->assertSame(3, $config['questions']['wishlist']['max']);
    }

    public function test_record_defaults_source_and_previous_answers_round_trip(): void
    {
        $user = User::factory()->create();

        RequirementSurvey::record($this->payload(['source' => null, 'name' => null, 'email' => null]), $user, 'android');

        $saved = SurveyResponse::sole();
        $this->assertSame('android', $saved->source);
        $this->assertSame($user->email, $saved->email);

        $previous = RequirementSurvey::previousFor($user);
        $this->assertSame('student', $previous['role']);
        $this->assertSame('love', $previous['features']['para_quizzes']);
        $this->assertSame(9, $previous['nps']);
        $this->assertNull(RequirementSurvey::previousFor(User::factory()->create()));
        $this->assertNull(RequirementSurvey::previousFor(null));
    }

    public function test_record_rejects_bad_input_from_the_app(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        RequirementSurvey::record($this->payload(['goals' => []]), null, 'android');
    }

    public function test_non_admins_cannot_see_results(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.survey.index'))->assertForbidden();
    }
}
