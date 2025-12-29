<?php

namespace App\Repositories;

use App\Models\DropshippingData;

class DropshippingRepository
{
    protected $dropshippingData;

    public function __construct(DropshippingData $dropshippingData)
    {
        $this->dropshippingData = $dropshippingData;
    }

    public function create(array $data, int $budgetId, int $dealerId)
    {
        return $this->dropshippingData->create([
            'budget_id' => $budgetId,
            'dealer_id' => $dealerId,
            'name' => $data['name'] ?? '',
            'person_type' => $data['person_type'] ?? '',
            'cpf_cnpj' => $data['cpf_cnpj'] ?? '',
            'IE' => $data['IE'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'cep' => $data['cep'] ?? '',
            'uf' => $data['uf'] ?? '',
            'state' => $data['state'] ?? '',
            'city' => $data['city'] ?? '',
            'neighborhood' => $data['neighborhood'] ?? '',
            'public_space' => $data['public_space'] ?? null,
            'number' => $data['number'] ?? '',
            'complement' => $data['complement'] ?? null,
        ]);
    }

    public function update(array $data, int $dropshippingDataId)
    {
        $dropshippingData = $this->dropshippingData->findOrFail($dropshippingDataId);

        $dropshippingData->update([
            'name' => $data['name'] ?? $dropshippingData->name,
            'person_type' => $data['person_type'] ?? $dropshippingData->person_type,
            'cpf_cnpj' => $data['cpf_cnpj'] ?? $dropshippingData->cpf_cnpj,
            'IE' => $data['IE'] ?? $dropshippingData->IE,
            'email' => $data['email'] ?? $dropshippingData->email,
            'phone' => $data['phone'] ?? $dropshippingData->phone,
            'cep' => $data['cep'] ?? $dropshippingData->cep,
            'uf' => $data['uf'] ?? $dropshippingData->uf,
            'state' => $data['state'] ?? $dropshippingData->state,
            'city' => $data['city'] ?? $dropshippingData->city,
            'neighborhood' => $data['neighborhood'] ?? $dropshippingData->neighborhood,
            'public_space' => $data['public_space'] ?? $dropshippingData->public_space,
            'number' => $data['number'] ?? $dropshippingData->number,
            'complement' => $data['complement'] ?? $dropshippingData->complement,
        ]);

        return $dropshippingData->fresh();
    }

    public function updateOrderId(int $budgetId, int $orderId)
    {
        $dropshippingData = $this->dropshippingData::where('budget_id', $budgetId)->first();

        if (!$dropshippingData) {
            throw new \Exception('Orçamento não encontrado');
        }

        $dropshippingData->update([
            'order_id' => $orderId,
        ]);

        return $dropshippingData->fresh();
    }

    public function findDropshippingByOrderId(int $orderId): DropshippingData
    {
        $dropshippingData = $this->dropshippingData->where('order_id', $orderId)->first();
        if (!$dropshippingData) {
            throw new \Exception('Dados de dropshipping não encontrado');
        }

        return $dropshippingData;
    }
}
