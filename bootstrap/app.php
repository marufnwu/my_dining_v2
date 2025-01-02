<?php

use App\Enums\ErrorCode;
use App\Exceptions\EmailNotVerifiedException;
use App\Helpers\ApiResponse;
use App\Helpers\Pipeline;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\ForceJson;
use Illuminate\Auth\AuthenticationException;
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

        $middleware->append([
            CheckMaintenanceMode::class
        ]);

        $middleware->group("api", [
            ForceJson::class
        ]);

        $middleware->alias([
            "EmailVerified" => \App\Http\Middleware\EmailVerified::class,
            "MessJoinChecker" => \App\Http\Middleware\MessJoinChecker::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // custom response sanctum
        $exceptions->render(function (Exception $e,  $request) {
            $pipeline = null;

            if($e instanceof AuthenticationException){
                $pipeline = Pipeline::error(message:"Login Required", status:401, errorCode: ErrorCode::AUTHENTICATION_REQUIRED->value);
            }

            if($e instanceof EmailNotVerifiedException){
                $pipeline = Pipeline::error($e->getMessage(), errorCode:$e->getCode());
            }

            if ($request->is('api/*')) {
                if($pipeline){
                    return $pipeline->toApiResponse();
                }
            }
        });




    })->create();
