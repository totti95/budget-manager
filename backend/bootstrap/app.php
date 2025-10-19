<?php

use App\Http\Middleware\LogRequests;
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
        // Pas besoin de EnsureFrontendRequestsAreStateful car on utilise Bearer tokens

        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        $middleware->throttleApi('60,1');

        // Convertir les requêtes entrantes de camelCase en snake_case
        $middleware->append(\App\Http\Middleware\ConvertRequestToSnakeCase::class);

        // Convertir toutes les réponses JSON en camelCase
        $middleware->append(\App\Http\Middleware\ConvertResponseToCamelCase::class);

        // Logging structuré des requêtes HTTP
        $middleware->append(LogRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
