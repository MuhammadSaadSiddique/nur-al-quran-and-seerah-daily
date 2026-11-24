<?php

namespace Tests\Feature;

use App\Models\Scholar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScholarOnboardingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Ibn Kathir',
            'title' => 'Imam',
            'credentials' => 'Student of Ibn Taymiyyah and classical hadith and tafsir teachers.',
            'expertise' => 'Tafsir and history',
            'background' => 'Known for Tafsir al-Quran al-Azim and Al-Bidaya wa al-Nihaya.',
            'languages' => 'Arabic',
            'sources' => 'Tafsir Ibn Kathir, Al-Bidaya wa al-Nihaya',
            'works' => 'Tafsir al-Quran al-Azim',
            'location' => 'Damascus',
            'authenticity_pledge' => '1',
        ], $overrides);
    }

    public function test_guest_is_redirected_from_onboarding(): void
    {
        $this->get(route('scholars.onboarding'))->assertRedirect(route('login'));
        $this->post(route('scholars.onboarding.store'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_directory_is_public_and_lists_only_published_scholars(): void
    {
        Scholar::query()->create([
            'full_name' => 'Published Scholar',
            'slug' => 'published-scholar',
            'credentials' => 'Credentials',
            'expertise' => 'Fiqh',
            'background' => 'Background',
            'sources' => 'Sources',
            'pledge_statement' => Scholar::PLEDGE,
            'pledged_at' => now(),
            'status' => Scholar::STATUS_PUBLISHED,
        ]);
        Scholar::query()->create([
            'full_name' => 'Hidden Scholar',
            'slug' => 'hidden-scholar',
            'credentials' => 'Credentials',
            'expertise' => 'Hadith',
            'background' => 'Background',
            'sources' => 'Sources',
            'pledge_statement' => Scholar::PLEDGE,
            'pledged_at' => now(),
            'status' => Scholar::STATUS_PENDING,
        ]);

        $this->get(route('scholars.index'))
            ->assertOk()
            ->assertSee('Published Scholar')
            ->assertDontSee('Hidden Scholar');

        $this->get(route('scholars.show', 'published-scholar'))->assertOk()->assertSee('Fiqh');
        $this->get(route('scholars.show', 'hidden-scholar'))->assertNotFound();
    }

    public function test_user_can_submit_one_profile_and_update_it_without_publishing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('scholars.onboarding'))
            ->assertOk()
            ->assertSee('Scholar onboarding')
            ->assertSee(Scholar::PLEDGE);

        $this->actingAs($user)
            ->post(route('scholars.onboarding.store'), $this->payload())
            ->assertRedirect(route('scholars.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('scholars', [
            'user_id' => $user->id,
            'full_name' => 'Ibn Kathir',
            'status' => Scholar::STATUS_PENDING,
            'pledge_statement' => Scholar::PLEDGE,
        ]);

        $this->actingAs($user)
            ->post(route('scholars.onboarding.store'), $this->payload(['full_name' => 'Updated Name']))
            ->assertRedirect(route('scholars.index'));

        $this->assertDatabaseCount('scholars', 1);
        $this->assertDatabaseHas('scholars', [
            'user_id' => $user->id,
            'full_name' => 'Updated Name',
            'status' => Scholar::STATUS_PENDING,
        ]);
    }

    public function test_pledge_and_details_are_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('scholars.onboarding'))
            ->post(route('scholars.onboarding.store'), [
                'full_name' => '',
                'credentials' => '',
                'expertise' => '',
                'background' => '',
                'sources' => '',
            ])
            ->assertRedirect(route('scholars.onboarding'))
            ->assertSessionHasErrors([
                'full_name',
                'credentials',
                'expertise',
                'background',
                'sources',
                'authenticity_pledge',
            ]);

        $this->assertDatabaseCount('scholars', 0);
    }

    public function test_admin_can_add_as_many_scholars_as_needed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('scholars.onboarding'))
            ->assertOk()
            ->assertSee('Add a scholar')
            ->assertSee('as many as you need');

        $this->actingAs($admin)
            ->post(route('scholars.onboarding.store'), $this->payload(['full_name' => 'First Scholar']))
            ->assertRedirect(route('scholars.onboarding'))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('scholars.onboarding.store'), $this->payload([
                'full_name' => 'Second Scholar',
                'expertise' => 'Seerah',
            ]))
            ->assertRedirect(route('scholars.onboarding'));

        $this->assertDatabaseCount('scholars', 2);
        $this->assertDatabaseHas('scholars', [
            'full_name' => 'First Scholar',
            'added_by' => $admin->id,
            'user_id' => null,
            'status' => Scholar::STATUS_PUBLISHED,
        ]);
        $this->assertDatabaseHas('scholars', [
            'full_name' => 'Second Scholar',
            'status' => Scholar::STATUS_PUBLISHED,
        ]);

        $this->get(route('scholars.index'))
            ->assertOk()
            ->assertSee('First Scholar')
            ->assertSee('Second Scholar');
    }

    public function test_admin_can_publish_hide_edit_and_remove_a_scholar(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $applicant = User::factory()->create();
        $scholar = Scholar::query()->create([
            'user_id' => $applicant->id,
            'added_by' => $applicant->id,
            'full_name' => 'Waiting Scholar',
            'slug' => 'waiting-scholar',
            'credentials' => 'Credentials',
            'expertise' => 'Aqeedah',
            'background' => 'Background',
            'sources' => 'Sources',
            'pledge_statement' => Scholar::PLEDGE,
            'pledged_at' => now(),
            'status' => Scholar::STATUS_PENDING,
        ]);

        $this->actingAs($applicant)->get(route('admin.scholars.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.scholars.index'))
            ->assertOk()
            ->assertSee('Waiting Scholar')
            ->assertSee('Publish');

        $this->actingAs($admin)
            ->patch(route('admin.scholars.publish', $scholar))
            ->assertRedirect();

        $this->assertSame(Scholar::STATUS_PUBLISHED, $scholar->fresh()->status);

        $this->actingAs($admin)
            ->put(route('admin.scholars.update', $scholar), $this->payload([
                'full_name' => 'Published Name',
                'expertise' => 'Hadith',
            ]))
            ->assertRedirect(route('admin.scholars.index'));

        $this->assertDatabaseHas('scholars', [
            'id' => $scholar->id,
            'full_name' => 'Published Name',
            'expertise' => 'Hadith',
            'status' => Scholar::STATUS_PUBLISHED,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.scholars.destroy', $scholar))
            ->assertRedirect();

        $this->assertDatabaseMissing('scholars', ['id' => $scholar->id]);
    }

    public function test_sitemap_includes_the_scholars_directory(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('scholars.index'), false);
    }
}
