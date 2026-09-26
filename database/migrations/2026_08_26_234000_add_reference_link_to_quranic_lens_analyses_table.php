<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quranic_lens_analyses') && !Schema::hasColumn('quranic_lens_analyses', 'reference_link')) {
            Schema::table('quranic_lens_analyses', function (Blueprint $table) {
                $table->string('reference_link', 2048)->nullable()->after('content');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quranic_lens_analyses') && Schema::hasColumn('quranic_lens_analyses', 'reference_link')) {
            Schema::table('quranic_lens_analyses', function (Blueprint $table) {
                $table->dropColumn('reference_link');
            });
        }
    }
};
