<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quran_research_contributions', function (Blueprint $table) {
            $table->integer('end_surah_number')->nullable()->after('surah_number');
            $table->json('surah_numbers')->nullable()->after('end_surah_number');
            $table->integer('end_verse_number')->nullable()->after('verse_number');
            $table->boolean('is_full_surah')->default(false)->after('end_verse_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_research_contributions', function (Blueprint $table) {
            $table->dropColumn([
                'end_surah_number',
                'surah_numbers',
                'end_verse_number',
                'is_full_surah',
            ]);
        });
    }
};
