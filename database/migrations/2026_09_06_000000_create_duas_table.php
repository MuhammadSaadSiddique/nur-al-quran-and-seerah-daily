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
        Schema::create('duas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->text('arabic_text');
            $table->text('transliteration')->nullable();
            $table->text('translation_en');
            $table->text('translation_ur')->nullable();
            $table->json('word_by_word')->nullable();
            $table->text('meaning_explanation')->nullable();
            $table->text('benefits_and_virtues')->nullable();
            $table->string('when_to_recite')->nullable();
            $table->unsignedSmallInteger('repeat_count')->default(1);
            $table->enum('source_type', ['quran', 'hadith', 'both'])->default('hadith')->index();
            
            // Quran reference fields
            $table->string('quran_reference')->nullable();
            $table->unsignedSmallInteger('surah_number')->nullable()->index();
            $table->unsignedSmallInteger('verse_number')->nullable();
            $table->unsignedBigInteger('verse_id')->nullable()->index();
            
            // Hadith reference fields
            $table->string('hadith_reference')->nullable();
            $table->string('hadith_book')->nullable();
            $table->string('hadith_number')->nullable();
            $table->string('hadith_grading')->nullable();
            $table->unsignedBigInteger('hadith_id')->nullable()->index();
            
            $table->string('audio_url')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duas');
    }
};
