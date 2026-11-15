<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->string('survey_key', 50)->index();
            // Deleting an account erases that person's answers and contact details with it.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->unsignedTinyInteger('nps')->nullable();
            $table->text('wish')->nullable();
            $table->string('name', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('notify')->default(false);
            $table->string('source', 30)->default('web')->index();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['survey_key', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
