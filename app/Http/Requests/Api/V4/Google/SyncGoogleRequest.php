<?php

namespace App\Http\Requests\Api\V4\Google;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncGoogleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'access_token' => ['nullable', 'string', 'min:20'],
            'sync' => ['sometimes', 'array'],
            'sync.*' => ['string', Rule::in(['calendar', 'youtube', 'drive'])],
            'calendar.max_results' => ['nullable', 'integer', 'min:1', 'max:100'],
            'youtube.max_results' => ['nullable', 'integer', 'min:1', 'max:50'],
            'drive.page_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'drive.order_by' => ['nullable', 'string', 'max:100'],
            'drive.page_token' => ['nullable', 'string', 'max:500'],
            'drive.query' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'access_token.min' => 'Google access token seems invalid.',
            'sync.*.in' => 'Supported sync types are calendar, youtube, and drive.',
        ];
    }
}
