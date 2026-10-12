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
        Schema::create('quran_research_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('submitter_name')->nullable();
            $table->string('submitter_email')->nullable();
            $table->integer('surah_number');
            $table->integer('verse_number')->default(1);
            $table->string('category', 50)->default('video'); // 'video', 'science', 'seerah', 'hadith', 'history', etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('video_url')->nullable(); // Youtube / Vimeo / MP4 / etc.
            $table->string('video_platform', 50)->nullable(); // youtube, vimeo, direct, etc.
            $table->string('video_id', 100)->nullable(); // extracted platform video id
            $table->string('status', 30)->default('pending')->index(); // 'pending', 'published', 'rejected'
            $table->integer('approvals_count')->default(0)->index();
            $table->integer('downvotes_count')->default(0)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quran_research_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contribution_id')->constrained('quran_research_contributions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('vote', ['approve', 'downvote']); // 1 approval vote or 1 downvote
            $table->text('feedback')->nullable(); // user feedback / verification remarks
            $table->timestamps();

            $table->unique(['contribution_id', 'user_id'], 'contrib_user_unique_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_research_reviews');
        Schema::dropIfExists('quran_research_contributions');
    }
};
