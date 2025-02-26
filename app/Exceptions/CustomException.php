<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;

class CustomException extends Exception
{

    public function __construct(?string $message = "Something went wrong!", ?ErrorCode $errorCode = ErrorCode::UNDEFINED)
    {
        // Use the provided message if it's not null, otherwise use the default message
        $this->message = $message ?? $this->message;

        // Use the provided code if it's not null, otherwise use the default code
        $this->code = $code ?? $errorCode->value;

        parent::__construct($this->message, $this->code);
    }
}
