<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'srru.email' => \App\Http\Middleware\EnsureSrruEmail::class,
            'profile.completed' => \App\Http\Middleware\EnsureProfileCompleted::class,
            'admin' => \App\Http\Middleware\EnsureIsAdmin::class,
            'super_admin' => \App\Http\Middleware\EnsureIsSuperAdmin::class,
            'api.srru.email' => \App\Http\Middleware\EnsureSrruEmailApi::class,
            'api.profile.completed' => \App\Http\Middleware\EnsureProfileCompletedApi::class,
            'api.locale' => \App\Http\Middleware\SetLocaleApi::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
