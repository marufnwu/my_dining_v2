<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;

class EmailNotVerifiedException extends Exception
{
    protected $message = 'Email not verified';
    protected $code = ErrorCode::EMAIL_VERIFICATION_REQUIRED->value;

    public function __construct()
    {
        parent::__construct($this->message, $this->code);
    }
}
