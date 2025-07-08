<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

/**
 * Pipeline for standardizing API responses.
 *
 * @template T The type of the data carried by the pipeline
 */
class Pipeline
{
    /** @var T|null */
    public $data;

    /** @var string */
    public $message;

    /** @var int */
    public $status;

    /** @var bool */
    public $success;

    /** @var int|null */
    public $errorCode;

    /**
     * Pipeline constructor.
     *
     * @param T|null $data The data to be returned
     * @param string $message The message to be returned
     * @param int $status HTTP status code
     * @param bool $success Whether the operation was successful
     * @param int|null $errorCode Optional error code for client-side handling
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
     *
     * @param T|null $data The data to be returned
     * @param string $message Success message
     * @param int $status HTTP status code
     * @return static
     */
    public static function success($data = null, string $message = 'Success', int $status = 200): self
    {
        return new self($data, $message, $status, true);
    }

    /**
     * Static method to initialize the pipeline with an error response.
     *
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param mixed $data Additional error data
     * @param int|null $errorCode Optional error code for client-side handling
     * @return static
     */
    public static function error(string $message = 'An error occurred', int $status = 200, $data = [], ?int $errorCode = null): self
    {
        return new self($data, $message, $status, false, $errorCode);
    }

    /**
     * Static method to initialize the pipeline with a validation error response.
     *
     * @param mixed $errors Validation errors
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param int|null $errorCode Optional error code for client-side handling
     * @return static
     */
    public static function validationError($errors, string $message = 'Validation failed', int $status = 422, ?int $errorCode = null): self
    {
        return new self($errors, $message, $status, false, $errorCode);
    }

    /**
     * Check if the pipeline represents a successful operation.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get the data from the pipeline.
     *
     * @return T|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Add or modify data in the pipeline.
     *
     * @param T $data
     * @return $this
     */
    public function withData($data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Modify the message.
     *
     * @param string $message
     * @return $this
     */
    public function withMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Modify the status code.
     *
     * @param int $status
     * @return $this
     */
    public function withStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Set the error code.
     *
     * @param int|null $errorCode
     * @return $this
     */
    public function withErrorCode(?int $errorCode): self
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    /**
     * Convert the object to a JsonResponse.
     *
     * @return JsonResponse
     */
    public function toApiResponse(): JsonResponse
    {
        return response()->json([
            'error' => !$this->success,
            'message' => $this->message,
            'data' => $this->success ? $this->data : null,
            'errors' => !$this->success ? $this->data : null,
            'error_code' => $this->errorCode
        ], $this->status);
    }

    /**
     * Allow casting to a JsonResponse directly when calling toApiResponse implicitly.
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        return $this->toApiResponse();
    }
}
