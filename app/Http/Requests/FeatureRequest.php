<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeatureRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add your authorization logic here
    }

    public function rules()
    {
        $featureId = $this->route('feature')?->id;

        return [
            'name' => 'required|string|max:255|unique:features,name,' . $featureId,
            'description' => 'required|string|max:500',
            'is_countable' => 'boolean',
            'reset_period' => 'required|in:monthly,yearly,weekly,daily,lifetime',
            'free_limit' => 'required|integer|min:0',
            'category' => 'required|string|max:100',
            'is_active' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'A feature with this name already exists.',
            'reset_period.in' => 'Reset period must be one of: monthly, yearly, weekly, daily, lifetime.',
            'free_limit.min' => 'Free limit must be 0 or greater.'
        ];
    }
}
