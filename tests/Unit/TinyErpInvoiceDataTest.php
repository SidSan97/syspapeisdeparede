<?php

namespace Tests\Unit;

use App\Services\TinyErpService;
use Tests\TestCase;

class TinyErpInvoiceDataTest extends TestCase
{
    public function test_make_invoice_data_uses_quantidade_volumes_from_order(): void
    {
        $service = new TinyErpService;

        $data = $service->makeInvoiceData(
            [
                'id' => 15,
                'payment_method' => 'pix',
                'total_amount' => 100,
                'selected_carrier_name' => 'Jadlog - Package',
                'selected_carrier_price' => 20,
                'quantidade_volumes' => 4,
            ],
            [
                'name' => 'Cliente Teste',
                'person_type' => 'PF',
                'email' => 'cliente@example.com',
                'cpf_cnpj' => '00000000000',
                'public_space' => 'Rua Teste',
                'neighborhood' => 'Centro',
                'cep' => '01000-000',
                'city' => 'São Paulo',
                'uf' => 'SP',
            ]
        );

        $this->assertSame(4, $data['nota_fiscal']['quantidade_volumes']);
    }

    public function test_make_invoice_data_defaults_quantidade_volumes_to_one(): void
    {
        $service = new TinyErpService;

        $data = $service->makeInvoiceData(
            [
                'id' => 15,
                'payment_method' => 'pix',
                'total_amount' => 100,
                'selected_carrier_name' => 'Jadlog - Package',
                'selected_carrier_price' => 20,
            ],
            [
                'name' => 'Cliente Teste',
                'person_type' => 'PF',
                'email' => 'cliente@example.com',
                'cpf_cnpj' => '00000000000',
                'public_space' => 'Rua Teste',
                'neighborhood' => 'Centro',
                'cep' => '01000-000',
                'city' => 'São Paulo',
                'uf' => 'SP',
            ]
        );

        $this->assertSame(1, $data['nota_fiscal']['quantidade_volumes']);
    }
}
