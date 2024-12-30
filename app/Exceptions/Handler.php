<?php

namespace App\Exceptions;

use App\Helpers\Pipeline;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    // ...existing code...

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof EmailNotVerifiedException) {
            return Pipeline::error($exception->getMessage(), $exception->getCode())->toApiResponse();
        }

        return parent::render($request, $exception);
    }
}
