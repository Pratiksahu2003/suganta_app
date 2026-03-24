<?php

namespace App\Http\Requests\Api\V4\Google;

use Illuminate\Foundation\Http\FormRequest;

class DriveUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:102400'],
            'name' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:150'],
        ];
    }
}
