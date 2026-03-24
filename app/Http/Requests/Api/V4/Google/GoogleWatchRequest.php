<?php

namespace App\Http\Requests\Api\V4\Google;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoogleWatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resource_type' => ['required', Rule::in(['calendar', 'drive'])],
            'ttl_seconds' => ['nullable', 'integer', 'min:300', 'max:604800'],
        ];
    }
}
