<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('generated_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question_id')->unique();
            $table->string('type');           // PARA, SEERAH, SEERAH_INSIGHT, QURAN_HISTORY
            $table->string('source_info');    // e.g. "Para 3", "Seerah", "Quranic History"
            $table->string('difficulty');
            $table->string('theme')->nullable();
            $table->text('text');
            $table->json('options');
            $table->integer('correct_answer_index');
            $table->text('explanation')->nullable();
            $table->integer('times_answered')->default(0);
            $table->integer('times_correct')->default(0);
            $table->timestamps();

            $table->index('type');
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_questions');
    }
};
