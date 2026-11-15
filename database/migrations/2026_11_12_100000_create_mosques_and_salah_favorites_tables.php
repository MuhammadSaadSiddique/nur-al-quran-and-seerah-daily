<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Community-sourced mosques with congregation (jama'ah) times, each user's
 * chosen mosque per salah, and a log of every contribution so the people who
 * add and maintain this data can be credited for their sadaqah jariyah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mosques', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('address', 255)->nullable();
            $table->string('locality', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 80)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Masjid or musalla (prayer room in a market, office, mall…).
            $table->string('place_type', 20)->default('masjid');
            $table->string('sect', 30)->nullable();
            // Sect changes after creation wait for an admin; "none" proposes clearing it.
            $table->string('pending_sect', 30)->nullable();
            $table->foreignId('pending_sect_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('pending_sect_at')->nullable();
            // How the mosque times Asr (hanafi / standard), whatever the worshipper's own setting.
            $table->string('asr_method', 10)->nullable();

            // Jama'ah times as local 24h "HH:MM". Null means not known yet.
            $table->string('fajr_time', 5)->nullable();
            $table->string('dhuhr_time', 5)->nullable();
            $table->string('asr_time', 5)->nullable();
            $table->string('maghrib_time', 5)->nullable();
            $table->string('isha_time', 5)->nullable();
            $table->string('jumuah_time', 5)->nullable();
            $table->string('jumuah_time_2', 5)->nullable();
            // Maghrib usually follows sunset by a fixed gap, so a clock time goes stale within days.
            $table->unsignedSmallInteger('maghrib_offset_minutes')->nullable();

            $table->boolean('has_women_area')->nullable();
            // Whitelisted facility keys => true / false / null (unknown).
            $table->json('facilities')->nullable();
            $table->json('khutbah_languages')->nullable();
            $table->unsignedTinyInteger('taraweeh_rakats')->nullable();
            $table->boolean('taraweeh_khatm')->nullable();
            // {"fitr": {"year": 2027, "times": ["07:00", "08:30"]}, "adha": {...}}
            $table->json('eid_times')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_verified')->default(false);
            $table->unsignedInteger('confirmations_count')->default(0);
            $table->unsignedInteger('reports_count')->default(0);
            $table->timestamp('times_updated_at')->nullable();
            $table->timestamp('times_confirmed_at')->nullable();
            $table->foreignId('times_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('status');
            $table->index('sect');
            $table->index('pending_sect');
        });

        Schema::create('mosque_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->json('payload')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['mosque_id', 'type']);
        });

        Schema::create('salah_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('prayer', 10);
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'prayer']);
            $table->index('mosque_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salah_favorites');
        Schema::dropIfExists('mosque_contributions');
        Schema::dropIfExists('mosques');
    }
};
