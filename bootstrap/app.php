<?php

use App\Console\Commands\FreshMigrateAndSeed;
use App\Enums\ErrorCode;
use App\Exceptions\CustomException;
use App\Exceptions\EmailNotVerifiedException;
use App\Exceptions\NoMessException;
use App\Exceptions\PermissionDeniedException;
use App\Helpers\Pipeline;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\ForceJson;
use App\Http\Middleware\RoleMiddleware;
use Bootstrap\MyApplication;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return MyApplication::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Global middleware for all requests
        $middleware->append([
            CheckMaintenanceMode::class,
        ]);

        // API middleware - correct syntax for Laravel 11
        $middleware->appendToGroup('api', [
            ForceJson::class,
        ]);

        $middleware->alias([
            "EmailVerified" => \App\Http\Middleware\EmailVerified::class,
            "MessJoinChecker" => \App\Http\Middleware\MessJoinChecker::class,
            "MessPermission" => \App\Http\Middleware\MessPermission::class,
            "MonthChecker" => \App\Http\Middleware\CheckActiveMonth::class,
            "mess.user" => \App\Http\Middleware\MessUserChecker::class,
            "force.json" => \App\Http\Middleware\ForceJson::class,
            "role" => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Configure authentication redirects
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Don't redirect API requests, let exception handler deal with it
                return null;
            }
            // For web routes, return a simple path instead of route() since login route doesn't exist
            return '/login';
        });
    })
    ->withCommands([
        FreshMigrateAndSeed::class
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        // custom response for API routes
        $exceptions->render(function (Exception $e, $request) {

            $pipeline = null;

            if ($e instanceof AuthenticationException) {
                // For API routes, always return JSON instead of redirecting
                if ($request->is('api/*') || $request->expectsJson()) {
                    $pipeline = Pipeline::error(message: "Authentication required", status: 401, errorCode: ErrorCode::AUTHENTICATION_REQUIRED->value);
                }
            } elseif ($e instanceof EmailNotVerifiedException) {
                $pipeline = Pipeline::error($e->getMessage(), errorCode: $e->getCode());
            } elseif ($e instanceof ValidationException) {
                $errors = collect($e->errors())->flatten()->all();
                $pipeline = Pipeline::validationError($errors, message: 'Validation failed', status: 200);
            } elseif ($e instanceof NoMessException) {
                $pipeline = Pipeline::error(message: $e->getMessage(), status: 200, errorCode: ErrorCode::NO_MESS_ACCESS->value);
            } elseif ($e instanceof PermissionDeniedException) {
                $pipeline = Pipeline::error(message: $e->getMessage(), status: 403, errorCode: ErrorCode::NO_MESS_ACCESS->value);
            } elseif ($e instanceof CustomException) {
                $pipeline = Pipeline::error(message: $e->getMessage(), status: 403, errorCode: $e->getCode());
            } elseif ($e instanceof NotFoundHttpException) {
                $pipeline = Pipeline::error(message: $e->getMessage(), status: 404);
            }

            // Return JSON response for API routes
            if ($request->is('api/*') || $request->expectsJson()) {
                if ($pipeline) {
                    return $pipeline->toApiResponse();
                }
            }
        });
    })->create();
