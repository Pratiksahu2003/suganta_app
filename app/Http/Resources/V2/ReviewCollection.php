<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReviewCollection extends ResourceCollection
{
    public $collects = ReviewResource::class;

    public function toArray(Request $request): array
    {
        return [
            'reviews' => $this->collection,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'success' => true,
            'code' => 200,
        ];
    }
}
