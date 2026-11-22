<?php

namespace Tests\Feature;

use App\Models\Mosque;
use App\Models\SalahFavorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMosqueBulkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function mosque(string $name, array $overrides = []): Mosque
    {
        return Mosque::create(array_merge([
            'name' => $name,
            'city' => 'Karachi',
            'latitude' => 24.92,
            'longitude' => 67.09,
            'status' => Mosque::STATUS_ACTIVE,
        ], $overrides));
    }

    private function list(array $query)
    {
        return $this->actingAs($this->admin)->get(route('admin.mosques.index', ['filter' => 'all'] + $query))->assertOk();
    }

    public function test_no_address_filter_finds_mosques_with_every_address_field_empty(): void
    {
        $this->mosque('Blank Masjid', ['city' => null]);
        $this->mosque('Empty Strings Masjid', ['city' => '', 'address' => '', 'locality' => '', 'country' => '']);
        $this->mosque('Street Only Masjid', ['city' => null, 'address' => 'Main Boulevard']);
        $this->mosque('Karachi Masjid');

        $this->list(['address' => 'missing'])
            ->assertSee('Blank Masjid')
            ->assertSee('Empty Strings Masjid')
            ->assertDontSee('Street Only Masjid')
            ->assertDontSee('Karachi Masjid');

        $this->list(['address' => 'no_city'])
            ->assertSee('Street Only Masjid')
            ->assertDontSee('Karachi Masjid');

        $this->list(['address' => 'has'])
            ->assertSee('Street Only Masjid')
            ->assertSee('Karachi Masjid')
            ->assertDontSee('Blank Masjid');
    }

    public function test_list_shows_bulk_bar_and_row_checkboxes(): void
    {
        $mosque = $this->mosque('Masjid Bilal');

        $this->list([])
            ->assertSee('id="bulk-form"', false)
            ->assertSee('name="ids[]" value="' . $mosque->id . '" form="bulk-form"', false)
            ->assertSee('Delete permanently');
    }

    public function test_bulk_action_on_ticked_mosques_only(): void
    {
        $a = $this->mosque('Masjid A');
        $b = $this->mosque('Masjid B');
        $c = $this->mosque('Masjid C');

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), ['action' => 'hide', 'ids' => [$a->id, $b->id]])
            ->assertSessionHas('success', '2 mosques hidden from the app.');

        $this->assertSame(Mosque::STATUS_HIDDEN, $a->fresh()->status);
        $this->assertSame(Mosque::STATUS_HIDDEN, $b->fresh()->status);
        $this->assertSame(Mosque::STATUS_ACTIVE, $c->fresh()->status);
    }

    public function test_bulk_action_on_all_matching_uses_the_list_filters(): void
    {
        $this->mosque('Lahore One', ['city' => 'Lahore']);
        $this->mosque('Lahore Two', ['city' => 'Lahore']);
        $karachi = $this->mosque('Karachi One');

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), [
                'action' => 'verify',
                'select_all_matching' => '1',
                'expected_count' => 2,
                'filter' => 'all',
                'city' => 'Lahore',
            ])
            ->assertSessionHas('success', '2 mosques verified.');

        $this->assertSame(2, Mosque::where('city', 'Lahore')->where('is_verified', true)->count());
        $this->assertFalse($karachi->fresh()->is_verified);
    }

    public function test_all_matching_is_refused_when_the_list_changed(): void
    {
        $this->mosque('Blank One', ['city' => null]);
        $this->mosque('Blank Two', ['city' => null]);

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), [
                'action' => 'delete',
                'select_all_matching' => '1',
                'expected_count' => 1, // the admin saw 1; there are now 2
                'filter' => 'all',
                'address' => 'missing',
            ])
            ->assertSessionHasErrors('bulk');

        $this->assertDatabaseCount('mosques', 2);
    }

    public function test_bulk_delete_removes_favourites_too(): void
    {
        $gone = $this->mosque('Duplicate Entry', ['city' => null]);
        $kept = $this->mosque('Real Masjid');
        $user = User::factory()->create();
        SalahFavorite::create(['user_id' => $user->id, 'prayer' => 'fajr', 'mosque_id' => $gone->id]);
        SalahFavorite::create(['user_id' => $user->id, 'prayer' => 'isha', 'mosque_id' => $kept->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), ['action' => 'delete', 'ids' => [$gone->id]])
            ->assertSessionHas('success', '1 mosque deleted along with their favourites and history.');

        $this->assertDatabaseMissing('mosques', ['id' => $gone->id]);
        $this->assertDatabaseMissing('salah_favorites', ['mosque_id' => $gone->id]);
        $this->assertDatabaseHas('salah_favorites', ['mosque_id' => $kept->id]);
    }

    public function test_bulk_sect_approval_only_touches_pending_proposals(): void
    {
        $pending = $this->mosque('Pending Masjid', ['sect' => 'deobandi', 'pending_sect' => 'barelvi', 'pending_sect_at' => now()]);
        $settled = $this->mosque('Settled Masjid', ['sect' => 'bohra']);

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), ['action' => 'approve_sect', 'ids' => [$pending->id, $settled->id]])
            ->assertSessionHas('success', 'School of thought approved for 1 mosque. 1 had no pending proposal.');

        $this->assertSame('barelvi', $pending->fresh()->sect);
        $this->assertNull($pending->fresh()->pending_sect);
        $this->assertSame('bohra', $settled->fresh()->sect);
    }

    public function test_restore_clears_reports_and_empty_selection_is_refused(): void
    {
        $hidden = $this->mosque('Hidden Masjid', ['status' => Mosque::STATUS_HIDDEN, 'reports_count' => 5]);

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), ['action' => 'restore', 'ids' => [$hidden->id]]);
        $this->assertSame(Mosque::STATUS_ACTIVE, $hidden->fresh()->status);
        $this->assertSame(0, $hidden->fresh()->reports_count);

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), ['action' => 'hide', 'ids' => []])
            ->assertSessionHasErrors('bulk');

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), ['action' => 'launch_rockets', 'ids' => [$hidden->id]])
            ->assertSessionHasErrors('action');
    }

    public function test_country_filter_and_empty_city_or_country_options(): void
    {
        $this->mosque('Pakistan Masjid', ['country' => 'Pakistan']);
        $this->mosque('Code Masjid', ['country' => 'PK']);
        $this->mosque('Countryless Masjid', ['country' => null, 'city' => null]);

        $none = \App\Http\Controllers\Admin\MosqueController::NONE;

        $this->list(['country' => 'PK'])->assertSee('Code Masjid')->assertDontSee('Pakistan Masjid');
        $this->list(['country' => $none])->assertSee('Countryless Masjid')->assertDontSee('Code Masjid');
        $this->list(['city' => $none])->assertSee('Countryless Masjid')->assertDontSee('Pakistan Masjid');

        // Dropdowns show how many use each spelling, so variants are easy to spot.
        $this->list([])
            ->assertSee('Karachi (2)')
            ->assertSee('No city (1)')
            ->assertSee('PK (1)')
            ->assertSee('No country (1)');
    }

    public function test_bulk_set_city_on_ticked_mosques_logs_each_change(): void
    {
        $a = $this->mosque('Masjid A', ['city' => 'لاہور']);
        $b = $this->mosque('Masjid B', ['city' => null]);
        $c = $this->mosque('Masjid C', ['city' => 'Lahore']);

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), ['action' => 'set_city', 'bulk_value' => '  Lahore ', 'ids' => [$a->id, $b->id, $c->id]])
            ->assertSessionHas('success', 'City set to “Lahore” on 2 mosques. 1 already had it.');

        $this->assertSame(['Lahore', 'Lahore', 'Lahore'], [$a->fresh()->city, $b->fresh()->city, $c->fresh()->city]);

        $log = \App\Models\MosqueContribution::where('mosque_id', $a->id)->where('note', 'Bulk edit in admin panel')->sole();
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame(['city' => 'لاہور'], $log->payload['before']);
        $this->assertTrue($log->payload['by_admin']);
        $this->assertDatabaseMissing('mosque_contributions', ['mosque_id' => $c->id, 'note' => 'Bulk edit in admin panel']);
    }

    public function test_rename_a_spelling_everywhere_with_select_all_matching(): void
    {
        $this->mosque('Urdu One', ['city' => 'اسلام آباد']);
        $this->mosque('Urdu Two', ['city' => 'اسلام آباد']);
        $other = $this->mosque('Karachi One');

        // The list's own "city" filter travels with the form; the new name is bulk_value.
        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), [
                'action' => 'set_city',
                'bulk_value' => 'Islamabad',
                'select_all_matching' => '1',
                'expected_count' => 2,
                'filter' => 'all',
                'city' => 'اسلام آباد',
            ])
            ->assertSessionHas('success', 'City set to “Islamabad” on 2 mosques.');

        $this->assertSame(0, Mosque::where('city', 'اسلام آباد')->count());
        $this->assertSame(2, Mosque::where('city', 'Islamabad')->count());
        $this->assertSame('Karachi', $other->fresh()->city);
    }

    public function test_bulk_set_country_for_mosques_without_one(): void
    {
        $this->mosque('No Country A');
        $this->mosque('No Country B', ['country' => '']);
        $this->mosque('Has Country', ['country' => 'PK']);

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), [
                'action' => 'set_country',
                'bulk_value' => 'Pakistan',
                'select_all_matching' => '1',
                'expected_count' => 2,
                'filter' => 'all',
                'country' => \App\Http\Controllers\Admin\MosqueController::NONE,
            ])
            ->assertSessionHas('success', 'Country set to “Pakistan” on 2 mosques.');

        $this->assertSame(2, Mosque::where('country', 'Pakistan')->count());
        $this->assertSame('PK', Mosque::where('name', 'Has Country')->value('country'));
    }

    public function test_set_city_needs_a_value(): void
    {
        $mosque = $this->mosque('Masjid Value', ['city' => 'Karachi']);

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), ['action' => 'set_city', 'bulk_value' => '', 'ids' => [$mosque->id]])
            ->assertSessionHasErrors('bulk_value');
        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), ['action' => 'set_country', 'bulk_value' => str_repeat('x', 81), 'ids' => [$mosque->id]])
            ->assertSessionHasErrors('bulk_value');

        $this->assertSame('Karachi', $mosque->fresh()->city);
    }

    public function test_renamed_city_survives_an_openstreetmap_reimport(): void
    {
        $element = ['type' => 'node', 'id' => 4242, 'lat' => 31.52, 'lon' => 74.35, 'tags' => [
            'amenity' => 'place_of_worship', 'religion' => 'muslim', 'name' => 'Badshahi Masjid', 'addr:city' => 'لاہور',
        ]];
        $importer = app(\App\Services\OsmMosqueImporter::class);
        $importer->import([$element]);
        $mosque = Mosque::where('osm_id', 4242)->sole();

        $this->actingAs($this->admin)
            ->post(route('admin.mosques.bulk'), ['action' => 'set_city', 'bulk_value' => 'Lahore', 'ids' => [$mosque->id]]);

        // OSM still says لاہور; the admin's rename must win.
        $report = $importer->import([$element]);

        $this->assertSame('Lahore', $mosque->fresh()->city);
        $this->assertSame(0, $report['refreshed']);
    }

    public function test_non_admin_cannot_run_bulk_actions(): void
    {
        $mosque = $this->mosque('Masjid Safe');
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->post(route('admin.mosques.bulk'), ['action' => 'delete', 'ids' => [$mosque->id]]);

        $this->assertContains($response->status(), [302, 403]);
        $this->assertDatabaseHas('mosques', ['id' => $mosque->id]);
    }
}
