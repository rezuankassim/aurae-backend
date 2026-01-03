<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic cleanup of orphaned uploads
// Runs daily at 2:00 AM to clean up uploads older than 24 hours
Schedule::command('uploads:cleanup --hours=24')
    ->daily()
    ->at('02:00');

// Schedule automatic publishing of news based on published_at timestamp
// Runs every minute to check for news that should be published
Schedule::command('news:publish-scheduled')
    ->everyMinute();
