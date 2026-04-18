<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
            api: __DIR__.'/../routes/api.php', // add this line

        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
         $middleware->alias([
        'is_admin'  => \App\Http\Middleware\IsAdmin::class,
        'is_doctor' => \App\Http\Middleware\IsDoctor::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
