<?php

use App\Http\Middleware\LogRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Pas besoin de EnsureFrontendRequestsAreStateful car on utilise Bearer tokens

        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        //         Rate limiting désactivé temporairement pour le développement
        $middleware->throttleApi('300,1');

        // Convertir les requêtes entrantes de camelCase en snake_case
        $middleware->append(\App\Http\Middleware\ConvertRequestToSnakeCase::class);

        // Convertir toutes les réponses JSON en camelCase
        $middleware->append(\App\Http\Middleware\ConvertResponseToCamelCase::class);

        // Logging structuré des requêtes HTTP
        $middleware->append(LogRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Standardiser le format des erreurs pour les requêtes API JSON
        $exceptions->render(function (\Throwable $e, $request) {
            // Ne traiter que les requêtes API (Accept: application/json)
            if (! $request->expectsJson()) {
                return null; // Laisser le handler par défaut gérer
            }

            $statusCode = 500;
            $message = 'Une erreur est survenue';
            $errors = null;

            // Validation errors (422)
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $statusCode = 422;
                $message = $e->getMessage();
                $errors = $e->errors();
            }
            // Authentication errors (401)
            elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                $statusCode = 401;
                $message = 'Non authentifié';
            }
            // Authorization errors (403)
            elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                $statusCode = 403;
                $message = $e->getMessage() ?: 'Action non autorisée';
            }
            // Model not found (404)
            elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $statusCode = 404;
                $message = 'Ressource introuvable';
            }
            // Too many requests (429)
            elseif ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
                $statusCode = 429;
                $message = 'Trop de requêtes. Veuillez réessayer plus tard.';
            }
            // HTTP exceptions
            elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $statusCode = $e->getStatusCode();
                $message = $e->getMessage() ?: $message;
            }
            // En production, ne pas exposer les détails des erreurs serveur
            elseif (config('app.debug')) {
                $message = $e->getMessage();
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
                'code' => $statusCode,
            ], $statusCode);
        });
    })->create();
