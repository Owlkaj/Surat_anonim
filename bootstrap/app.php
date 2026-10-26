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
        // Jangan pernah memakai '*' di sini: header X-Forwarded-For dari klien
        // akan dipercaya dan seluruh rate limit berbasis IP dapat dilewati.
        $rawProxies = (string) env('TRUSTED_PROXIES', '127.0.0.1,::1');
        $trustedProxies = array_values(array_filter(array_map('trim', explode(',', $rawProxies))));

        $middleware->trustProxies(at: $trustedProxies);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
