<?php

namespace App\Http\Requests;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subscription_plan_id' => [
                'required',
                'integer',
                Rule::exists('subscription_plans', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subscription_plan_id.required' => 'Please select a subscription plan.',
            'subscription_plan_id.integer' => 'Invalid subscription plan ID format.',
            'subscription_plan_id.exists' => 'The selected subscription plan is not available or inactive.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subscription_plan_id' => 'subscription plan',
        ];
    }

    /**
     * Get the subscription plan from the validated data.
     */
    public function getSubscriptionPlan(): ?SubscriptionPlan
    {
        $planId = $this->validated('subscription_plan_id');
        
        return SubscriptionPlan::active()->find($planId);
    }
}