<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Helpers\Pipeline;
use App\Rules\ValidPhoneNumber;
use App\Services\MessService;
use App\Services\UserService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class CreateUserAccountRequest extends BaseFormRequest
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
            // 'user_name' => 'required|string|max:255|unique:users,user_name',
            'email' => 'required|email|max:255|unique:users,email',
            'country_id' => 'required|exists:countries,id,status,1',
            'phone' => ['required', 'string', "max:10", new ValidPhoneNumber(request()->input('country_id'))],
            'city' => 'required|string|max:30',
            'gender' => ['required', 'in:' . implode(',', Gender::values())],
            'password' => 'required|string|min:8|confirmed',
            "role"=>""
        ];
    }


}
