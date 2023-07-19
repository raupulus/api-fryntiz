<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        $schedule->command('aemet:update-every10m')->everyTenMinutes();
        $schedule->command('aemet:update-every30m')->everyThirtyMinutes();
        $schedule->command('aemet:update-every4h')->everyFourHours();
        $schedule->command('aemet:update-daily8')->dailyAt('08:05');
        $schedule->command('aemet:update-daily12')->dailyAt('12:05');
        $schedule->command('aemet:update-daily20')->dailyAt('20:05');

        ## Publicar contenido programado
        $schedule->command('content:publish')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
