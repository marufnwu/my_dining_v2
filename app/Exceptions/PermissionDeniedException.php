<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;

class PermissionDeniedException extends Exception
{
    protected $message = 'Permission denied';
    protected $code = ErrorCode::EMAIL_VERIFICATION_REQUIRED->value;

    public function __construct()
    {
        parent::__construct($this->message);
    }
}
