<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'admin'                  => \App\Http\Middleware\AdminMiddleware::class,
        'check.role'             => \App\Http\Middleware\CheckRole::class,
        'check.active.user'      => \App\Http\Middleware\CheckActiveUser::class,
        'check.profile.complete' => \App\Http\Middleware\CheckProfileComplete::class,
        'force.password.change'  => \App\Http\Middleware\ForcePasswordChange::class,
    ]);
    $middleware->web(append: [
        \App\Http\Middleware\ApplySettings::class,
    ]);
    $middleware->validateCsrfTokens(except: [
        'api/v1/*',
    ]);
})


    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();