<?php

namespace Tests\Feature;

use App\Models\Mosque;
use App\Models\MosqueContribution;
use App\Models\SalahFavorite;
use App\Models\User;
use App\Services\DailyEngagementService;
use App\Services\MosqueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MosqueDirectoryTest extends TestCase
{
    use RefreshDatabase;

    // Gulshan-e-Iqbal, Karachi
    private const LAT = 24.9204;
    private const LNG = 67.0932;

    private function service(): MosqueService
    {
        return app(MosqueService::class);
    }

    private function mosque(array $overrides = []): Mosque
    {
        return Mosque::create(array_merge([
            'name' => 'Masjid Al-Noor',
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'fajr_time' => '05:45',
            'dhuhr_time' => '13:30',
            'status' => Mosque::STATUS_ACTIVE,
            'times_updated_at' => now(),
        ], $overrides));
    }

    public function test_nearby_returns_closest_first_within_radius_and_skips_hidden(): void
    {
        $near = $this->mosque(['name' => 'Near Masjid', 'latitude' => self::LAT + 0.002]);          // ~220 m
        $mid = $this->mosque(['name' => 'Mid Masjid', 'latitude' => self::LAT + 0.02]);             // ~2.2 km
        $this->mosque(['name' => 'Far Masjid', 'latitude' => self::LAT + 0.5]);                     // ~55 km
        $this->mosque(['name' => 'Hidden Masjid', 'latitude' => self::LAT + 0.001, 'status' => Mosque::STATUS_HIDDEN]);

        $results = $this->service()->nearby(self::LAT, self::LNG, 5);

        $this->assertSame([$near->id, $mid->id], array_column($results, 'id'));
        $this->assertEqualsWithDelta(0.22, $results[0]['distance_km'], 0.02);
        $this->assertSame('05:45', $results[0]['times']['fajr']);
    }

    public function test_search_finds_mosque_by_area_outside_radius(): void
    {
        $this->mosque(['name' => 'Jamia Masjid Tooba', 'locality' => 'Defence Phase 2', 'latitude' => self::LAT - 0.1]);

        $results = $this->service()->nearby(self::LAT, self::LNG, 2, 30, 'Defence');

        $this->assertCount(1, $results);
        $this->assertSame('Jamia Masjid Tooba', $results[0]['name']);
    }

    public function test_nearby_without_location_or_search_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->service()->nearby(null, null);
    }

    public function test_user_can_add_mosque_and_it_counts_as_daily_contribution(): void
    {
        $user = User::factory()->create(['streak_count' => 0, 'last_streak_date' => null]);

        $result = $this->service()->create($user, [
            'name' => '  Masjid   Bilal ',
            'locality' => 'Block 13',
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'fajr_time' => '5:05',
            'isha_time' => '20:15',
            'maghrib_time' => '17:40',
            'maghrib_offset_minutes' => '7',
            'has_women_area' => 'yes',
        ]);

        $this->assertFalse($result['duplicate']);
        $this->assertSame('Masjid Bilal', $result['mosque']['name']);
        $this->assertSame('05:05', $result['mosque']['times']['fajr']);
        // A sunset gap replaces a fixed Maghrib clock time.
        $this->assertNull($result['mosque']['times']['maghrib']);
        $this->assertSame(7, $result['mosque']['maghrib_offset_minutes']);
        $this->assertTrue($result['mosque']['has_women_area']);
        $this->assertFalse($result['mosque']['is_stale']);

        $this->assertDatabaseHas('mosque_contributions', [
            'user_id' => $user->id,
            'type' => MosqueContribution::TYPE_ADDED,
        ]);

        $status = app(DailyEngagementService::class)->todayStatus($user->fresh());
        $this->assertTrue($status['activities']['contribute']['done']);
    }

    public function test_adding_a_mosque_that_already_exists_nearby_returns_the_existing_one(): void
    {
        $existing = $this->mosque(['name' => 'Jamia Masjid Al-Noor']);
        $user = User::factory()->create();

        $result = $this->service()->create($user, [
            'name' => 'Al Noor Mosque',
            'latitude' => self::LAT + 0.0004, // ~45 m away
            'longitude' => self::LNG,
        ]);

        $this->assertTrue($result['duplicate']);
        $this->assertSame($existing->id, $result['mosque']['id']);
        $this->assertDatabaseCount('mosques', 1);
    }

    public function test_same_name_in_another_neighbourhood_is_a_different_mosque(): void
    {
        $this->mosque(['name' => 'Masjid Al-Noor']);
        $user = User::factory()->create();

        $result = $this->service()->create($user, [
            'name' => 'Masjid Al-Noor',
            'latitude' => self::LAT + 0.03, // ~3 km away
            'longitude' => self::LNG,
        ]);

        $this->assertFalse($result['duplicate']);
        $this->assertDatabaseCount('mosques', 2);
    }

    public function test_badly_formatted_time_is_rejected(): void
    {
        $user = User::factory()->create();

        try {
            $this->service()->create($user, [
                'name' => 'Masjid Umar',
                'latitude' => self::LAT,
                'longitude' => self::LNG,
                'fajr_time' => '25:99',
            ]);
            $this->fail('Expected a validation error.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fajr_time', $e->errors());
        }

        $this->assertDatabaseCount('mosques', 0);
    }

    public function test_updating_times_keeps_history_and_resets_confirmations(): void
    {
        $mosque = $this->mosque(['confirmations_count' => 4]);
        $user = User::factory()->create();

        $result = $this->service()->updateTimes($user, $mosque, [
            'fajr_time' => '05:30',
            'dhuhr_time' => '13:30',
            'jumuah_time' => '13:15',
        ]);

        $this->assertTrue($result['changed']);
        $this->assertSame('05:30', $result['mosque']['times']['fajr']);
        $this->assertSame('13:15', $result['mosque']['times']['jumuah']);
        $this->assertSame(0, $result['mosque']['confirmations_count']);

        $log = MosqueContribution::where('type', MosqueContribution::TYPE_TIMES_UPDATED)->firstOrFail();
        $this->assertSame('05:45', $log->payload['before']['fajr_time']);
        $this->assertSame('05:30', $log->payload['after']['fajr_time']);
        $this->assertSame($user->id, $mosque->fresh()->times_updated_by);
    }

    public function test_submitting_unchanged_times_counts_as_a_confirmation(): void
    {
        $mosque = $this->mosque();
        $user = User::factory()->create();

        $result = $this->service()->updateTimes($user, $mosque, [
            'fajr_time' => '05:45',
            'dhuhr_time' => '13:30',
        ]);

        $this->assertFalse($result['changed']);
        $this->assertSame(1, $mosque->fresh()->confirmations_count);
        $this->assertDatabaseMissing('mosque_contributions', ['type' => MosqueContribution::TYPE_TIMES_UPDATED]);
    }

    public function test_confirmation_counts_once_per_worshipper_and_not_for_own_fresh_update(): void
    {
        $updater = User::factory()->create();
        $mosque = $this->mosque(['times_updated_by' => $updater->id]);
        $neighbour = User::factory()->create();

        $this->assertTrue($this->service()->confirmTimes($neighbour, $mosque)['counted']);
        $this->assertFalse($this->service()->confirmTimes($neighbour, $mosque->fresh())['counted']);
        $this->assertFalse($this->service()->confirmTimes($updater, $mosque->fresh())['counted']);

        $this->assertSame(1, $mosque->fresh()->confirmations_count);
    }

    public function test_unverified_mosque_is_hidden_after_enough_distinct_reports(): void
    {
        $mosque = $this->mosque();
        $first = User::factory()->create();

        $this->service()->report($first, $mosque, ['reason' => 'closed']);
        // The same person reporting again does not add weight.
        $this->assertFalse($this->service()->report($first, $mosque->fresh(), ['reason' => 'closed'])['counted']);

        for ($i = 1; $i < MosqueService::AUTO_HIDE_REPORTS; $i++) {
            $result = $this->service()->report(User::factory()->create(), $mosque->fresh(), ['reason' => 'duplicate']);
        }

        $this->assertTrue($result['hidden']);
        $this->assertSame(Mosque::STATUS_HIDDEN, $mosque->fresh()->status);
        $this->assertSame([], $this->service()->nearby(self::LAT, self::LNG));
    }

    public function test_verified_mosque_stays_visible_when_reported(): void
    {
        $mosque = $this->mosque(['is_verified' => true]);

        for ($i = 0; $i < MosqueService::AUTO_HIDE_REPORTS + 1; $i++) {
            $this->service()->report(User::factory()->create(), $mosque->fresh(), ['reason' => 'wrong_times']);
        }

        $this->assertSame(Mosque::STATUS_ACTIVE, $mosque->fresh()->status);
    }

    public function test_each_salah_can_point_to_a_different_mosque(): void
    {
        $home = $this->mosque(['name' => 'Masjid near home']);
        $office = $this->mosque(['name' => 'Masjid near office', 'latitude' => self::LAT + 0.05]);
        $user = User::factory()->create();

        $this->service()->setFavorite($user, 'fajr', $home->id);
        $this->service()->setFavorite($user, 'isha', $home->id);
        $this->service()->setFavorite($user, 'dhuhr', $office->id);
        $favorites = $this->service()->setFavorite($user, 'jumuah', $office->id);

        $this->assertSame(['fajr', 'dhuhr', 'isha', 'jumuah'], array_keys($favorites));
        $this->assertSame($home->id, $favorites['fajr']['id']);
        $this->assertSame($office->id, $favorites['dhuhr']['id']);
        $this->assertSame(1, $favorites['fajr']['regulars_count']);

        // Changing Fajr to the office replaces the row instead of adding one.
        $this->service()->setFavorite($user, 'fajr', $office->id);
        $this->assertSame(4, SalahFavorite::where('user_id', $user->id)->count());

        $favorites = $this->service()->setFavorite($user, 'isha', null);
        $this->assertArrayNotHasKey('isha', $favorites);
    }

    public function test_hidden_mosque_drops_out_of_favorites_and_unknown_prayer_is_rejected(): void
    {
        $mosque = $this->mosque();
        $user = User::factory()->create();
        $this->service()->setFavorite($user, 'asr', $mosque->id);

        $mosque->update(['status' => Mosque::STATUS_HIDDEN]);
        $this->assertSame([], $this->service()->favorites($user));

        $this->expectException(ValidationException::class);
        $this->service()->setFavorite($user, 'tahajjud', $mosque->id);
    }

    public function test_contribution_summary_counts_worshippers_using_your_mosques(): void
    {
        $contributor = User::factory()->create();
        $added = $this->service()->create($contributor, [
            'name' => 'Masjid Hamza',
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'fajr_time' => '05:40',
        ])['mosque'];

        foreach (User::factory()->count(3)->create() as $worshipper) {
            $this->service()->setFavorite($worshipper, 'fajr', $added['id']);
        }
        // Your own favourite is not counted as someone you helped.
        $this->service()->setFavorite($contributor, 'fajr', $added['id']);

        $summary = $this->service()->contributionSummary($contributor);

        $this->assertSame(1, $summary['mosques_added']);
        $this->assertSame(1, $summary['mosques_helped']);
        $this->assertSame(3, $summary['worshippers_benefiting']);
        $this->assertSame('Masjid Hamza', $summary['recent'][0]['mosque_name']);
    }

    public function test_admin_can_review_verify_and_hide_mosques(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $mosque = $this->mosque(['name' => 'Masjid Under Review', 'reports_count' => 1]);
        MosqueContribution::create([
            'mosque_id' => $mosque->id,
            'user_id' => User::factory()->create()->id,
            'type' => MosqueContribution::TYPE_REPORTED,
            'payload' => ['reason' => 'wrong_location'],
            'note' => 'Pin is on the wrong street',
        ]);

        $this->actingAs($admin)->get(route('admin.mosques.index'))
            ->assertOk()
            ->assertSee('Masjid Under Review')
            ->assertSee('Pin is on the wrong street');

        $this->actingAs($admin)->patch(route('admin.mosques.moderate', $mosque), ['action' => 'verify'])
            ->assertSessionHas('success');
        $this->assertTrue($mosque->fresh()->is_verified);

        $this->actingAs($admin)->patch(route('admin.mosques.moderate', $mosque), ['action' => 'hide']);
        $this->assertSame(Mosque::STATUS_HIDDEN, $mosque->fresh()->status);
    }

    public function test_new_mosque_keeps_profile_facilities_and_ramadan_details(): void
    {
        $user = User::factory()->create();

        // Shaped like the mobile form post: strings, bracket arrays, comma lists.
        $mosque = $this->service()->create($user, [
            'name' => 'Musalla Saddar Market',
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'place_type' => 'musalla',
            'sect' => 'barelvi',
            'asr_method' => 'hanafi',
            'jumuah_time' => '13:15',
            'jumuah_time_2' => '14:00',
            'facilities' => [
                'wheelchair_entrance' => '1',
                'prayer_chairs' => '1',
                'car_parking' => '0',
                'air_conditioning' => '',
                'helipad' => '1', // unknown keys are ignored
            ],
            'khutbah_languages' => 'ur,ar,xx',
            'taraweeh_rakats' => '20',
            'taraweeh_khatm' => 'yes',
            'eid_year' => '2027',
            'eid_fitr_times' => '8:30,07:00',
        ])['mosque'];

        $this->assertSame('musalla', $mosque['place_type']);
        $this->assertSame('barelvi', $mosque['sect']);
        $this->assertFalse($mosque['sect_change_pending']);
        $this->assertSame('hanafi', $mosque['asr_method']);
        $this->assertSame('14:00', $mosque['jumuah_second_time']);
        $this->assertTrue($mosque['facilities']['wheelchair_entrance']);
        $this->assertFalse($mosque['facilities']['car_parking']);
        $this->assertNull($mosque['facilities']['air_conditioning']);
        $this->assertArrayNotHasKey('helipad', $mosque['facilities']);
        $this->assertSame(['ar', 'ur'], $mosque['khutbah_languages']);
        $this->assertSame(['rakats' => 20, 'khatm' => true], $mosque['taraweeh']);
        $this->assertSame(['year' => 2027, 'times' => ['07:00', '08:30']], ((array) $mosque['eid_times'])['fitr']);
    }

    public function test_detail_edits_go_live_but_sect_changes_wait_for_admin(): void
    {
        $mosque = $this->mosque(['sect' => 'deobandi', 'facilities' => ['toilets' => true]]);
        $neighbour = User::factory()->create();

        $result = $this->service()->updateDetails($neighbour, $mosque, [
            'sect' => 'ahl_e_hadith',
            'facilities' => ['wheelchair_entrance' => '1'],
        ]);

        $this->assertTrue($result['changed']);
        $this->assertTrue($result['sect_pending']);
        $this->assertSame('deobandi', $result['mosque']['sect']);
        $this->assertTrue($result['mosque']['sect_change_pending']);
        // Facilities merge: the earlier "toilets" answer survives.
        $this->assertTrue($result['mosque']['facilities']['wheelchair_entrance']);
        $this->assertTrue($result['mosque']['facilities']['toilets']);
        $this->assertDatabaseHas('mosque_contributions', ['type' => MosqueContribution::TYPE_SECT_PROPOSED, 'user_id' => $neighbour->id]);

        $this->service()->resolveSectProposal($mosque->fresh(), true);
        $this->assertSame('ahl_e_hadith', $mosque->fresh()->sect);
        $this->assertNull($mosque->fresh()->pending_sect);
    }

    public function test_rejected_or_cleared_sect_proposals_and_admin_edits(): void
    {
        $mosque = $this->mosque(['sect' => 'bohra']);

        // Proposing "not specified" is also reviewed, then rejected.
        $this->service()->updateDetails(User::factory()->create(), $mosque, ['sect' => '']);
        $this->assertSame(Mosque::SECT_NONE, $mosque->fresh()->pending_sect);
        $this->service()->resolveSectProposal($mosque->fresh(), false);
        $this->assertSame('bohra', $mosque->fresh()->sect);
        $this->assertNull($mosque->fresh()->pending_sect);

        // Admins change it directly.
        $admin = User::factory()->create(['is_admin' => true]);
        $result = $this->service()->updateDetails($admin, $mosque->fresh(), ['sect' => 'ismaili']);
        $this->assertFalse($result['sect_pending']);
        $this->assertSame('ismaili', $mosque->fresh()->sect);
    }

    public function test_partial_detail_update_leaves_other_fields_and_noop_is_reported(): void
    {
        $mosque = $this->mosque([
            'khutbah_languages' => ['ur'],
            'eid_times' => ['adha' => ['year' => 2027, 'times' => ['06:30']]],
        ]);
        $user = User::factory()->create();

        $this->service()->updateDetails($user, $mosque, ['eid_fitr_times' => '07:00', 'eid_year' => '2027']);
        $fresh = $mosque->fresh();
        $this->assertSame(['ur'], $fresh->khutbah_languages);
        $this->assertSame(['adha', 'fitr'], array_keys($fresh->eid_times));

        $again = $this->service()->updateDetails($user, $fresh, ['khutbah_languages' => 'ur']);
        $this->assertFalse($again['changed']);
    }

    public function test_invalid_sect_and_eid_time_are_rejected(): void
    {
        $mosque = $this->mosque();
        $user = User::factory()->create();

        foreach ([['sect' => 'made_up'], ['eid_adha_times' => '25:61']] as $input) {
            try {
                $this->service()->updateDetails($user, $mosque, $input);
                $this->fail('Expected a validation error for ' . json_encode($input));
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
    }

    public function test_nearby_filters_by_accessibility_jumuah_and_sect(): void
    {
        $accessible = $this->mosque([
            'name' => 'Accessible Masjid',
            'facilities' => ['wheelchair_entrance' => true, 'prayer_chairs' => true],
            'jumuah_time' => '13:30',
            'sect' => 'deobandi',
        ]);
        $this->mosque(['name' => 'Steps Only', 'latitude' => self::LAT + 0.001, 'facilities' => ['wheelchair_entrance' => false]]);
        $musalla = $this->mosque(['name' => 'Office Musalla', 'latitude' => self::LAT + 0.002, 'sect' => 'shia_ithna_ashari']);

        $ids = fn (array $rows) => array_column($rows, 'id');

        $this->assertSame([$accessible->id], $ids($this->service()->nearby(self::LAT, self::LNG, 5, 30, null, ['wheelchair', 'chairs'])));
        $this->assertSame([$accessible->id], $ids($this->service()->nearby(self::LAT, self::LNG, 5, 30, null, ['jumuah'])));
        $this->assertSame([$musalla->id], $ids($this->service()->nearby(self::LAT, self::LNG, 5, 30, null, [], 'shia_ithna_ashari')));
        // Unknown filters and sects are ignored rather than emptying the list.
        $this->assertCount(3, $this->service()->nearby(self::LAT, self::LNG, 5, 30, null, ['bogus'], 'bogus'));
    }

    public function test_admin_sees_and_approves_sect_proposal(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $proposer = User::factory()->create(['name' => 'Bilal Proposer']);
        $mosque = $this->mosque(['name' => 'Masjid Awaiting Sect']);
        $this->service()->updateDetails($proposer, $mosque, ['sect' => 'sunni_other']);

        $this->actingAs($admin)->get(route('admin.mosques.index'))
            ->assertOk()
            ->assertSee('Masjid Awaiting Sect')
            ->assertSee('Proposed: Sunni · Other')
            ->assertSee('Bilal Proposer');

        $this->actingAs($admin)->patch(route('admin.mosques.moderate', $mosque), ['action' => 'approve_sect'])
            ->assertSessionHas('success');
        $this->assertSame('sunni_other', $mosque->fresh()->sect);
    }

    public function test_non_admin_cannot_open_mosque_moderation(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('admin.mosques.index'));

        $this->assertContains($response->status(), [302, 403]);
    }
}
