<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateCollectionModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:255'],

            'value' => ['required', 'numeric', 'min:0'],

            'deadline' => ['required', 'integer', 'min:0'],

            'requests' => ['nullable', 'array'],

            'request_link' => ['boolean'],
            'request_comment' => ['boolean'],
            'request_file' => ['boolean'],
            'request_collection' => ['boolean'],
            'request_layout' => ['boolean'],

            'reference_files' => ['nullable', 'array', 'max:10'],

            'reference_files.*' => [
                'image',
                'max:10240',
            ],

            'files_to_delete' => [
                'nullable',
                'array',
            ],

            'files_to_delete.*' => [
                'integer',
                'distinct',
                'exists:collection_model_files,id',
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'request_link' => (bool) data_get($this->input(), 'requests.link', false),
            'request_comment' => (bool) data_get($this->input(), 'requests.comment', false),
            'request_file' => (bool) data_get($this->input(), 'requests.file', false),
            'request_collection' => (bool) data_get($this->input(), 'requests.collection', false),
            'request_layout' => (bool) data_get($this->input(), 'requests.layout', false),
        ]);

        if ($this->has('files_to_delete')) {
            $this->merge([
                'files_to_delete' => $this->prepareArrayOfIntegers(
                    $this->input('files_to_delete', [])
                ),
            ]);
        }
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        unset($data['requests']);

        return $data;
    }

    protected function prepareArrayOfIntegers($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();
    }
}
