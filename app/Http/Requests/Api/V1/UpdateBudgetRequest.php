<?php

namespace App\Http\Requests\Api\V1;

use App\Models\CollectionModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in([
                'em aberto',
                'Em aberto',
                'aprovado',
                'Aprovado',
                'cancelado',
                'Cancelado',
                'Em produção',
                'Enviado',
            ])],
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.name' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls' => ['required', 'array', 'min:1'],
            'rooms.*.walls.*.name' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls.*.direction' => [
                'required',
                'string',
                Rule::in(['left-to-right', 'right-to-left']),
            ],
            'rooms.*.walls.*.width' => ['required', 'numeric', 'min:0.01'],
            'rooms.*.walls.*.height' => ['required', 'numeric', 'min:0.01'],
            'rooms.*.walls.*.model' => ['nullable', 'integer', 'exists:collection_models,id'],
            'rooms.*.walls.*.comment_referring_model' => ['nullable', 'string', 'max:500'],
            'rooms.*.walls.*.link_referring_model' => ['nullable', 'string', 'url', 'max:500'],
            'rooms.*.walls.*.files_referring_model' => ['nullable', 'array'],
            'rooms.*.walls.*.files_referring_model.*' => ['nullable', 'string', 'max:2048'],
            'rooms.*.walls.*.collection_referring_model' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls.*.continueSameArt' => ['required', 'boolean'],
            'rooms.*.walls.*.continuations' => [
                'nullable',
                'array',
                'required_if:rooms.*.walls.*.continueSameArt,true',
            ],
            'rooms.*.walls.*.continuations.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'rooms.*.walls.*.continuations.*.width' => [
                'required_with:rooms.*.walls.*.continuations',
                'numeric',
                'min:0.01',
            ],
            'rooms.*.walls.*.continuations.*.height' => [
                'required_with:rooms.*.walls.*.continuations',
                'numeric',
                'min:0.01',
            ],
            'rooms.*.walls.*.continuations.*.fit' => [
                'nullable',
                'string',
                Rule::in(['Inicial', 'Superior', 'Inferior', 'Central']),
            ],
            'deliveryTime' => ['nullable', 'integer', 'min:0'],
            'cep' => ['nullable', 'string', 'max:9'],
            'selectedCarrier' => ['nullable', 'array'],
            'selectedCarrier.name' => ['required_with:selectedCarrier', 'string', 'max:255'],
            'selectedCarrier.price' => ['required_with:selectedCarrier', 'numeric', 'min:0'],
            'selectedCarrier.deliveryTime' => ['required_with:selectedCarrier', 'integer', 'min:0'],
            'dropshipping_budget' => ['nullable', 'boolean'],
            'dropshipping_data' => ['nullable', 'array'],
            'dropshipping_data.name' => ['required_with:dropshipping_data', 'string', 'max:255'],
            'dropshipping_data.person_type' => ['required_with:dropshipping_data', 'string', Rule::in(['PF', 'PJ'])],
            'dropshipping_data.cpf_cnpj' => ['required_with:dropshipping_data', 'string', 'max:18'],
            'dropshipping_data.IE' => [
                'required_if:dropshipping_data.person_type,PJ',
                'nullable',
                'string',
                'max:18',
            ],
            'dropshipping_data.email' => ['required_with:dropshipping_data', 'email', 'max:255'],
            'dropshipping_data.phone' => ['required_with:dropshipping_data', 'string', 'max:15'],
            'dropshipping_data.cep' => ['required_with:dropshipping_data', 'string', 'max:9'],
            'dropshipping_data.uf' => ['required_with:dropshipping_data', 'string', 'size:2'],
            'dropshipping_data.state' => ['required_with:dropshipping_data', 'string', 'max:30'],
            'dropshipping_data.city' => ['required_with:dropshipping_data', 'string', 'max:255'],
            'dropshipping_data.neighborhood' => ['required_with:dropshipping_data', 'string', 'max:255'],
            'dropshipping_data.public_space' => ['nullable', 'string', 'max:255'],
            'dropshipping_data.number' => ['required_with:dropshipping_data', 'string', 'max:20'],
            'dropshipping_data.complement' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rooms = $this->input('rooms');
        if (! is_array($rooms)) {
            return;
        }

        foreach ($rooms as $roomIndex => $room) {
            if (! is_array($room) || ! isset($room['walls']) || ! is_array($room['walls'])) {
                continue;
            }

            foreach ($room['walls'] as $wallIndex => $wall) {
                if (! is_array($wall)) {
                    continue;
                }

                if (! array_key_exists('model', $wall) || $wall['model'] === '' || $wall['model'] === false) {
                    $rooms[$roomIndex]['walls'][$wallIndex]['model'] = null;
                }
            }
        }

        $this->merge(['rooms' => $rooms]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rooms = $this->input('rooms', []);
            if (! is_array($rooms)) {
                return;
            }

            $modelIds = [];
            foreach ($rooms as $room) {
                foreach (($room['walls'] ?? []) as $wall) {
                    if (! empty($wall['model'])) {
                        $modelIds[] = (int) $wall['model'];
                    }
                }
            }
            $modelIds = array_values(array_unique($modelIds));
            if (! $modelIds) {
                return;
            }

            $models = CollectionModel::query()->whereIn('id', $modelIds)->get()->keyBy('id');

            foreach ($rooms as $roomIndex => $room) {
                foreach (($room['walls'] ?? []) as $wallIndex => $wall) {
                    $modelId = isset($wall['model']) ? (int) $wall['model'] : null;
                    if (! $modelId || ! $models->has($modelId)) {
                        continue;
                    }

                    $model = $models->get($modelId);
                    $needComment = (bool) ($model->request_comment ?? false);
                    $needLink = (bool) ($model->request_link ?? false);
                    $needFile = (bool) ($model->request_file ?? false);
                    $needCollection = (bool) ($model->request_collection ?? false);

                    $base = "rooms.$roomIndex.walls.$wallIndex";

                    if ($needComment && ! trim((string) ($wall['comment_referring_model'] ?? ''))) {
                        $validator->errors()->add("$base.comment_referring_model", 'Descrição do modelo é obrigatória para este modelo.');
                    }

                    if ($needLink && ! trim((string) ($wall['link_referring_model'] ?? ''))) {
                        $validator->errors()->add("$base.link_referring_model", 'Link de referência é obrigatório para este modelo.');
                    }

                    if ($needFile) {
                        $files = $wall['files_referring_model'] ?? [];
                        if (! is_array($files) || count(array_filter($files, fn ($v) => trim((string) $v) !== '')) === 0) {
                            $validator->errors()->add("$base.files_referring_model", 'Arquivo(s) de referência são obrigatórios para este modelo.');
                        }
                    }

                    if ($needCollection && ! trim((string) ($wall['collection_referring_model'] ?? ''))) {
                        $validator->errors()->add("$base.collection_referring_model", 'Seleção de arte da coleção é obrigatória para este modelo.');
                    }
                }
            }
        });
    }
}
