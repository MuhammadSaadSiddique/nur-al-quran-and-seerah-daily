<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('researcher_onboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->text('credentials');
            $table->string('expertise');
            $table->text('background');
            $table->string('languages')->nullable();
            $table->text('sources');
            $table->foreignId('requested_expert_category_id')->nullable()->constrained('science_categories')->nullOnDelete();
            $table->text('pledge_statement');
            $table->timestamp('pledged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('researcher_onboardings');
    }
};
