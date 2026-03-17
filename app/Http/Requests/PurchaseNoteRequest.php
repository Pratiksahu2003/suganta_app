<?php

namespace App\Http\Requests;

use App\Models\Note;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note_id' => [
                'required',
                'integer',
                Rule::exists('notes', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note_id.required' => 'Please select a note to purchase.',
            'note_id.integer' => 'Invalid note ID format.',
            'note_id.exists' => 'The selected note is not available or inactive.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'note_id' => 'note',
        ];
    }

    /**
     * Get the note from the validated data.
     */
    public function getNote(): ?Note
    {
        $noteId = $this->validated('note_id');

        return Note::where('is_active', true)->find($noteId);
    }
}
