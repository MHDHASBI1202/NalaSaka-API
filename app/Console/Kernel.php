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
    // Kirim notifikasi acak ke SEMUA user yang punya token setiap hari jam 10:00
    $schedule->call(function () {
        $users = \App\Models\User::whereNotNull('fcm_token')->get();
        foreach ($users as $user) {
            // Panggil fungsi controller yang sudah kita buat
            app(\App\Http\Controllers\NotificationController::class)->sendFollowedStoreNotification($user->id);
        }
    })->dailyAt('10:00');

    // Kirim lagi jam 16:00
    $schedule->call(function () {
        $users = \App\Models\User::whereNotNull('fcm_token')->get();
        foreach ($users as $user) {
            app(\App\Http\Controllers\NotificationController::class)->sendFollowedStoreNotification($user->id);
        }
    })->dailyAt('16:00');
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
