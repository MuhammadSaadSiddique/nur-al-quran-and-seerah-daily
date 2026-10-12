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
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->index();
                $table->string('type', 30)->default('dua')->index(); // 'dua' or 'quran_research'
                $table->string('emoji', 20)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->string('status', 20)->default('approved')->index(); // 'approved', 'pending', 'rejected'
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('submitter_name')->nullable();
                $table->timestamps();

                $table->unique(['type', 'slug'], 'categories_type_slug_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
