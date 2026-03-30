<?php

namespace App\Http\Requests\CollectionModels;

use Illuminate\Foundation\Http\FormRequest;

class CollectionModelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'value' => ['required', 'numeric', 'min:0'],
            'deadline' => ['required', 'integer', 'min:0'],
            'requests.link' => ['sometimes', 'boolean'],
            'requests.comment' => ['sometimes', 'boolean'],
            'requests.file' => ['sometimes', 'boolean'],
            'requests.collection' => ['sometimes', 'boolean'],
            'requests.layout' => ['sometimes', 'boolean'],
            'reference_files' => ['nullable', 'array'],
            'reference_files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
            'files_to_delete' => ['nullable', 'array'],
            'files_to_delete.*' => ['integer', 'exists:collection_model_files,id'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $requests = $this->input('requests', []);

        $normalized = [
            'name' => $this->prepareString($this->input('name')),
            'value' => $this->prepareNumeric($this->input('value')),
            'deadline' => is_numeric($this->input('deadline')) ? (int) $this->input('deadline') : $this->input('deadline'),
            'requests' => [
                'link' => $this->prepareBoolean($requests['link'] ?? false),
                'comment' => $this->prepareBoolean($requests['comment'] ?? false),
                'file' => $this->prepareBoolean($requests['file'] ?? false),
                'collection' => $this->prepareBoolean($requests['collection'] ?? false),
                'layout' => $this->prepareBoolean($requests['layout'] ?? false),
            ],
        ];

        if ($this->has('files_to_delete')) {
            $normalized['files_to_delete'] = $this->prepareArrayOfIntegers($this->input('files_to_delete', []));
        }

        $this->merge($normalized);
    }

    protected function prepareBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function prepareNumeric($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }

    protected function prepareString($value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_null($value)) {
            return '';
        }

        return trim((string) $value);
    }

    protected function prepareArrayOfIntegers($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }

        if (is_null($value) || $value === '') {
            return [];
        }

        return [(int) $value];
    }
}

