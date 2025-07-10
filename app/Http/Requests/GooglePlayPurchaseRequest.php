<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GooglePlayPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
                function ($attribute, $value, $fail) {
                    $method = \App\Models\PaymentMethod::find($value);
                    if (!$method?->enabled) {
                        $fail('This payment method is not currently available.');
                    }
                    if ($method?->type !== 'google_play') {
                        $fail('Invalid payment method type.');
                    }
                },
            ],
            'subscription_id' => 'required|string|max:255',
            'purchase_token' => 'required|string|max:1000',
            'package_name' => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_id.required' => 'A payment method is required.',
            'payment_method_id.exists' => 'The selected payment method is invalid.',
            'subscription_id.required' => 'The Google Play subscription ID is required.',
            'purchase_token.required' => 'The purchase token is required.',
            'package_name.required' => 'The package name is required.',
        ];
    }
}
