<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePortfolioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation. Normalize remove_images/remove_files so empty
     * or null values from Flutter multipart don't trigger validation errors.
     * Also handles JSON strings (e.g. "[0,1]") that some clients send.
     */
    protected function prepareForValidation(): void
    {
        // Normalize remove_images/remove_files before validation.
        // Handles: array, JSON string, or dotted keys (remove_images.0, remove_images.1).
        // Always merge so validation never sees malformed/empty values that trigger "field is required".
        $removeImages = $this->collectRemoveInput('remove_images');
        $removeFiles = $this->collectRemoveInput('remove_files');

        $this->merge([
            'remove_images' => $this->normalizeRemoveArray($removeImages),
            'remove_files' => $this->normalizeRemoveArray($removeFiles),
        ]);
    }

    /**
     * Collect remove_images/remove_files from request - supports array, dotted keys, or both.
     */
    private function collectRemoveInput(string $key): mixed
    {
        $value = $this->input($key);
        if ($value !== null) {
            return $value;
        }
        // Some clients send remove_images.0, remove_images.1 as separate keys
        $all = $this->all();
        $collected = [];
        foreach ($all as $k => $v) {
            if (str_starts_with($k, $key . '.') && $v !== null && $v !== '') {
                $collected[] = $v;
            }
        }
        return $collected ?: null;
    }

    /**
     * Normalize remove_images/remove_files input: handle JSON strings, filter empties.
     * Keeps 0 and "0" (valid indices) - only drops null, empty string, empty array.
     */
    private function normalizeRemoveArray(mixed $value): array
    {
        try {
            if (is_array($value)) {
                $filtered = array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
                return $filtered;
            }
            if (is_string($value) && trim($value) !== '') {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return array_values(array_filter($decoded, fn ($v) => $v !== null && $v !== ''));
                }
            }
        } catch (\Throwable $e) {
            // Defensive: any odd input results in empty array so validation passes
        }
        return [];
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'images' => ['sometimes', 'array', 'max:10'],
            'images.*' => [
                'required',
                'file',
                'image',
                'max:5120',
                'mimes:jpg,jpeg,png,gif,webp'
            ],
            // remove_images / remove_files: no validation - prepared in prepareForValidation, handled in controller
            'files' => ['sometimes', 'array', 'max:10'],
            'files.*' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar'
            ],
            'category' => ['sometimes', 'nullable', 'string', 'max:500'],
            'tags' => ['sometimes', 'nullable', 'string', 'max:500'],
            'url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['draft', 'published', 'archived'])
            ],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_featured' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Portfolio title must not exceed 255 characters.',
            'images.max' => 'You can upload a maximum of 10 images.',
            'images.*.image' => 'Each file must be a valid image.',
            'images.*.max' => 'Each image must not exceed 5MB.',
            'files.max' => 'You can upload a maximum of 10 files.',
            'files.*.max' => 'Each file must not exceed 10MB.',
            'url.url' => 'Please provide a valid URL.',
            'category.max' => 'Categories must not exceed 500 characters.',
            'tags.max' => 'Tags must not exceed 500 characters.',
            'status.in' => 'Invalid status selected.',
        ];
    }
}
