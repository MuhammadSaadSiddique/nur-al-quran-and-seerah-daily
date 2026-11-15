<?php

namespace Tests\Feature;

use App\Models\ResearcherOnboarding;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\RequirementSurvey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminResearchersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_team_applicants_details_and_activity(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $researcher = User::factory()->create(['is_researcher' => true, 'email' => 'team@example.com']);
        ResearcherOnboarding::acceptFor($researcher, ['full_name' => 'Dr Yusuf Team', 'credentials' => 'PhD Hadith sciences']);
        $applicant = User::factory()->create(['email' => 'applicant@example.com']);
        ResearcherOnboarding::acceptFor($applicant, ['full_name' => 'Maryam Applicant']);
        User::factory()->create(['email' => 'ordinary@example.com']);

        DB::table('quran_research_contributions')->insert([
            ['user_id' => $researcher->id, 'surah_number' => 1, 'title' => 'One', 'status' => 'published', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $researcher->id, 'surah_number' => 2, 'title' => 'Two', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        SurveyResponse::create([
            'survey_key' => RequirementSurvey::KEY,
            'answers' => ['role' => 'scholar'],
            'name' => 'Survey Scholar',
            'email' => 'scholar@example.com',
            'source' => 'web',
        ]);

        $page = $this->actingAs($admin)->get(route('admin.researchers.index'))->assertOk();
        $page->assertSee('Dr Yusuf Team')
            ->assertSee('PhD Hadith sciences')
            ->assertSee('Maryam Applicant')
            ->assertSee('Approve as researcher')
            ->assertSee('Survey Scholar')
            ->assertDontSee('ordinary@example.com');
        $this->assertSame(2, $page->viewData('activity')[$researcher->id]['contributions']);
        $this->assertSame(1, $page->viewData('activity')[$researcher->id]['published']);
        $this->assertSame(1, $page->viewData('stats')['applicants']);

        $this->actingAs($admin)->get(route('admin.researchers.index', ['status' => 'applicants']))
            ->assertOk()
            ->assertSee('Maryam Applicant')
            ->assertDontSee('Dr Yusuf Team');

        $this->actingAs($admin)->get(route('admin.researchers.index', ['q' => 'yusuf']))
            ->assertOk()
            ->assertSee('Dr Yusuf Team')
            ->assertDontSee('Maryam Applicant');
    }

    public function test_non_admins_cannot_see_researchers(): void
    {
        $researcher = User::factory()->create(['is_researcher' => true]);
        $this->actingAs($researcher)->get(route('admin.researchers.index'))->assertForbidden();
    }
}
