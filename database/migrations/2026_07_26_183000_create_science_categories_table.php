<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('science_categories')) {
            Schema::create('science_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('emoji')->nullable();
                $table->text('mapped_fields')->nullable(); // Comma-separated raw database field values
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('science_categories');
    }
};
