<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\TinyErpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TinyErpInvoiceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_make_invoice_data_uses_quantidade_volumes_from_order(): void
    {
        $data = $this->app->make(TinyErpService::class)->makeInvoiceData(
            $this->orderPayload(['quantity_volumes' => 4]),
            $this->dropshippingPayload(),
        );

        $this->assertSame(4, $data['nota_fiscal']['quantidade_volumes']);
    }

    public function test_make_invoice_data_defaults_quantidade_volumes_to_one(): void
    {
        $data = $this->app->make(TinyErpService::class)->makeInvoiceData(
            $this->orderPayload(),
            $this->dropshippingPayload(),
        );

        $this->assertSame(1, $data['nota_fiscal']['quantidade_volumes']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderPayload(array $overrides = []): array
    {
        $user = User::factory()->create(['is_dropshipping' => 1]);

        return array_merge([
            'id' => 15,
            'user_id' => $user->id,
            'payment_method' => 'pix',
            'total_amount' => 100,
            'selected_carrier_name' => 'Jadlog - Package',
            'selected_carrier_price' => 20,
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function dropshippingPayload(): array
    {
        return [
            'name' => 'Cliente Teste',
            'person_type' => 'PF',
            'email' => 'cliente@example.com',
            'cpf_cnpj' => '00000000000',
            'public_space' => 'Rua Teste',
            'number' => '100',
            'neighborhood' => 'Centro',
            'cep' => '01000-000',
            'city' => 'São Paulo',
            'uf' => 'SP',
        ];
    }
}
