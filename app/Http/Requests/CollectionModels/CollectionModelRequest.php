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
        $rules = [
            'value' => ['required', 'numeric', 'min:0'],
            'deadline' => ['required', 'integer', 'min:0'],
            'requests.link' => ['sometimes', 'boolean'],
            'requests.comment' => ['sometimes', 'boolean'],
            'requests.file' => ['sometimes', 'boolean'],
            'link' => ['nullable', 'url', 'required_if:requests.link,true'],
            'comment' => ['nullable', 'string', 'max:5000', 'required_if:requests.comment,true'],
            'reference_files' => ['nullable', 'array'],
            'reference_files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
            'files_to_delete' => ['nullable', 'array'],
            'files_to_delete.*' => ['integer', 'exists:collection_model_files,id'],
        ];

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $requests = $this->input('requests', []);

        $this->merge([
            'value' => $this->prepareNumeric($this->input('value')),
            'deadline' => is_numeric($this->input('deadline')) ? (int) $this->input('deadline') : $this->input('deadline'),
            'requests' => [
                'link' => $this->prepareBoolean($requests['link'] ?? false),
                'comment' => $this->prepareBoolean($requests['comment'] ?? false),
                'file' => $this->prepareBoolean($requests['file'] ?? false),
            ],
            'files_to_delete' => $this->prepareArrayOfIntegers($this->input('files_to_delete', [])),
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $shouldRequestFile = data_get($this->input('requests', []), 'file', false);

            if (! $shouldRequestFile) {
                return;
            }

            $uploadedFiles = $this->file('reference_files', []);
            $uploadedCount = is_array($uploadedFiles) ? count($uploadedFiles) : 0;

            if ($this->isMethod('post')) {
                if ($uploadedCount === 0) {
                    $validator->errors()->add('reference_files', 'Envie pelo menos um arquivo.');
                }

                return;
            }

            $model = $this->route('collection_model');

            if (! $model) {
                return;
            }

            $existingCount = $model->files()->count();
            $filesMarkedForDeletion = count($this->input('files_to_delete', []));
            $remaining = $existingCount - $filesMarkedForDeletion;

            if ($remaining <= 0 && $uploadedCount === 0) {
                $validator->errors()->add('reference_files', 'Envie pelo menos um arquivo.');
            }
        });
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

