<?php

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
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->append([
            CheckMaintenanceMode::class
        ]);

        $middleware->group("api", [
            ForceJson::class
        ]);


    })
    ->withExceptions(function (Exceptions $exceptions) {
          // custom response sanctum
          $exceptions->render(function (AuthenticationException $e,  $request) {
            if ($request->is('api/*')) {
                return Pipeline::error(message:"Unauthenticated", status:402)->toApiResponse();
            }
            // Handle other exceptions or default response here
            return $e->render($request);
        });
    })->create();
