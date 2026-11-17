<?php

use App\Support\Stats\GeoIp;
use App\Support\Stats\VisitorStats;
use App\Support\Updater;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update from git: the admin page "System update" queues a request, cron runs it.
// Needs: * * * * * cd /path/to/app && php8.4 artisan schedule:run  (crontab of www-data)
Schedule::call(fn () => app(Updater::class)->heartbeat())->everyMinute()->name('updater-heartbeat');
Schedule::command('app:update --if-requested')->everyMinute()->withoutOverlapping(30)->runInBackground();

// Visitor statistics: the country database (DB-IP lite) monthly, old page views daily.
Artisan::command('geoip:update', function (GeoIp $geo): void {
    $this->info('Downloaded: '.$geo->update());
})->purpose('Download the free DB-IP country database used for the visitor countries');
Schedule::command('geoip:update')->monthlyOn(3, '04:20');

Artisan::command('stats:prune', function (VisitorStats $stats): void {
    $this->info('Removed page views: '.$stats->prune((int) settings('seo.stats_retention_days', 730)));
})->purpose('Remove page views older than the retention period (Settings → Search engines)');
Schedule::command('stats:prune')->dailyAt('03:40');
