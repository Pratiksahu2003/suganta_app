<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'order_id' => $this->order_id,
            'reference_id' => $this->reference_id,
            'user_id' => $this->user_id,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'status' => $this->status,
            'formatted_amount' => $this->currency . ' ' . number_format($this->amount, 2),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Conditionally include meta and gateway_response for detailed views
            'meta' => $this->when($request->routeIs('*.show'), $this->meta),
            'gateway_response' => $this->when($request->routeIs('*.show'), $this->gateway_response),
        ];
    }
}