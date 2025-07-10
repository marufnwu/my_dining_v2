<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_id' => 'required|exists:subscriptions,id',
            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
                function ($attribute, $value, $fail) {
                    $method = \App\Models\PaymentMethod::find($value);
                    if (!$method?->enabled) {
                        $fail('This payment method is not currently available.');
                    }
                    if ($method?->type !== 'manual') {
                        $fail('Invalid payment method type.');
                    }
                },
            ],
            'amount' => 'required|numeric|min:0.01',
            'transaction_id' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
            'proof_url' => 'nullable|url|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_id.required' => 'A subscription is required.',
            'subscription_id.exists' => 'The selected subscription is invalid.',
            'payment_method_id.required' => 'A payment method is required.',
            'payment_method_id.exists' => 'The selected payment method is invalid.',
            'amount.required' => 'Please enter the payment amount.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The amount must be at least :min.',
            'transaction_id.required' => 'Please provide the transaction ID from your payment.',
            'proof_url.url' => 'Please provide a valid URL for the payment proof.',
        ];
    }
}
