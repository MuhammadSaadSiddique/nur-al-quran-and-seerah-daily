<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('translations')) {
            Schema::create('translations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('verse_id')->nullable()->index();
                $table->string('language')->nullable();
                $table->string('translator')->nullable();
                $table->text('text')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('quran_pattern_cards')) {
            Schema::create('quran_pattern_cards', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('verse_id')->index();
                $table->string('focus_token', 80)->default('');
                $table->text('gloss')->nullable();
                $table->string('arabic_term')->nullable();
                $table->text('ring_note')->nullable();
                $table->text('linguistic_note')->nullable();
                $table->json('topics')->nullable();
                $table->json('math')->nullable();
                $table->timestamps();

                $table->unique(['verse_id', 'focus_token']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_pattern_cards');
    }
};
