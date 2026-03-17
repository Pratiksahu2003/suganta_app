<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isPurchased = $user ? $this->resource->isPurchasedBy($user->id) : false;
        $hasSubscriptionAccess = $user
            ? app(\App\Services\SubscriptionService::class)->hasActiveSubscription($user, 2)
            : false;
        $canAccess = $isPurchased || $hasSubscriptionAccess || !$this->resource->is_paid;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'is_paid' => $this->is_paid,
            'is_active' => $this->is_active,
            'download_count' => $this->download_count,
            'file_url' => $canAccess ? $this->file_url : null,
            'file_size' => $this->file_size,
            'formatted_price' => $this->is_paid ? '₹' . number_format($this->price, 2) : 'Free',
            'can_access' => $canAccess,
            'is_purchased' => $isPurchased,
            'has_subscription_access' => $hasSubscriptionAccess,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'note_type' => $this->whenLoaded('noteType', fn () => [
                'id' => $this->noteType->id,
                'code' => $this->noteType->code,
                'name' => $this->noteType->name,
            ]),
            'note_category' => $this->whenLoaded('noteCategory', fn () => [
                'id' => $this->noteCategory->id,
                'name' => $this->noteCategory->name,
                'slug' => $this->noteCategory->slug,
            ]),
        ];
    }
}
