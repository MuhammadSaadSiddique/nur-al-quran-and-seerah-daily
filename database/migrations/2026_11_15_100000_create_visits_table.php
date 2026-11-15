<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website visit analytics. One row per visit (a browsing session that ends
 * after 30 idle minutes or when someone arrives from a new source), holding
 * where the visitor came from, the page they landed on and their approximate
 * location. Raw IP addresses are never stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Long-lived first-party cookie, so returning visitors count once.
            $table->uuid('visitor_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_new_visitor')->default(true);

            $table->string('landing_path', 500);
            $table->string('last_path', 500)->nullable();
            $table->unsignedInteger('pageviews')->default(1);

            $table->string('referrer_url', 500)->nullable();
            $table->string('referrer_host', 191)->nullable()->index();
            // direct, search, social, email, referral, app, campaign
            $table->string('channel', 20)->index();
            $table->string('utm_source', 150)->nullable();
            $table->string('utm_medium', 150)->nullable();
            $table->string('utm_campaign', 150)->nullable();

            $table->char('country_code', 2)->nullable()->index();
            $table->string('region', 120)->nullable();
            $table->string('city', 120)->nullable();

            $table->string('device_type', 10)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('os', 40)->nullable();

            $table->timestamp('started_at')->index();
            $table->timestamp('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
