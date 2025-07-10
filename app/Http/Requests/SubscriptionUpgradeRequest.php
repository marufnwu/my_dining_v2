<?php

namespace App\Http\Requests;

use App\Models\Plan;
use App\Models\PlanPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionUpgradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'exists:plans,id',
                function ($attribute, $value, $fail) {
                    $plan = Plan::find($value);
                    if (!$plan?->is_active) {
                        $fail('Selected plan is not currently available.');
                    }
                },
            ],
            'package_id' => [
                'required',
                'exists:plan_packages,id',
                function ($attribute, $value, $fail) {
                    $package = PlanPackage::find($value);
                    if (!$package?->is_active) {
                        $fail('Selected package is not currently available.');
                    }
                    if ($package && $package->plan_id != $this->input('plan_id')) {
                        $fail('Selected package does not belong to the selected plan.');
                    }
                },
            ],
            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
                function ($attribute, $value, $fail) {
                    $method = \App\Models\PaymentMethod::find($value);
                    if (!$method?->enabled) {
                        $fail('This payment method is not currently available.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'Please select a subscription plan.',
            'plan_id.exists' => 'The selected plan is invalid.',
            'package_id.required' => 'Please select a package.',
            'package_id.exists' => 'The selected package is invalid.',
            'payment_method_id.required' => 'Please select a payment method.',
            'payment_method_id.exists' => 'The selected payment method is invalid.',
        ];
    }
}
