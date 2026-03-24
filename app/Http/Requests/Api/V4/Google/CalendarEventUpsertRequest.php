<?php

namespace App\Http\Requests\Api\V4\Google;

use Illuminate\Foundation\Http\FormRequest;

class CalendarEventUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'attendees' => ['nullable', 'array', 'max:100'],
            'attendees.*.email' => ['required_with:attendees', 'email', 'max:255'],
            'attendees.*.display_name' => ['nullable', 'string', 'max:255'],
            'attendees.*.optional' => ['nullable', 'boolean'],
            'with_google_meet' => ['nullable', 'boolean'],
            'reminders.use_default' => ['nullable', 'boolean'],
            'reminders.overrides' => ['nullable', 'array', 'max:5'],
            'reminders.overrides.*.method' => ['required_with:reminders.overrides', 'string', 'in:email,popup'],
            'reminders.overrides.*.minutes' => ['required_with:reminders.overrides', 'integer', 'min:0', 'max:40320'],
        ];
    }
}
