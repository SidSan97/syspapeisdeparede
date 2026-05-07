<?php

namespace App\Http\Requests\Orders;

use App\Models\CollectionModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'tenant_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'primary_budget_room_id' => ['sometimes', 'nullable', 'exists:budget_rooms,id'],
            'total_area' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'total_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'total_amount_installments' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'delivery_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'payment_method' => ['sometimes', 'nullable', 'string'],
            'installment_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'installments' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'cep' => ['sometimes', 'nullable', 'string', 'max:9'],
            'selected_carrier_name' => ['sometimes', 'nullable', 'string'],
            'selected_carrier_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'selected_carrier_delivery_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'carriers_snapshot' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in([
                'em aberto',
                'Em aberto',
                'aprovado',
                'Aprovado',
                'cancelado',
                'Cancelado',
                'Em produção',
                'Enviado',
            ])],
            'payment_file' => ['sometimes', 'nullable', 'string'],
            'comment_referring_model' => ['sometimes', 'nullable', 'string', 'max:500'],
            'link_referring_model' => ['sometimes', 'nullable', 'string', 'max:150'],
            'files_referring_model' => ['sometimes', 'nullable', 'array'],
            'collection_referring_model' => ['sometimes', 'nullable', 'string'],
            'dropshipping_budget' => ['sometimes', 'nullable', 'boolean'],
            'dropshipping_data' => ['sometimes', 'nullable', 'array'],
            'selectedCarrier' => ['sometimes', 'nullable'],
            'carriers' => ['sometimes', 'nullable', 'array'],
            'rooms' => ['sometimes', 'nullable', 'array'],
            'rooms.*.name' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls' => ['nullable', 'array'],
            'rooms.*.walls.*.name' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls.*.width' => ['nullable', 'numeric', 'min:0.01'],
            'rooms.*.walls.*.height' => ['nullable', 'numeric', 'min:0.01'],
            'rooms.*.walls.*.model' => ['nullable', 'integer', 'exists:collection_models,id'],
            'rooms.*.walls.*.comment_referring_model' => ['nullable', 'string', 'max:500'],
            'rooms.*.walls.*.link_referring_model' => ['nullable', 'string', 'url', 'max:500'],
            'rooms.*.walls.*.files_referring_model' => ['nullable', 'array'],
            'rooms.*.walls.*.files_referring_model.*' => ['nullable', 'string', 'max:2048'],
            'rooms.*.walls.*.collection_referring_model' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls.*.continueSameArt' => ['nullable', 'boolean'],
            'rooms.*.walls.*.continuations' => ['nullable', 'array'],
            'rooms.*.walls.*.continuations.*.fit' => [
                'nullable',
                'string',
                Rule::in(['Inicial', 'Superior', 'Inferior', 'Central']),
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        // Processar selectedCarrier se vier como objeto
        if ($this->has('selectedCarrier') && is_array($this->input('selectedCarrier'))) {
            $carrier = $this->input('selectedCarrier');
            $data['selected_carrier_name'] = $carrier['name'] ?? null;
            $data['selected_carrier_price'] = isset($carrier['price']) ? (float) $carrier['price'] : null;
            $data['selected_carrier_delivery_time'] = isset($carrier['deliveryTime']) ? (int) $carrier['deliveryTime'] : null;
        }

        // Processar carriers_snapshot se não vier mas houver carriers no request
        if (!$this->has('carriers_snapshot') && $this->has('carriers') && is_array($this->input('carriers'))) {
            $data['carriers_snapshot'] = $this->input('carriers');
        }

        // Processar payment_method se vier como 'pix' ou 'credit_card'
        if ($this->has('payment_method')) {
            $paymentMethod = $this->input('payment_method');
            if ($paymentMethod === 'credit_card') {
                $data['payment_method'] = 'installment';
            } elseif ($paymentMethod === 'pix') {
                $data['payment_method'] = 'pix';
            }
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Get the validated data with processed values.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Remover selectedCarrier e carriers do array validado (já foram processados)
        unset($validated['selectedCarrier'], $validated['carriers']);

        return $validated;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rooms = $this->input('rooms', []);
            if (!is_array($rooms) || !$rooms) {
                return;
            }

            $modelIds = [];
            foreach ($rooms as $room) {
                foreach (($room['walls'] ?? []) as $wall) {
                    if (!empty($wall['model'])) {
                        $modelIds[] = (int) $wall['model'];
                    }
                }
            }
            $modelIds = array_values(array_unique($modelIds));
            if (!$modelIds) {
                return;
            }

            $models = CollectionModel::query()->whereIn('id', $modelIds)->get()->keyBy('id');

            foreach ($rooms as $roomIndex => $room) {
                foreach (($room['walls'] ?? []) as $wallIndex => $wall) {
                    $modelId = isset($wall['model']) ? (int) $wall['model'] : null;
                    if (!$modelId || !$models->has($modelId)) {
                        continue;
                    }

                    $model = $models->get($modelId);
                    $needComment = (bool) ($model->request_comment ?? false);
                    $needLink = (bool) ($model->request_link ?? false);
                    $needFile = (bool) ($model->request_file ?? false);
                    $needCollection = (bool) ($model->request_collection ?? false);

                    $base = "rooms.$roomIndex.walls.$wallIndex";

                    if ($needComment && !trim((string) ($wall['comment_referring_model'] ?? ''))) {
                        $validator->errors()->add("$base.comment_referring_model", 'Descrição do modelo é obrigatória para este modelo.');
                    }

                    if ($needLink && !trim((string) ($wall['link_referring_model'] ?? ''))) {
                        $validator->errors()->add("$base.link_referring_model", 'Link de referência é obrigatório para este modelo.');
                    }

                    if ($needFile) {
                        $files = $wall['files_referring_model'] ?? [];
                        if (!is_array($files) || count(array_filter($files, fn ($v) => trim((string) $v) !== '')) === 0) {
                            $validator->errors()->add("$base.files_referring_model", 'Arquivo(s) de referência são obrigatórios para este modelo.');
                        }
                    }

                    if ($needCollection && !trim((string) ($wall['collection_referring_model'] ?? ''))) {
                        $validator->errors()->add("$base.collection_referring_model", 'Seleção de arte da coleção é obrigatória para este modelo.');
                    }
                }
            }
        });
    }
}

