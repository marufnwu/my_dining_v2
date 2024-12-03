<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class Pipeline
{
    protected $data;
    protected $message;
    protected $status;
    protected $success;

    public function __construct($data = [], string $message = 'Success', int $status = 200, bool $success = true)
    {
        $this->data = $data;
        $this->message = $message;
        $this->status = $status;
        $this->success = $success;
    }

    /**
     * Static method to initialize the pipeline with a success response.
     */
    public static function success($data = [], string $message = 'Success', int $status = 200): self
    {
        return new self($data, $message, $status, true);
    }

    /**
     * Static method to initialize the pipeline with an error response.
     */
    public static function error(string $message = 'An error occurred', int $status = 400, $data = []): self
    {
        return new self($data, $message, $status, false);
    }

    /**
     * Static method to initialize the pipeline with a validation error response.
     */
    public static function validationError($errors, string $message = 'Validation failed', int $status = 422): self
    {
        return new self($errors, $message, $status, false);
    }

    /**
     * Add or modify data in the pipeline.
     */
    public function withData($data): self
    {
        $this->data = array_merge($this->data, $data);
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
     */
    public function toApiResponse(): JsonResponse
    {
        return response()->json([
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->success ? $this->data : null,
            'errors' => !$this->success ? $this->data : null,
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
