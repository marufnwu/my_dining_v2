<?php

namespace App\Helpers;
class ApiResponse
{
    public static function success($data = null, string $message = 'Success', int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error(string $message = 'An error occurred', int $status = 400, $data = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $data,
        ], $status);
    }

    public static function validationError($errors, string $message = 'Validation failed', int $status = 422)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
