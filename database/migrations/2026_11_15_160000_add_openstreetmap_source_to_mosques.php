<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Track where each mosque came from, so OpenStreetMap imports can be
 * attributed (ODbL), refreshed on re-import, and never re-created after an
 * admin deletes them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            // community = added in the app; osm = created by an OpenStreetMap import.
            $table->string('source', 20)->default('community')->after('id');
            // A community mosque can also be linked to its OSM feature.
            $table->string('osm_type', 10)->nullable()->after('source');
            $table->unsignedBigInteger('osm_id')->nullable()->after('osm_type');
            $table->timestamp('osm_synced_at')->nullable()->after('osm_id');

            $table->index('source');
            $table->index(['osm_type', 'osm_id']);
            $table->index('osm_id');
        });

        // OSM features an admin deleted. Imports skip them instead of bringing them back.
        Schema::create('mosque_import_exclusions', function (Blueprint $table) {
            $table->id();
            $table->string('osm_type', 10)->nullable();
            $table->unsignedBigInteger('osm_id');
            $table->string('mosque_name', 150)->nullable();
            $table->foreignId('excluded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['osm_type', 'osm_id']);
            $table->index('osm_id');
        });

        $this->backfill();
    }

    /**
     * Rows created by the first version of mosques:import-osm only recorded
     * the numeric OSM id in their "added" contribution (not node/way), so the
     * type stays null until the next import matches them again.
     */
    public function backfill(): void
    {
        DB::table('mosque_contributions')
            ->where('type', 'added')
            ->whereNull('user_id')
            ->where('note', 'Imported from OpenStreetMap')
            ->orderBy('id')
            ->select('id', 'mosque_id', 'payload')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $payload = json_decode((string) $row->payload, true);
                    $osmId = is_array($payload) && is_numeric($payload['osm_id'] ?? null) ? (int) $payload['osm_id'] : null;

                    DB::table('mosques')
                        ->where('id', $row->mosque_id)
                        ->whereNull('osm_id')
                        ->update([
                            'source' => 'osm',
                            'osm_id' => $osmId,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('mosque_import_exclusions');

        Schema::table('mosques', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropIndex(['osm_type', 'osm_id']);
            $table->dropIndex(['osm_id']);
            $table->dropColumn(['source', 'osm_type', 'osm_id', 'osm_synced_at']);
        });
    }
};
