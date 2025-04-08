<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Helpers\Pipeline;
use App\Rules\ValidPhoneNumber;
use App\Services\MessService;
use App\Services\UserService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateUserAccountRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'country_id' => [
                'required_without_all:country_code',
                Rule::exists('countries', 'id')
            ],
            'country_code' => [
                'required_without_all:country_id',
                'string',
                'max:5',
                Rule::exists('countries', 'dial_code')
            ],
            'phone' => [
                'required',
                'string',
                'max:15', // Increased max length to accommodate international numbers
                new ValidPhoneNumber(
                    $this->input('country_id'),
                    $this->input('country_code')
                )
            ],
            'city' => 'required|string|max:30',
            'gender' => ['required', 'in:' . implode(',', Gender::values())],
            'password' => 'required|string|min:8|confirmed',
            "role" => ""
        ];
    }
}
