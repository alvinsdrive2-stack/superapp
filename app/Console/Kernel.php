<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Dashboard cache refresh every 10 minutes
        $schedule->command('dashboard:cache-refresh')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->description('Refresh dashboard cache for optimal performance');

        // Clean old scheduler logs daily at 2 AM
        $schedule->call(function () {
            \App\Models\CacheSchedulerLog::clearOld(7);
        })->dailyAt('02:00')
            ->description('Clean old cache scheduler logs');

        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}