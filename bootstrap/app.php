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
    ->withMiddleware(function (Middleware $middleware): void {
        // Native Android uses /api/* and mobile.php (no CSRF). Web forms must send @csrf.
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'researcher' => \App\Http\Middleware\IsResearcher::class,
            'researcher.pledge' => \App\Http\Middleware\EnsureResearcherPledged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
