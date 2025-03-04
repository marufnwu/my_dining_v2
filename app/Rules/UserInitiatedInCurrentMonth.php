<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserInitiatedInCurrentMonth implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  Closure  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if the user is initiated in the current month
        $isInitiated = app()->getMonth()->initiatedUser()->where("mess_user_id", $value)->exists();

        // If the user is not initiated, call the $fail callback with an error message
        if (!$isInitiated) {
            $fail('The :attribute must be a user initiated in the current month.');
        }
    }
}
