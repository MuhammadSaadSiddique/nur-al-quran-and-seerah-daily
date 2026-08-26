<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quranic_lens_analyses', function (Blueprint $table) {
            $table->foreignId('theme_id')->nullable()->after('lens_type')->constrained('themes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('quranic_lens_analyses', function (Blueprint $table) {
            $table->dropForeign(['theme_id']);
            $table->dropColumn('theme_id');
        });
    }
};
