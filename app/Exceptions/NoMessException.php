<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;

class NoMessException extends Exception
{
    protected $message = 'No valid mess access found';
    protected $code = ErrorCode::NO_MESS_ACCESS->value;

    public function __construct()
    {
        parent::__construct($this->message, $this->code);
    }
}
