<?php

namespace App\Http\Requests\Api\V4\Google;

use Illuminate\Foundation\Http\FormRequest;

class OAuthCodeExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:20'],
            'redirect_uri' => ['nullable', 'url', 'max:500'],
            'state' => ['nullable', 'string', 'min:16', 'max:255'],
        ];
    }
}
