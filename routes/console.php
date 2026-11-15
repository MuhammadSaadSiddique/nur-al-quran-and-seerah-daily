<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('send:weekly-research-promo')->weekly();

// Delete website visits older than visitors.retention_days.
Schedule::command('model:prune', ['--model' => [\App\Models\Visit::class]])->daily();
