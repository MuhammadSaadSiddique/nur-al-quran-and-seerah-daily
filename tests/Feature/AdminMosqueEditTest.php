<?php

namespace Tests\Feature;

use App\Models\Mosque;
use App\Models\MosqueContribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMosqueEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Aisha']);
    }

    private function mosque(array $overrides = []): Mosque
    {
        return Mosque::create(array_merge([
            'name' => 'Masjid Al-Noor',
            'city' => 'Karachi',
            'latitude' => 24.9204,
            'longitude' => 67.0932,
            'fajr_time' => '05:45',
            'status' => Mosque::STATUS_ACTIVE,
            'times_updated_at' => now(),
        ], $overrides));
    }

    /** What the edit form posts for a mosque, before the admin changes anything. */
    private function formFor(Mosque $mosque, array $changes = []): array
    {
        $tri = fn ($v) => $v === null ? '' : ($v ? '1' : '0');
        $facilities = [];
        foreach ($mosque->facilityMap() as $key => $value) {
            $facilities[$key] = $tri($value);
        }

        return array_merge([
            'name' => $mosque->name,
            'locality' => (string) $mosque->locality,
            'city' => (string) $mosque->city,
            'address' => (string) $mosque->address,
            'country' => (string) $mosque->country,
            'latitude' => (string) $mosque->latitude,
            'longitude' => (string) $mosque->longitude,
            'status' => $mosque->status,
            'is_verified' => $mosque->is_verified ? '1' : '0',
            'place_type' => $mosque->place_type ?? 'masjid',
            'sect' => (string) $mosque->sect,
            'asr_method' => (string) $mosque->asr_method,
            'fajr_time' => (string) $mosque->fajr_time,
            'dhuhr_time' => (string) $mosque->dhuhr_time,
            'asr_time' => (string) $mosque->asr_time,
            'maghrib_time' => (string) $mosque->maghrib_time,
            'isha_time' => (string) $mosque->isha_time,
            'jumuah_time' => (string) $mosque->jumuah_time,
            'jumuah_time_2' => (string) $mosque->jumuah_time_2,
            'maghrib_offset_minutes' => (string) $mosque->maghrib_offset_minutes,
            'has_women_area' => $tri($mosque->has_women_area),
            'facilities' => $facilities,
            'khutbah_languages' => $mosque->khutbah_languages ?? '',
            'taraweeh_rakats' => (string) $mosque->taraweeh_rakats,
            'taraweeh_khatm' => $tri($mosque->taraweeh_khatm),
            'eid_year' => (string) now()->year,
            'eid_fitr_times' => implode(', ', $mosque->eid_times['fitr']['times'] ?? []),
            'eid_adha_times' => implode(', ', $mosque->eid_times['adha']['times'] ?? []),
        ], $changes);
    }

    public function test_edit_page_shows_current_values_and_history(): void
    {
        $mosque = $this->mosque(['facilities' => ['wheelchair_entrance' => true], 'sect' => 'deobandi']);
        MosqueContribution::create([
            'mosque_id' => $mosque->id,
            'user_id' => User::factory()->create(['name' => 'Reporter Bilal'])->id,
            'type' => MosqueContribution::TYPE_REPORTED,
            'payload' => ['reason' => 'wrong_times'],
        ]);

        $this->actingAs($this->admin)->get(route('admin.mosques.edit', $mosque))
            ->assertOk()
            ->assertSee('value="Masjid Al-Noor"', false)
            ->assertSee('value="05:45"', false)
            ->assertSee('Step-free (wheelchair) entrance')
            ->assertSee('Reporter Bilal')
            ->assertSee('Delete mosque');
    }

    public function test_admin_edit_saves_everything_and_keeps_not_sure_as_unknown(): void
    {
        $mosque = $this->mosque(['confirmations_count' => 6, 'has_women_area' => null]);

        $this->actingAs($this->admin)
            ->put(route('admin.mosques.update', $mosque), $this->formFor($mosque, [
                'name' => 'Jamia Masjid Al-Noor',
                'fajr_time' => '05:30',
                'maghrib_time' => '17:50',
                'maghrib_offset_minutes' => '5',
                'jumuah_time' => '13:15',
                'jumuah_time_2' => '14:00',
                'has_women_area' => '',            // "Not sure" must stay unknown, not become "No"
                'taraweeh_khatm' => '',
                'facilities' => ['wheelchair_entrance' => '1', 'car_parking' => '0', 'toilets' => ''],
                'khutbah_languages' => ['ur', 'ar'],
                'eid_year' => '2027',
                'eid_fitr_times' => '8:30, 07:00',
                'is_verified' => '1',
            ]))
            ->assertRedirect(route('admin.mosques.edit', $mosque))
            ->assertSessionHas('success', 'Jamia Masjid Al-Noor updated. Times changed, so confirmations were reset.');

        $fresh = $mosque->fresh();
        $this->assertSame('Jamia Masjid Al-Noor', $fresh->name);
        $this->assertSame('05:30', $fresh->fajr_time);
        $this->assertNull($fresh->maghrib_time); // the sunset gap replaces a fixed time
        $this->assertSame(5, $fresh->maghrib_offset_minutes);
        $this->assertSame('14:00', $fresh->jumuah_time_2);
        $this->assertNull($fresh->has_women_area);
        $this->assertNull($fresh->taraweeh_khatm);
        $this->assertSame(['wheelchair_entrance' => true, 'car_parking' => false], $fresh->facilities);
        $this->assertSame(['ar', 'ur'], $fresh->khutbah_languages);
        $this->assertSame(['fitr' => ['year' => 2027, 'times' => ['07:00', '08:30']]], $fresh->eid_times);
        $this->assertTrue($fresh->is_verified);
        $this->assertSame(0, $fresh->confirmations_count);
        $this->assertSame($this->admin->id, $fresh->times_updated_by);

        $log = MosqueContribution::where('type', MosqueContribution::TYPE_DETAILS_UPDATED)->sole();
        $this->assertTrue($log->payload['by_admin']);
        $this->assertSame('Masjid Al-Noor', $log->payload['before']['name']);
        $this->assertArrayNotHasKey('city', $log->payload['after']); // unchanged fields are not logged
    }

    public function test_saving_without_changes_logs_nothing(): void
    {
        $mosque = $this->mosque(['khutbah_languages' => ['ur'], 'facilities' => ['toilets' => false]]);

        $this->actingAs($this->admin)
            ->put(route('admin.mosques.update', $mosque), $this->formFor($mosque))
            ->assertSessionHas('success', 'No changes to save.');

        $this->assertDatabaseCount('mosque_contributions', 0);
        $this->assertSame(['toilets' => false], $mosque->fresh()->facilities);
    }

    public function test_unticking_every_language_clears_them(): void
    {
        $mosque = $this->mosque(['khutbah_languages' => ['ur', 'en']]);

        // The form's hidden empty field is all that arrives when nothing is ticked.
        $this->actingAs($this->admin)
            ->put(route('admin.mosques.update', $mosque), $this->formFor($mosque, ['khutbah_languages' => '']));

        $this->assertNull($mosque->fresh()->khutbah_languages);
    }

    public function test_changing_sect_settles_pending_proposal_but_leaving_it_keeps_it(): void
    {
        $mosque = $this->mosque(['sect' => 'deobandi', 'pending_sect' => 'barelvi', 'pending_sect_at' => now()]);

        // Editing only the name leaves the proposal for review.
        $this->actingAs($this->admin)
            ->put(route('admin.mosques.update', $mosque), $this->formFor($mosque, ['name' => 'Masjid Noor']));
        $this->assertSame('barelvi', $mosque->fresh()->pending_sect);

        // Choosing a sect in the form decides it.
        $this->actingAs($this->admin)
            ->put(route('admin.mosques.update', $mosque), $this->formFor($mosque->fresh(), ['sect' => 'ahl_e_hadith']));
        $this->assertSame('ahl_e_hadith', $mosque->fresh()->sect);
        $this->assertNull($mosque->fresh()->pending_sect);
    }

    public function test_invalid_input_returns_to_form_with_errors(): void
    {
        $mosque = $this->mosque();

        $this->actingAs($this->admin)
            ->from(route('admin.mosques.edit', $mosque))
            ->put(route('admin.mosques.update', $mosque), $this->formFor($mosque, ['isha_time' => '25:70', 'latitude' => '123']))
            ->assertRedirect(route('admin.mosques.edit', $mosque))
            ->assertSessionHasErrors(['isha_time', 'latitude']);

        $this->assertSame('Masjid Al-Noor', $mosque->fresh()->name);
    }

    public function test_return_link_goes_back_to_filtered_list_but_never_elsewhere(): void
    {
        $mosque = $this->mosque();
        $list = route('admin.mosques.index', ['filter' => 'all', 'city' => 'Karachi']);

        $this->actingAs($this->admin)
            ->put(route('admin.mosques.update', $mosque), $this->formFor($mosque, ['name' => 'Masjid A', 'return' => $list]))
            ->assertRedirect($list);

        $this->actingAs($this->admin)
            ->put(route('admin.mosques.update', $mosque), $this->formFor($mosque->fresh(), ['name' => 'Masjid B', 'return' => 'https://evil.example/phish']))
            ->assertRedirect(route('admin.mosques.edit', $mosque));
    }

    public function test_non_admin_cannot_edit(): void
    {
        $mosque = $this->mosque();
        $user = User::factory()->create(['is_admin' => false]);

        $this->assertContains($this->actingAs($user)->get(route('admin.mosques.edit', $mosque))->status(), [302, 403]);
        $this->actingAs($user)->put(route('admin.mosques.update', $mosque), $this->formFor($mosque, ['name' => 'Hijacked']));
        $this->assertSame('Masjid Al-Noor', $mosque->fresh()->name);
    }

    public function test_list_filters_by_city_sect_type_facility_and_times(): void
    {
        $this->mosque(['name' => 'Karachi Deobandi Masjid', 'sect' => 'deobandi', 'facilities' => ['wheelchair_entrance' => true]]);
        $this->mosque(['name' => 'Lahore Musalla', 'city' => 'Lahore', 'place_type' => 'musalla', 'facilities' => ['wheelchair_entrance' => false]]);
        $this->mosque([
            'name' => 'Old Times Masjid', 'fajr_time' => null, 'times_updated_at' => null,
            'asr_method' => 'hanafi', 'has_women_area' => true,
        ]);

        $see = function (array $query, array $expected, array $absent) {
            $response = $this->actingAs($this->admin)->get(route('admin.mosques.index', ['filter' => 'all'] + $query))->assertOk();
            foreach ($expected as $name) {
                $response->assertSee($name);
            }
            foreach ($absent as $name) {
                $response->assertDontSee($name);
            }
        };

        $see(['city' => 'Lahore'], ['Lahore Musalla'], ['Karachi Deobandi Masjid', 'Old Times Masjid']);
        $see(['sect' => 'deobandi'], ['Karachi Deobandi Masjid'], ['Lahore Musalla']);
        $see(['sect' => 'unspecified'], ['Lahore Musalla', 'Old Times Masjid'], ['Karachi Deobandi Masjid']);
        $see(['place_type' => 'musalla'], ['Lahore Musalla'], ['Karachi Deobandi Masjid']);
        $see(['facility' => 'wheelchair_entrance'], ['Karachi Deobandi Masjid'], ['Lahore Musalla', 'Old Times Masjid']);
        $see(['facility' => 'women_area'], ['Old Times Masjid'], ['Lahore Musalla']);
        $see(['times' => 'none'], ['Old Times Masjid'], ['Lahore Musalla']);
        $see(['times' => 'stale'], ['Old Times Masjid'], ['Karachi Deobandi Masjid']);
        $see(['asr' => 'hanafi'], ['Old Times Masjid'], ['Lahore Musalla']);
        $see(['search' => 'Lahore', 'place_type' => 'masjid'], [], ['Lahore Musalla']);
    }

    public function test_list_sorts_and_queue_tabs_keep_other_filters(): void
    {
        $this->mosque(['name' => 'Zubair Masjid']);
        $this->mosque(['name' => 'Ali Masjid', 'reports_count' => 2]);

        $html = $this->actingAs($this->admin)->get(route('admin.mosques.index', ['filter' => 'all', 'sort' => 'name']))->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Zubair Masjid'), strpos($html, 'Ali Masjid'));

        // The "Reported" tab link carries the city filter along.
        $this->actingAs($this->admin)->get(route('admin.mosques.index', ['filter' => 'all', 'city' => 'Karachi']))
            ->assertSee(e(route('admin.mosques.index', ['city' => 'Karachi', 'filter' => 'reported'])), false);
    }
}
