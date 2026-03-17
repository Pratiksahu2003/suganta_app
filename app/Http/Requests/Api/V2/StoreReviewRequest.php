<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reviewable_type' => ['required', 'string', Rule::in(['user'])],
            'reviewable_id' => ['required', 'integer', 'min:1'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'reviewable_type.required' => 'Please specify the review type (user).',
            'reviewable_type.in' => 'Review type must be "user".',
            'reviewable_id.required' => 'Please specify the user you are reviewing.',
            'reviewable_id.min' => 'Invalid user ID.',
            'rating.required' => 'Please provide a rating (1–5 stars).',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'comment.max' => 'Your review comment cannot exceed 5000 characters.',
            'tags.max' => 'You can add a maximum of 10 tags.',
            'tags.*.max' => 'Each tag cannot exceed 50 characters.',
        ];
    }
}
