<?php

namespace App\Http\Requests;

use App\Rules\MessUserExistsInCurrentMess;
use App\Rules\UserInitiatedInCurrentMonth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MealRequestFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "mess_user_id" => [
                "required",
                "numeric",
                new MessUserExistsInCurrentMess(),
                new UserInitiatedInCurrentMonth(),
            ],
            "date" => "required|date",
            "breakfast" => "nullable|numeric|min:0",
            "lunch" => "nullable|numeric|min:0",
            "dinner" => "nullable|numeric|min:0",
            "comment" => "sometimes|string|nullable",
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'breakfast' => $this->breakfast ?? 0,
            'lunch' => $this->lunch ?? 0,
            'dinner' => $this->dinner ?? 0,
        ]);
    }

    protected function withValidator($validator)
    {
        $validator->sometimes(['breakfast', 'lunch', 'dinner'], 'required_without_all:breakfast,lunch,dinner', function ($input) {
            return empty($input->breakfast) && empty($input->lunch) && empty($input->dinner);
        });
    }
}
