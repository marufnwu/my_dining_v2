<?php

namespace App\Exceptions;

use Exception;

class MaintenanceModeException extends Exception
{

    public function render($request)
    {
        if ($request->is('api/*')) {
            // Return JSON for API routes
            return response()->json([
                'error' => true,
                'message' => $this->message,
            ], $this->code);
        }

        // Render an HTML page for web routes
        return response()->view('errors.maintenance', [
            'message' => $this->message,
        ], $this->code);
    }

}
