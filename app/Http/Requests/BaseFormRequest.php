<?php

namespace App\Http\Requests;

use App\Helpers\Pipeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        if (request()->acceptsJson()) {
            // Gather the validation errors
            $errors = $validator->errors();

            // Return a structured error response using the Pipeline helper
            throw new ValidationException(
                $validator,
                Pipeline::validationError($errors->all(), message: 'Validation failed', status: 422)->toApiResponse()
            );
        }
        
        parent::failedValidation($validator);

    }
}
