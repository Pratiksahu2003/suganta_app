<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'subscription_plan_id' => $this->subscription_plan_id,
            'payment_id' => $this->payment_id,
            'status' => $this->status,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'amount_paid' => $this->amount_paid,
            'is_active' => $this->isActive(),
            'days_remaining' => $this->expires_at ? max(0, now()->diffInDays($this->expires_at, false)) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relationships
            'plan' => $this->whenLoaded('plan', function () {
                return new SubscriptionPlanResource($this->plan);
            }),
            'payment' => $this->whenLoaded('payment', function () {
                return new PaymentResource($this->payment);
            }),
        ];
    }
}