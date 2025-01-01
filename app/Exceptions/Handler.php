<?php

namespace App\Exceptions;

use App\Helpers\Pipeline;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof EmailNotVerifiedException) {
            return Pipeline::error($exception->getMessage(), $exception->getCode())->toApiResponse();
        }

        if($exception instanceof AuthenticationException){
            return Pipeline::error(message:"Unauthenticated", status:$exception->getCode())->toApiResponse();
        }


        return parent::render($request, $exception);
    }
}
