<?php

namespace App\Rules;

use App\Models\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class ValidPhoneNumber implements ValidationRule
{
    private ?string $countryId;
    private ?string $countryCode;

    public function __construct(?string $countryId = null, ?string $countryCode = null)
    {
        $this->countryId = $countryId;
        $this->countryCode = $countryCode;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1. Validate country exists
        $country = $this->getCountry();
        if (!$country) {
            $fail('Please specify a valid country.');
            return;
        }

        // 2. Validate phone number format
        if (!preg_match('/^[0-9]+$/', $value)) {
            $fail('The phone number must contain only digits.');
            return;
        }

        // 3. Validate with libphonenumber
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            // Format: +[dial_code][number] (e.g., +1234567890)
            $phoneNumber = $phoneUtil->parse('+'.$country->dial_code.$value, $country->code);

            if (!$phoneUtil->isValidNumber($phoneNumber)) {
                $fail('The phone number is not valid for the selected country.');
            }
        } catch (NumberParseException $e) {
            $fail('The phone number format is invalid.');
        }
    }

    private function getCountry(): ?Country
    {
        if ($this->countryId) {
            return Country::find($this->countryId);
        }

        if ($this->countryCode) {
            return Country::where('dial_code', $this->countryCode)->first();
        }

        return null;
    }
}
