<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
}

