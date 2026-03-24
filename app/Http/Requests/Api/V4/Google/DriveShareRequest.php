<?php

namespace App\Http\Requests\Api\V4\Google;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriveShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['reader', 'commenter', 'writer'])],
            'type' => ['nullable', Rule::in(['user', 'group', 'domain', 'anyone'])],
            'send_notification_email' => ['nullable', 'boolean'],
        ];
    }
}
