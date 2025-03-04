<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class MessUserExistsInCurrentMess implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Get the current mess ID
        $messId = app()->getMess()->id;

        // Check if the mess_user_id exists in the mess_users table for the current mess
        return DB::table('mess_users')
            ->where('id', $value)
            ->where('mess_id', $messId)
            ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The selected :attribute is invalid or does not belong to the current mess.';
    }
}
