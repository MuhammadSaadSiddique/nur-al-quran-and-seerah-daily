<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholars', function (Blueprint $table) {
            $table->id();
            // One self-submitted profile per account. Admin-added scholars leave this empty, so there is no limit.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('title')->nullable();
            $table->string('slug')->unique();
            $table->text('credentials');
            $table->string('expertise');
            $table->text('background');
            $table->string('languages')->nullable();
            $table->text('sources');
            $table->text('works')->nullable();
            $table->string('location')->nullable();
            $table->text('pledge_statement');
            $table->timestamp('pledged_at');
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholars');
    }
};
