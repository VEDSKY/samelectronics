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
    ->withMiddleware(function (Middleware $middleware) {
        // The playground API is deliberately CSRF-exempt. It can only reach the
        // throwaway sandbox, so a cross-site request can do no more than restore
        // or vandalise the performance — never the real site.
        $middleware->validateCsrfTokens(except: [
            'api/terminal/shell/*',
            'api/terminal/sentinel/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
