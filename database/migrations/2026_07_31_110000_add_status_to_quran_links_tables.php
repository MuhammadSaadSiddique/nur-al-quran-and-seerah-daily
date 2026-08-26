<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'quran_science_links',
            'quran_seerat_links',
            'quran_hadith_links',
            'quran_history_links',
            'quran_scripture_links'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'status')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('status')->default('pending'); // pending, approved, rejected
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'quran_science_links',
            'quran_seerat_links',
            'quran_hadith_links',
            'quran_history_links',
            'quran_scripture_links'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'status')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('status');
                });
            }
        }
    }
};
