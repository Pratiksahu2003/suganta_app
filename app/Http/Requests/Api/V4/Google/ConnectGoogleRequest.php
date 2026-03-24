<?php

namespace App\Http\Requests\Api\V4\Google;

use Illuminate\Foundation\Http\FormRequest;

class ConnectGoogleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string', 'min:20'],
            'access_token' => ['nullable', 'string', 'min:20'],
            'expires_in' => ['nullable', 'integer', 'min:60', 'max:86400'],
            'google_email' => ['nullable', 'email', 'max:255'],
            'google_calendar_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
