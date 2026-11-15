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

    public function test_non_admin_cannot_run_bulk_actions(): void
    {
        $mosque = $this->mosque('Masjid Safe');
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->post(route('admin.mosques.bulk'), ['action' => 'delete', 'ids' => [$mosque->id]]);

        $this->assertContains($response->status(), [302, 403]);
        $this->assertDatabaseHas('mosques', ['id' => $mosque->id]);
    }
}
