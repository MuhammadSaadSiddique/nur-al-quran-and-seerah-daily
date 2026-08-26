<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quranic_lens_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('chapter_number');
            $table->integer('verse_number');
            $table->string('lens_type'); // e.g., 'tafsir', 'hadith', 'seerat', 'science', 'history', 'scripture', 'psychology'
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->foreignId('moderated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('moderated_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('quranic_lens_word_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('chapter_number');
            $table->integer('verse_number');
            $table->integer('word_position'); // 1-indexed position
            $table->string('word_text'); // the Arabic word
            $table->string('tag_type'); // 'grammar', 'root_word', 'thematic', 'custom'
            $table->string('tag_value');
            $table->text('explanation')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->foreignId('moderated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quranic_lens_verse_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('chapter_number');
            $table->integer('verse_number');
            $table->string('tag_type'); // 'theme', 'law', 'theology', 'prophecy', 'custom'
            $table->string('tag_value');
            $table->text('explanation')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->foreignId('moderated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quranic_lens_verse_tags');
        Schema::dropIfExists('quranic_lens_word_tags');
        Schema::dropIfExists('quranic_lens_analyses');
    }
};
