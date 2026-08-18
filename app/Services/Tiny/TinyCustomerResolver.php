<?php

namespace App\Services\Tiny;

use App\Models\Reseller;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class TinyCustomerResolver
{
    /**
     * Resolve a origem dos dados do cliente.
     *
     * is_dropshipping = true: dados do cliente final.
     * is_dropshipping = false: dados do Reseller relacionado ao User.
     *
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>|null  $dropshipping
     * @return array<string, mixed>
     */
    public function resolve(array $order, ?array $dropshipping = null): array
    {
        $userId = $order['user_id'] ?? null;

        if (! $userId) {
            throw new InvalidArgumentException(
                'O pedido não possui user_id para identificar o cliente do Tiny ERP.'
            );
        }

        $user = User::query()
            ->with('reseller')
            ->find($userId);

        if (! $user) {
            throw new RuntimeException(
                "Usuário {$userId} do pedido não encontrado."
            );
        }

        $isDropshipping = (bool) $user->is_dropshipping;

        Log::info('Resolvendo cliente do pedido para o Tiny ERP', [
            'order_id' => $order['id'] ?? null,
            'user_id' => $user->id,
            'is_dropshipping' => $isDropshipping,
            'reseller_id' => $user->reseller?->id,
        ]);

        if ($isDropshipping) {
            if (empty($dropshipping)) {
                throw new InvalidArgumentException(
                    "O usuário {$user->id} está configurado como dropshipping, "
                    .'mas os dados do cliente final não foram informados.'
                );
            }

            $customer = $this->normalizeDropshipping($dropshipping);

            Log::info('Cliente do pedido resolvido como dropshipping', [
                'order_id' => $order['id'] ?? null,
                'user_id' => $user->id,
            ]);

            return $customer;
        }

        $reseller = $user->reseller;

        if (! $reseller) {
            throw new RuntimeException(
                "O usuário {$user->id} não possui revendedor associado."
            );
        }

        Log::info('Cliente do pedido resolvido como revendedor', [
            'order_id' => $order['id'] ?? null,
            'user_id' => $user->id,
            'reseller_id' => $reseller->id,
            'tiny_id' => $reseller->tiny_id,
            'tiny_code' => $reseller->tiny_code,
        ]);

        return $this->normalizeReseller($reseller);
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    public function makeClientData(array $customer): array
    {
        $this->validateCustomer($customer);

        $data = [
            'nome' => $customer['name'],
            'tipo_pessoa' => $this->normalizePersonType($customer['person_type']),
            'email' => $customer['email'] ?? '',
            'cpf_cnpj' => $customer['cpf_cnpj'] ?? '',
            'ie' => $customer['ie'] ?? '',
            'rg' => $customer['rg'] ?? '',
            'endereco' => $customer['public_space'] ?? '',
            'numero' => $customer['number'] ?? '',
            'complemento' => $customer['complement'] ?? '',
            'bairro' => $customer['neighborhood'] ?? '',
            'cep' => $customer['cep'] ?? '',
            'cidade' => $customer['city'] ?? '',
            'uf' => $customer['uf'] ?? '',
            'fone' => $customer['phone'] ?? '',
            'atualizar_cliente' => 'N',
        ];

        if (! empty($customer['code'])) {
            $data['codigo'] = $customer['code'];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    public function makeAddressData(array $customer): array
    {
        $this->validateCustomer($customer);

        return [
            'nome_destinatario' => $customer['name'],
            'tipo_pessoa' => $this->normalizePersonType($customer['person_type']),
            'cpf_cnpj' => $customer['cpf_cnpj'] ?? '',
            'ie' => $customer['ie'] ?? '',
            'endereco' => $customer['public_space'] ?? '',
            'numero' => $customer['number'] ?? '',
            'complemento' => $customer['complement'] ?? '',
            'bairro' => $customer['neighborhood'] ?? '',
            'cep' => $customer['cep'] ?? '',
            'cidade' => $customer['city'] ?? '',
            'uf' => $customer['uf'] ?? '',
            'fone' => $customer['phone'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeReseller(Reseller $reseller): array
    {
        return [
            'tiny_id' => $reseller->tiny_id,
            'code' => $reseller->tiny_code ? (string) $reseller->tiny_code : null,
            'name' => $reseller->name,
            'person_type' => $reseller->person_type,
            'email' => $reseller->email,
            'cpf_cnpj' => $reseller->cnpj,
            'ie' => $reseller->ie,
            'rg' => null,
            'public_space' => $reseller->public_space,
            'number' => $reseller->number,
            'complement' => $reseller->complement,
            'neighborhood' => $reseller->neighborhood,
            'cep' => $reseller->cep,
            'city' => $reseller->city,
            'uf' => $reseller->uf,
            'phone' => $reseller->phone,
        ];
    }

    /**
     * @param  array<string, mixed>  $dropshipping
     * @return array<string, mixed>
     */
    private function normalizeDropshipping(array $dropshipping): array
    {
        $customer = [
            'tiny_id' => null,
            'code' => null,
            'name' => $dropshipping['name'] ?? null,
            'person_type' => $dropshipping['person_type'] ?? null,
            'email' => $dropshipping['email'] ?? null,
            'cpf_cnpj' => $dropshipping['cpf_cnpj']
                ?? $dropshipping['cnpj']
                ?? $dropshipping['cpf']
                ?? null,
            'ie' => $dropshipping['ie'] ?? $dropshipping['IE'] ?? null,
            'rg' => $dropshipping['rg'] ?? null,
            'public_space' => $dropshipping['public_space'] ?? null,
            'number' => $dropshipping['number'] ?? null,
            'complement' => $dropshipping['complement'] ?? null,
            'neighborhood' => $dropshipping['neighborhood'] ?? null,
            'cep' => $dropshipping['cep'] ?? null,
            'city' => $dropshipping['city'] ?? null,
            'uf' => $dropshipping['uf'] ?? null,
            'phone' => $dropshipping['phone'] ?? null,
        ];

        $this->validateDropshipping($customer);

        return $customer;
    }

    /**
     * @param  array<string, mixed>  $customer
     */
    private function validateCustomer(array $customer): void
    {
        if (empty($customer['name'])) {
            throw new InvalidArgumentException(
                'Nome do cliente não informado para integração com o Tiny ERP.'
            );
        }

        if (empty($customer['person_type'])) {
            throw new InvalidArgumentException(
                'Tipo de pessoa do cliente não informado para integração com o Tiny ERP.'
            );
        }

        if (empty($customer['cpf_cnpj'])) {
            throw new InvalidArgumentException(
                'CPF/CNPJ do cliente não informado para integração com o Tiny ERP.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $customer
     */
    private function validateDropshipping(array $customer): void
    {
        $requiredFields = [
            'name',
            'person_type',
            'cpf_cnpj',
            'public_space',
            'number',
            'neighborhood',
            'cep',
            'city',
            'uf',
        ];

        foreach ($requiredFields as $field) {
            $value = $customer[$field] ?? null;

            if ($value === null || trim((string) $value) === '') {
                throw new InvalidArgumentException(
                    "Campo obrigatório do cliente dropshipping não informado: {$field}"
                );
            }
        }
    }

    private function normalizePersonType(?string $personType): string
    {
        $personType = strtoupper(trim((string) $personType));

        return match ($personType) {
            'PF', 'F' => 'F',
            'PJ', 'J' => 'J',
            'E' => 'E',
            default => throw new InvalidArgumentException(
                "Tipo de pessoa inválido para o Tiny ERP: {$personType}"
            ),
        };
    }
}
