<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateMessRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "mess_name"=>"required|string|min:6|max:12",
            "name"=>"requirted|string|min:4|max:20",
            "user_name"=>"required|alpha_dash:ascii|unique:users,user_name|max:10|min:6",
            "email"=>"required|email|unique:users,email",
            ""
        ];
    }
}
