<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ActiveMessUser implements ValidationRule
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
        // Get the current mess ID
        $messId = app()->getMess()->id;

        // Check if the mess_user exists
        $messUser = DB::table('mess_users')
            ->where('id', $value)
            ->first();

        if (!$messUser) {
            $fail('The selected mess user does not exist.');
            return;
        }

        // Check if the mess_user belongs to the current mess
        if ($messUser->mess_id != $messId) {
            $fail('The selected mess user does not belong to the current mess.');
            return;
        }

        // Check if the mess_user is active
        if ($messUser->status !== 'active') {
            $fail('The selected mess user is not active.');
            return;
        }
    }
}
