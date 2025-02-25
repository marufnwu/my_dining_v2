<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;

class MustNotMessJoinException extends CustomException
{
    protected $message = 'You are already in a mess';
    protected $code = ErrorCode::ALREADY_JOINED_MESS->value;

    public function __construct()
    {
        parent::__construct($this->message, $this->code);
    }
}
