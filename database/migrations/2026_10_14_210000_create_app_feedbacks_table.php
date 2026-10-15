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
        Schema::create('app_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating')->default(5); // 1 - 5 stars
            $table->string('category')->default('app_experience'); // app_experience, content, feature_request, bug, general
            $table->text('message')->nullable();
            $table->string('platform')->default('web'); // web, android, ios
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('device_info')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'resolved'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_feedbacks');
    }
};
