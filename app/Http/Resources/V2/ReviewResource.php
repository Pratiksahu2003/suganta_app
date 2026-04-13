<?php

namespace App\Http\Resources\V2;

use Carbon\Carbon;
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
            'replied_at' => $this->formatDate($this->replied_at),
            'reviewed_at' => $this->formatDate($this->reviewed_at),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
            'time_ago' => $this->created_at ? $this->formatTimeAgo($this->created_at) : null,

            'reviewer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'role' => $this->user->role ?? null,
            ]),

            'reviewable' => [
                'type' => $this->whenLoaded('reviewable', fn () => $this->reviewable->role ?? null),
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

    protected function formatDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }
        if (is_string($value)) {
            try {
                return Carbon::parse($value)->format('c');
            } catch (\Throwable) {
                return $value;
            }
        }
        return null;
    }

    protected function formatTimeAgo(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->diffForHumans();
        }
        if (is_string($value)) {
            try {
                return Carbon::parse($value)->diffForHumans();
            } catch (\Throwable) {
                return null;
            }
        }
        return null;
    }
}
