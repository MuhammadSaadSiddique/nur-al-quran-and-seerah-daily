<?php

namespace Tests\Feature;

use App\Models\Mosque;
use App\Models\MosqueContribution;
use App\Models\User;
use App\Services\MosqueService;
use App\Services\OsmMosqueImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OsmMosqueImportTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = 24.9204;
    private const LNG = 67.0932;

    /** A realistic Overpass answer: nodes, a way with a centre, a relation, and the usual noise. */
    private function elements(): array
    {
        return [
            ['type' => 'node', 'id' => 101, 'lat' => self::LAT, 'lon' => self::LNG, 'tags' => [
                'amenity' => 'place_of_worship', 'religion' => 'muslim', 'name' => 'Masjid Tooba',
                'addr:city' => 'Karachi', 'addr:street' => 'Main Boulevard', 'addr:housenumber' => '12',
                'denomination' => 'shia', 'wheelchair' => 'limited', 'toilets' => 'yes',
            ]],
            // The same node again (two selectors matched it).
            ['type' => 'node', 'id' => 101, 'lat' => self::LAT, 'lon' => self::LNG, 'tags' => ['amenity' => 'place_of_worship', 'religion' => 'muslim', 'name' => 'Masjid Tooba']],
            // The building outline of the same mosque, ~30 m away.
            ['type' => 'way', 'id' => 505, 'center' => ['lat' => self::LAT + 0.00027, 'lon' => self::LNG], 'tags' => ['building' => 'mosque', 'name' => 'Masjid Tooba']],
            ['type' => 'way', 'id' => 202, 'center' => ['lat' => self::LAT + 0.01, 'lon' => self::LNG], 'tags' => [
                'amenity' => 'place_of_worship', 'religion' => 'muslim', 'building' => 'mosque',
                'name' => 'جامع مسجد بلال', 'name:en' => 'Jamia Masjid Bilal', 'wheelchair' => 'yes', 'denomination' => 'deobandi',
            ]],
            ['type' => 'relation', 'id' => 404, 'center' => ['lat' => self::LAT + 0.02, 'lon' => self::LNG], 'tags' => [
                'amenity' => 'place_of_worship', 'religion' => 'muslim', 'place_of_worship' => 'musalla', 'name' => 'Office Musalla',
            ]],
            ['type' => 'node', 'id' => 303, 'lat' => self::LAT + 0.03, 'lon' => self::LNG, 'tags' => ['amenity' => 'place_of_worship', 'religion' => 'muslim']],
            ['type' => 'way', 'id' => 909, 'tags' => ['amenity' => 'place_of_worship', 'religion' => 'muslim', 'name' => 'No position']],
        ];
    }

    private function fakeOsm(?array $elements = null): void
    {
        Http::fake([
            'overpass-api.de/*' => Http::response(['elements' => $elements ?? $this->elements()]),
            'nominatim.openstreetmap.org/*' => Http::response([
                ['type' => 'city', 'display_name' => 'Karachi, Sindh, Pakistan', 'boundingbox' => ['24.75', '25.05', '66.90', '67.25']],
            ]),
        ]);
    }

    private function importer(): OsmMosqueImporter
    {
        return app(OsmMosqueImporter::class);
    }

    public function test_tags_are_mapped_conservatively(): void
    {
        $tooba = $this->importer()->parse($this->elements()[0]);
        $this->assertSame('Masjid Tooba', $tooba['attributes']['name']);
        $this->assertSame('12 Main Boulevard', $tooba['attributes']['address']);
        $this->assertSame('shia_other', $tooba['attributes']['sect']); // generic "shia" is not assumed Ithna Ashari
        $this->assertArrayNotHasKey('wheelchair_entrance', $tooba['facilities']); // "limited" is not step-free
        $this->assertTrue($tooba['facilities']['toilets']);

        $bilal = $this->importer()->parse($this->elements()[3]);
        $this->assertSame('Jamia Masjid Bilal', $bilal['attributes']['name']); // English name for an English app
        $this->assertSame(['way', 202], [$bilal['osm_type'], $bilal['osm_id']]);
        $this->assertEqualsWithDelta(self::LAT + 0.01, $bilal['attributes']['latitude'], 1e-7);

        $this->assertSame('musalla', $this->importer()->parse($this->elements()[4])['attributes']['place_type']);
        $this->assertFalse($this->importer()->parse($this->elements()[5])['named']);
        $this->assertNull($this->importer()->parse($this->elements()[6]));
    }

    public function test_import_adds_named_mosques_and_skips_noise(): void
    {
        $report = $this->importer()->import($this->elements());

        $this->assertSame(3, $report['created']);
        $this->assertSame(1, $report['skipped_unnamed']);
        $this->assertSame(1, $report['skipped_duplicate']); // the building outline of Masjid Tooba
        $this->assertSame(1, $report['skipped_invalid']);
        $this->assertDatabaseCount('mosques', 3);

        $tooba = Mosque::where('osm_id', 101)->sole();
        $this->assertSame(Mosque::SOURCE_OSM, $tooba->source);
        $this->assertSame('node', $tooba->osm_type);
        $this->assertNull($tooba->created_by);
        $this->assertSame('https://www.openstreetmap.org/node/101', $tooba->osmUrl());
        $this->assertDatabaseHas('mosque_contributions', ['mosque_id' => $tooba->id, 'type' => MosqueContribution::TYPE_ADDED, 'user_id' => null]);

        // The app API carries the ODbL credit.
        $presented = app(MosqueService::class)->nearby(self::LAT, self::LNG, 5)[0];
        $this->assertSame('osm', $presented['source']);
        $this->assertSame(Mosque::OSM_ATTRIBUTION, $presented['attribution']);
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $report = $this->importer()->import($this->elements(), dryRun: true);

        $this->assertTrue($report['dry_run']);
        $this->assertSame(3, $report['created']);
        $this->assertDatabaseCount('mosques', 0);
        $this->assertDatabaseCount('mosque_contributions', 0);
    }

    public function test_preview_issues_no_writes_at_all(): void
    {
        // MyISAM ignores transactions, so a preview must never write and roll back.
        $this->importer()->import($this->elements());
        $changed = $this->elements();
        $changed[0]['tags']['name'] = 'Masjid-e-Tooba';
        $changed[] = ['type' => 'node', 'id' => 888, 'lat' => self::LAT + 0.04, 'lon' => self::LNG, 'tags' => ['amenity' => 'place_of_worship', 'religion' => 'muslim', 'name' => 'Masjid Hamza']];

        DB::enableQueryLog();
        $report = $this->importer()->import($changed, dryRun: true);
        $writes = collect(DB::getQueryLog())->filter(fn ($q) => preg_match('/^\s*(insert|update|delete|replace)\b/i', $q['query']));
        DB::disableQueryLog();

        $this->assertSame([], $writes->pluck('query')->all());
        $this->assertSame(1, $report['created']);
        $this->assertSame(1, $report['refreshed']);
        $this->assertSame('Masjid Tooba', Mosque::where('osm_id', 101)->sole()->name);
    }

    public function test_deleting_cleans_up_even_without_foreign_keys(): void
    {
        // Simulate MyISAM, which has no cascades.
        DB::statement('PRAGMA foreign_keys = OFF');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->importer()->import($this->elements());
        $tooba = Mosque::where('osm_id', 101)->sole();
        $bilal = Mosque::where('osm_id', 202)->sole();
        foreach ([$tooba, $bilal] as $mosque) {
            \App\Models\SalahFavorite::create(['user_id' => $admin->id, 'prayer' => $mosque->is($tooba) ? 'fajr' : 'isha', 'mosque_id' => $mosque->id]);
        }

        $this->actingAs($admin)->delete(route('admin.mosques.destroy', $tooba));
        $this->actingAs($admin)->post(route('admin.mosques.bulk'), ['action' => 'delete', 'ids' => [$bilal->id]]);
        DB::statement('PRAGMA foreign_keys = ON');

        foreach ([$tooba, $bilal] as $mosque) {
            $this->assertDatabaseMissing('mosques', ['id' => $mosque->id]);
            $this->assertDatabaseMissing('salah_favorites', ['mosque_id' => $mosque->id]);
            $this->assertDatabaseMissing('mosque_contributions', ['mosque_id' => $mosque->id]);
        }
    }

    public function test_reimport_is_idempotent(): void
    {
        $this->importer()->import($this->elements());
        $second = $this->importer()->import($this->elements());

        $this->assertSame(0, $second['created']);
        $this->assertSame(3, $second['unchanged']);
        $this->assertDatabaseCount('mosques', 3);
    }

    public function test_untouched_imports_refresh_but_community_edits_are_kept(): void
    {
        $this->importer()->import($this->elements());
        $tooba = Mosque::where('osm_id', 101)->sole();
        $bilal = Mosque::where('osm_id', 202)->sole();

        // A worshipper fixes Bilal's name in the app.
        $bilal->update(['name' => 'Jamia Masjid Bilal (Block 13)']);
        MosqueContribution::create(['mosque_id' => $bilal->id, 'user_id' => User::factory()->create()->id, 'type' => MosqueContribution::TYPE_DETAILS_UPDATED]);

        $changed = $this->elements();
        $changed[0]['tags']['name'] = 'Masjid-e-Tooba';
        $changed[3]['tags']['name:en'] = 'Bilal Mosque';
        $changed[3]['tags']['addr:city'] = 'Karachi';

        $report = $this->importer()->import($changed);

        $this->assertSame(1, $report['refreshed']);
        $this->assertSame(1, $report['filled']);
        $this->assertSame('Masjid-e-Tooba', $tooba->fresh()->name);
        $this->assertSame('Jamia Masjid Bilal (Block 13)', $bilal->fresh()->name); // their edit wins
        $this->assertSame('Karachi', $bilal->fresh()->city); // but the empty city is filled
    }

    public function test_mosque_added_in_the_app_is_linked_not_duplicated(): void
    {
        $community = Mosque::create([
            'name' => 'Masjid Bilal', 'latitude' => self::LAT + 0.01, 'longitude' => self::LNG + 0.0002,
            'status' => Mosque::STATUS_ACTIVE, 'fajr_time' => '05:40',
        ]);

        $report = $this->importer()->import($this->elements());

        $this->assertSame(1, $report['linked']);
        $community->refresh();
        $this->assertSame(Mosque::SOURCE_COMMUNITY, $community->source);
        $this->assertSame(['way', 202], [$community->osm_type, $community->osm_id]);
        $this->assertSame('Masjid Bilal', $community->name);
        $this->assertTrue($community->facilityMap()['wheelchair_entrance']); // gap filled from OSM
        $this->assertTrue($community->usesOsmData());
    }

    public function test_deleted_imports_are_not_brought_back(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->importer()->import($this->elements());
        $tooba = Mosque::where('osm_id', 101)->sole();
        $musalla = Mosque::where('osm_id', 404)->sole();

        $this->actingAs($admin)->delete(route('admin.mosques.destroy', $tooba));
        $this->actingAs($admin)->post(route('admin.mosques.bulk'), ['action' => 'delete', 'ids' => [$musalla->id]]);
        $this->assertDatabaseCount('mosque_import_exclusions', 2);

        $report = $this->importer()->import($this->elements());

        $this->assertSame(2, $report['skipped_deleted']);
        $this->assertDatabaseMissing('mosques', ['osm_id' => 101]);
        $this->assertDatabaseMissing('mosques', ['osm_id' => 404]);
    }

    public function test_rows_from_the_first_importer_are_backfilled_and_matched(): void
    {
        // What the first version of the command left behind: no source columns, osm id only in the log.
        $legacyId = DB::table('mosques')->insertGetId([
            'name' => 'Masjid Tooba', 'latitude' => self::LAT, 'longitude' => self::LNG,
            'place_type' => 'masjid', 'sect' => 'shia_ithna_ashari', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('mosque_contributions')->insert([
            'mosque_id' => $legacyId, 'user_id' => null, 'type' => 'added',
            'payload' => json_encode(['source' => 'openstreetmap', 'osm_id' => 101]),
            'note' => 'Imported from OpenStreetMap', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_11_15_160000_add_openstreetmap_source_to_mosques.php');
        $migration->backfill();

        $legacy = Mosque::find($legacyId);
        $this->assertSame(Mosque::SOURCE_OSM, $legacy->source);
        $this->assertSame(101, $legacy->osm_id);
        $this->assertNull($legacy->osm_type);

        $report = $this->importer()->import($this->elements());

        $this->assertSame(1, $report['refreshed']);
        $this->assertSame(2, $report['created']); // Bilal and the musalla; Tooba matched the legacy row
        $legacy->refresh();
        $this->assertSame('node', $legacy->osm_type);
        $this->assertSame('shia_other', $legacy->sect); // the old mapping's guess is corrected
    }

    public function test_refresh_never_adds_and_ignores_unrelated_features_sharing_an_id(): void
    {
        Mosque::create([
            'name' => 'Masjid Tooba', 'latitude' => self::LAT, 'longitude' => self::LNG,
            'status' => Mosque::STATUS_ACTIVE, 'source' => Mosque::SOURCE_OSM, 'osm_id' => 101, // type unknown (legacy)
        ]);

        // Asking for "101" as a node and as a way returns the mosque and a cave preserve elsewhere.
        $this->fakeOsm([
            ['type' => 'node', 'id' => 101, 'lat' => self::LAT, 'lon' => self::LNG, 'tags' => ['amenity' => 'place_of_worship', 'religion' => 'muslim', 'name' => 'Masjid Tooba']],
            ['type' => 'way', 'id' => 101, 'center' => ['lat' => 37.1, 'lon' => -91.5], 'tags' => ['leisure' => 'nature_reserve', 'name' => 'Twin Culvert Cave Nature Preserve']],
            // A real mosque we don't have yet: a refresh must not add it either.
            ['type' => 'node', 'id' => 777, 'lat' => self::LAT + 0.05, 'lon' => self::LNG, 'tags' => ['amenity' => 'place_of_worship', 'religion' => 'muslim', 'name' => 'Masjid New']],
        ]);

        $this->artisan('mosques:import-osm', ['--refresh' => true])
            ->expectsOutputToContain('Linked mosques in the directory: 1.')
            ->assertSuccessful();

        $this->assertDatabaseCount('mosques', 1);
        $this->assertSame('node', Mosque::sole()->osm_type);
        $this->assertDatabaseMissing('mosques', ['name' => 'Twin Culvert Cave Nature Preserve']);

        // An area import also ignores anything not tagged as a mosque.
        $report = $this->importer()->import([
            ['type' => 'way', 'id' => 5, 'center' => ['lat' => 1, 'lon' => 1], 'tags' => ['leisure' => 'park', 'name' => 'City Park']],
        ]);
        $this->assertSame(1, $report['skipped_not_mosque']);
        $this->assertSame(0, $report['created']);
    }

    public function test_command_imports_around_a_point_and_previews(): void
    {
        $this->fakeOsm();

        $this->artisan('mosques:import-osm', ['--lat' => self::LAT, '--lng' => self::LNG, '--radius' => 5, '--dry-run' => true])
            ->expectsOutputToContain('OpenStreetMap returned 7 places.')
            ->expectsOutputToContain('Dry run: nothing was written.')
            ->assertSuccessful();
        $this->assertDatabaseCount('mosques', 0);

        $this->artisan('mosques:import-osm', ['--lat' => self::LAT, '--lng' => self::LNG])
            ->expectsOutputToContain('© OpenStreetMap contributors')
            ->assertSuccessful();
        $this->assertDatabaseCount('mosques', 3);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'overpass-api.de')
                && $request->hasHeader('User-Agent', config('services.osm.user_agent'))
                && str_contains($request['data'], '"religion"="muslim"')
                && str_contains($request['data'], 'around:5000');
        });
    }

    public function test_command_resolves_a_city_and_requires_an_area(): void
    {
        $this->fakeOsm();

        $this->artisan('mosques:import-osm', ['--city' => 'Karachi', '--country' => 'Pakistan'])
            ->expectsOutputToContain('Found Karachi, Sindh, Pakistan')
            ->assertSuccessful();
        $this->assertDatabaseCount('mosques', 3);

        $this->artisan('mosques:import-osm')->assertFailed();
        $this->artisan('mosques:import-osm', ['--bbox' => '1,2,3'])->assertFailed();
    }

    public function test_admin_panel_previews_then_imports(): void
    {
        $this->fakeOsm();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.mosques.import-osm'), ['mode' => 'place', 'place' => 'Karachi', 'apply' => '0'])
            ->assertSessionHas('osm_report', fn ($r) => $r['dry_run'] === true && $r['created'] === 3 && $r['area'] === 'Karachi, Sindh, Pakistan');
        $this->assertDatabaseCount('mosques', 0);

        $this->actingAs($admin)
            ->post(route('admin.mosques.import-osm'), ['mode' => 'point', 'latitude' => self::LAT, 'longitude' => self::LNG, 'radius_km' => 3, 'apply' => '1']);
        $this->assertDatabaseCount('mosques', 3);

        $this->actingAs($admin)->get(route('admin.mosques.index', ['filter' => 'all', 'source' => 'osm']))
            ->assertOk()
            ->assertSee('Masjid Tooba')
            ->assertSee('OSM ↗')
            ->assertSee('Imported from OpenStreetMap');
    }

    public function test_admin_panel_refuses_huge_areas_and_reports_server_errors(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['type' => 'administrative', 'display_name' => 'Pakistan', 'boundingbox' => ['23.6', '37.1', '60.8', '77.8']],
            ]),
        ]);
        $this->actingAs($admin)
            ->post(route('admin.mosques.import-osm'), ['mode' => 'place', 'place' => 'Pakistan', 'apply' => '1'])
            ->assertSessionHasErrors('osm_import');

        Http::fake(['overpass-api.de/*' => Http::response('Bad request', 400)]);
        $this->actingAs($admin)
            ->post(route('admin.mosques.import-osm'), ['mode' => 'point', 'latitude' => self::LAT, 'longitude' => self::LNG])
            ->assertSessionHasErrors(['osm_import' => 'Overpass returned HTTP 400.']);
        $this->assertDatabaseCount('mosques', 0);
    }

    public function test_non_admin_cannot_import(): void
    {
        $this->fakeOsm();
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->post(route('admin.mosques.import-osm'), ['mode' => 'point', 'latitude' => self::LAT, 'longitude' => self::LNG, 'apply' => '1']);

        $this->assertContains($response->status(), [302, 403]);
        $this->assertDatabaseCount('mosques', 0);
        Http::assertNothingSent();
    }
}
