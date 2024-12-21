<?php

namespace App\Rules;

use App\Models\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class ValidPhoneNumber implements ValidationRule
{
    private string $countryId;

    public function __construct(string $countryId)
    {
        $this->countryId = $countryId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        $country = Country::where("id", $this->countryId)->first();

        try {
            $phoneNumber = $phoneUtil->parse($country->dial_code.$value, $country->code);
            if (!$phoneUtil->isValidNumber($phoneNumber)) {
                $fail("The $attribute is not a valid phone number.");
            }
        } catch (NumberParseException $e) {

            $fail("The $attribute is not a valid phone number.");
        }
    }
}
