<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('surahs')) {
            Schema::create('surahs', function (Blueprint $table) {
                $table->id();
                $table->integer('number')->nullable();
                $table->string('name_arabic')->nullable();
                $table->string('name_simple')->nullable();
                $table->string('name_complex')->nullable();
                $table->string('name_translated')->nullable();
                $table->string('name_transliteration')->nullable();
                $table->string('revelation_place')->nullable();
                $table->integer('verses_count')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('verses')) {
            Schema::create('verses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surah_id')->nullable();
                $table->integer('verse_number')->nullable();
                $table->string('verse_key')->nullable();
                $table->integer('juz_number')->nullable();
                $table->text('text_arabic')->nullable();
                $table->text('text_transliteration')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('science_facts')) {
            Schema::create('science_facts', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('field')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('quran_science_links')) {
            Schema::create('quran_science_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('verse_id')->nullable();
                $table->foreignId('science_fact_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('seerat_events')) {
            Schema::create('seerat_events', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('quran_seerat_links')) {
            Schema::create('quran_seerat_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('verse_id')->nullable();
                $table->foreignId('seerat_event_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hadith_collections')) {
            Schema::create('hadith_collections', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ahadith')) {
            Schema::create('ahadith', function (Blueprint $table) {
                $table->id();
                $table->string('hadith_number')->nullable();
                $table->text('hadith_text')->nullable();
                $table->text('hadith_translation')->nullable();
                $table->foreignId('collection_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('quran_hadith_links')) {
            Schema::create('quran_hadith_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('verse_id')->nullable();
                $table->foreignId('hadith_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('history_contexts')) {
            Schema::create('history_contexts', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('historical_period')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('quran_history_links')) {
            Schema::create('quran_history_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('verse_id')->nullable();
                $table->foreignId('history_context_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bible_verses')) {
            Schema::create('bible_verses', function (Blueprint $table) {
                $table->id();
                $table->string('verse_reference')->nullable();
                $table->text('text')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('torah_sections')) {
            Schema::create('torah_sections', function (Blueprint $table) {
                $table->id();
                $table->string('section_reference')->nullable();
                $table->text('text')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('quran_scripture_links')) {
            Schema::create('quran_scripture_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('verse_id')->nullable();
                $table->foreignId('bible_verse_id')->nullable();
                $table->foreignId('torah_section_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_scripture_links');
        Schema::dropIfExists('torah_sections');
        Schema::dropIfExists('bible_verses');
        Schema::dropIfExists('quran_history_links');
        Schema::dropIfExists('history_contexts');
        Schema::dropIfExists('quran_hadith_links');
        Schema::dropIfExists('ahadith');
        Schema::dropIfExists('hadith_collections');
        Schema::dropIfExists('quran_seerat_links');
        Schema::dropIfExists('seerat_events');
        Schema::dropIfExists('quran_science_links');
        Schema::dropIfExists('science_facts');
        Schema::dropIfExists('verses');
        Schema::dropIfExists('surahs');
    }
};
