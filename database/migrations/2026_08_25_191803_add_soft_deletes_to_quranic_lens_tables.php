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
        Schema::table('quranic_lens_analyses', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('quranic_lens_word_tags', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('quranic_lens_verse_tags', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quranic_lens_analyses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('quranic_lens_word_tags', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('quranic_lens_verse_tags', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
