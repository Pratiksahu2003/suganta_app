<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'billing_period' => $this->billing_period,
            'max_images' => $this->max_images,
            'max_files' => $this->max_files,
            'features' => $this->features,
            'is_popular' => $this->is_popular,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            's_type' => $this->s_type,
            'formatted_price' => $this->currency . ' ' . number_format($this->price, 2),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}