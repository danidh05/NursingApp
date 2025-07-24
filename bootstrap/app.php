<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Run birthday processing daily at 9:00 AM
        $schedule->command('birthdays:process')
                ->dailyAt('09:00')
                ->timezone('Asia/Beirut') // Adjust to your timezone
                ->withoutOverlapping()
                ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();


    