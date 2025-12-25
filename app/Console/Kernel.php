<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
{
    $schedule->call(function () {
        $users = \App\Models\User::whereNotNull('fcm_token')->get();
        foreach ($users as $user) {
            app(\App\Http\Controllers\NotificationController::class)->sendFollowedStoreNotification($user->id);
        }
    })->dailyAt('10:00');

    $schedule->call(function () {
        $users = \App\Models\User::whereNotNull('fcm_token')->get();
        foreach ($users as $user) {
            app(\App\Http\Controllers\NotificationController::class)->sendFollowedStoreNotification($user->id);
        }
    })->dailyAt('16:00');
}

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
