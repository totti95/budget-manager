<?php

use App\Models\Notification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('notifications:cleanup', function () {
    $thirtyDaysAgo = now()->subDays(30);

    $deleted = Notification::where('read', true)
        ->where('read_at', '<', $thirtyDaysAgo)
        ->delete();

    $this->info("Deleted {$deleted} read notifications older than 30 days.");
})->purpose('Delete read notifications older than 30 days')->daily();
