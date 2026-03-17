<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'tags' => $this->tags ?? [],
            'is_verified' => $this->is_verified,
            'helpful_count' => $this->helpful_count,
            'status' => $this->status,
            'reply' => $this->reply,
            'replied_at' => $this->replied_at?->toISOString(),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'time_ago' => $this->created_at?->diffForHumans(),

            'reviewer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar_url ?? null,
            ]),

            'reviewable' => [
                'type' => $this->reviewable_type_name,
                'id' => $this->reviewable_id,
                'name' => $this->whenLoaded('reviewable', fn () => $this->getReviewableName()),
            ],

            'permissions' => $this->when($request->user(), fn () => [
                'can_edit' => $request->user()->id === $this->user_id,
                'can_delete' => $request->user()->id === $this->user_id,
            ]),
        ];
    }

    protected function getReviewableName(): ?string
    {
        $reviewable = $this->reviewable;
        if (!$reviewable) {
            return null;
        }

        return $reviewable->name
            ?? $reviewable->title
            ?? $reviewable->display_name
            ?? null;
    }
}
