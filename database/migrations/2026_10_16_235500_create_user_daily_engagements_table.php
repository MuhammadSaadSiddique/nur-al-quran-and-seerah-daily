<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_daily_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('engagement_date');
            $table->json('activities')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'engagement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_daily_engagements');
    }
};
