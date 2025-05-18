<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

/**
* @template T
*/

class Pipeline
{
    /** @var T|null */
    public $data;

    /** @var string */
    protected $message;

    /** @var int */
    protected $status;

    /** @var bool */
    protected $success;

    /** @var int|null */
    protected $errorCode;

    /**
     * Pipeline constructor.
     *
     * @param T|null $data
     * @param string $message
     * @param int $status
     * @param bool $success
     * @param int|null $errorCode
     */

    public function __construct($data = null, string $message = 'Success', int $status = 200, bool $success = true, ?int $errorCode = null)
    {
        $this->data = $data;
        $this->message = $message;
        $this->status = $status;
        $this->success = $success;
        $this->errorCode = $errorCode;
    }

    /**
     * Static method to initialize the pipeline with a success response.
     */
    public static function success($data = null, string $message = 'Success', int $status = 200): self
    {
        return new self($data, $message, $status, true);
    }

    /**
     * Static method to initialize the pipeline with an error response.
     */
    public static function error(string $message = 'An error occurred', int $status = 200, $data = [], $errorCode=null): self
    {
        return new self($data, $message, $status, false, $errorCode);
    }

    /**
     * Static method to initialize the pipeline with a validation error response.
     */
    public static function validationError($errors, string $message = 'Validation failed', int $status = 422): self
    {
        return new self($errors, $message, $status, false);
    }


    public function isSuccess() : bool {
        return $this->success;
    }

    /**
     * Add or modify data in the pipeline.
     */
    public function withData($data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Modify the message.
     */
    public function withMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Modify the status code.
     */
    public function withStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Convert the object to a JsonResponse.
     * @return JsonResponse
     */
    public function toApiResponse(): JsonResponse
    {
        return response()->json([
            'error' => !$this->success,
            'message' => $this->message,
            'data' => $this->success ? $this->data : null,
            'errors' => !$this->success ? $this->data : null,
            "error_code" => $this->errorCode ?? null
        ], $this->status);
    }

    /**
     * Allow casting to a JsonResponse directly when calling toApiResponse implicitly.
     */
    public function __invoke(): JsonResponse
    {
        return $this->toApiResponse();
    }
}
