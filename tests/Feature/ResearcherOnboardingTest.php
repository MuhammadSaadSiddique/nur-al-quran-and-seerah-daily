<?php

namespace Tests\Feature;

use App\Models\ResearcherOnboarding;
use App\Models\ScienceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResearcherOnboardingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Aisha Rahman',
            'credentials' => 'Studied tafsir with local teachers for eight years.',
            'expertise' => 'Tafsir and Seerah',
            'background' => 'I review verse commentary and seerah links before they are published.',
            'languages' => 'Arabic, English',
            'sources' => 'Quran, Sahih al-Bukhari, Tafsir Ibn Kathir',
            'authenticity_pledge' => '1',
        ], $overrides);
    }

    public function test_guest_is_redirected_from_onboarding(): void
    {
        $this->get(route('researchers.onboarding'))->assertRedirect(route('login'));
        $this->post(route('researchers.onboarding.store'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_user_can_submit_details_and_pledge_without_gaining_researcher_access(): void
    {
        $user = User::create([
            'name' => 'Applicant',
            'email' => 'applicant@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user)->get(route('researchers.onboarding'))
            ->assertOk()
            ->assertSee('Researcher onboarding plan')
            ->assertSee('Authenticity pledge')
            ->assertSee(ResearcherOnboarding::PLEDGE);

        $response = $this->actingAs($user)->post(route('researchers.onboarding.store'), $this->payload());

        $response->assertRedirect(route('researchers.index'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertFalse((bool) $user->is_researcher);
        $this->assertTrue($user->hasAcceptedResearcherPledge());

        $this->assertDatabaseHas('researcher_onboardings', [
            'user_id' => $user->id,
            'full_name' => 'Aisha Rahman',
            'expertise' => 'Tafsir and Seerah',
            'pledge_statement' => ResearcherOnboarding::PLEDGE,
        ]);
    }

    public function test_pledge_and_details_are_required(): void
    {
        $user = User::create([
            'name' => 'Applicant',
            'email' => 'applicant@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->from(route('researchers.onboarding'))
            ->post(route('researchers.onboarding.store'), [
                'full_name' => '',
                'credentials' => '',
                'expertise' => '',
                'background' => '',
                'sources' => '',
            ]);

        $response->assertRedirect(route('researchers.onboarding'));
        $response->assertSessionHasErrors([
            'full_name',
            'credentials',
            'expertise',
            'background',
            'sources',
            'authenticity_pledge',
        ]);
        $this->assertDatabaseCount('researcher_onboardings', 0);
    }

    public function test_user_can_update_onboarding_only_by_reconfirming_the_pledge(): void
    {
        $user = User::create([
            'name' => 'Applicant',
            'email' => 'applicant@example.com',
            'password' => bcrypt('password'),
        ]);
        ResearcherOnboarding::acceptFor($user, ['full_name' => 'First Name']);

        $this->actingAs($user)->from(route('researchers.onboarding'))
            ->post(route('researchers.onboarding.store'), $this->payload([
                'full_name' => 'Updated Name',
                'authenticity_pledge' => null,
            ]))
            ->assertSessionHasErrors('authenticity_pledge');

        $this->assertDatabaseHas('researcher_onboardings', [
            'user_id' => $user->id,
            'full_name' => 'First Name',
        ]);

        $this->actingAs($user)->post(route('researchers.onboarding.store'), $this->payload([
            'full_name' => 'Updated Name',
        ]))->assertRedirect(route('researchers.index'));

        $this->assertDatabaseCount('researcher_onboardings', 1);
        $this->assertDatabaseHas('researcher_onboardings', [
            'user_id' => $user->id,
            'full_name' => 'Updated Name',
        ]);
    }

    public function test_researcher_without_pledge_cannot_open_or_use_approvals(): void
    {
        $researcher = User::create([
            'name' => 'Researcher',
            'email' => 'researcher@example.com',
            'password' => bcrypt('password'),
            'is_researcher' => true,
        ]);

        $this->actingAs($researcher)->get(route('admin.lens.approvals.index'))
            ->assertRedirect(route('researchers.onboarding'));

        $this->actingAs($researcher)->post(route('admin.lens.approvals.approve', ['analysis', 1]))
            ->assertRedirect(route('researchers.onboarding'));
    }

    public function test_researcher_with_pledge_can_open_approvals(): void
    {
        $researcher = User::create([
            'name' => 'Researcher',
            'email' => 'researcher@example.com',
            'password' => bcrypt('password'),
            'is_researcher' => true,
        ]);
        ResearcherOnboarding::acceptFor($researcher);

        $this->actingAs($researcher)->get(route('admin.lens.approvals.index'))
            ->assertOk();
    }

    public function test_admin_can_approve_without_a_personal_pledge(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.lens.approvals.index'))->assertOk();
    }

    public function test_credentials_are_visible_to_admins_and_hidden_from_the_public_directory(): void
    {
        $applicant = User::create([
            'name' => 'Applicant',
            'email' => 'applicant@example.com',
            'password' => bcrypt('password'),
        ]);
        ResearcherOnboarding::acceptFor($applicant, [
            'full_name' => 'Private Applicant',
            'credentials' => 'Secret teaching chain',
            'expertise' => 'Arabic rhetoric',
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $this->get(route('researchers.index'))
            ->assertOk()
            ->assertDontSee('Secret teaching chain')
            ->assertDontSee('Private Applicant');

        $this->actingAs($admin)->get(route('researchers.index'))
            ->assertOk()
            ->assertSee('Secret teaching chain')
            ->assertSee('Private Applicant')
            ->assertSee('Grant researcher access');
    }

    public function test_onboarding_can_request_a_specialty_without_assigning_it(): void
    {
        $category = ScienceCategory::create([
            'name' => 'Geology',
            'slug' => 'geology',
            'emoji' => '🪨',
            'mapped_fields' => 'geology, earth',
        ]);

        $user = User::create([
            'name' => 'Applicant',
            'email' => 'applicant@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user)->post(route('researchers.onboarding.store'), $this->payload([
            'requested_expert_category_id' => $category->id,
        ]))->assertRedirect(route('researchers.index'));

        $user->refresh();
        $this->assertNull($user->expert_category_id);
        $this->assertSame($category->id, $user->researcherOnboarding->requested_expert_category_id);
    }
}
