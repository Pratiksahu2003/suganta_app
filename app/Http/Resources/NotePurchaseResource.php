<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotePurchaseResource extends JsonResource
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
            'note_id' => $this->note_id,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'download_count' => $this->download_count,
            'purchased_at' => $this->purchased_at?->toIso8601String(),
            'can_download' => $this->canDownload(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'note' => $this->whenLoaded('note', fn () => new NoteResource($this->note)),
            'payment' => $this->whenLoaded('payment', fn () => new PaymentResource($this->payment)),
        ];
    }
}
