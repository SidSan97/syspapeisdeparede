<?php

namespace Tests\Feature;

use App\Models\Reseller;
use App\Models\User;
use App\Services\Tiny\TinyClientService;
use App\Services\TinyErpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TinyErpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.tiny_erp.token' => 'test-token',
            'services.tiny_erp.api_url' => 'https://tiny.test/api',
        ]);
    }

    public function test_search_products_returns_tiny_retorno(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://tiny.test/api/*' => Http::response([
                'retorno' => ['status' => 'OK', 'produtos' => [['produto' => ['id' => 1]]]],
            ]),
        ]);

        $result = $this->tiny()->searchProducts();

        $this->assertSame('OK', $result['status']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'produtos.pesquisa.php')
            && $request->method() === 'GET');
    }

    public function test_get_product_and_price_lists_hit_expected_endpoints(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://tiny.test/api/*' => Http::response(['retorno' => ['status' => 'OK']]),
        ]);

        $this->tiny()->getProduct(10);
        $this->tiny()->getListPrice();
        $this->tiny()->getListPriceExceptions(3);
        $this->tiny()->getCarriersTypes();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'produto.obter.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'listas.precos.pesquisa.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'listas.precos.excecoes.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'formas.envio.pesquisa.php'));
    }

    public function test_send_order_posts_pedido_incluir(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://tiny.test/api/*' => Http::response([
                'retorno' => ['status' => 'OK', 'registros' => ['registro' => ['id' => 55]]],
            ]),
        ]);

        $result = $this->tiny()->sendOrder(
            $this->dropshippingOrder(),
            $this->dropshippingCustomer(),
        );

        $this->assertSame('OK', $result['status']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'pedido.incluir.php')
            && $request->method() === 'POST');
    }

    public function test_send_invoice_and_account_payable_post_expected_endpoints(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://tiny.test/api/*' => Http::response(['retorno' => ['status' => 'OK']]),
        ]);

        $order = $this->dropshippingOrder();
        $customer = $this->dropshippingCustomer();

        $this->tiny()->sendInvoice($order, $customer);
        $this->tiny()->sendAccountPayable($order, $customer);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'nota.fiscal.incluir.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'conta.pagar.incluir.php'));
    }

    public function test_search_groupings_uses_mapped_shipping_code(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://tiny.test/api/*' => Http::response(['retorno' => ['status' => 'OK']]),
        ]);

        $this->tiny()->searchGroupings('Jadlog - Package');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'expedicao.pesquisar.agrupamentos.php')
                && str_contains($request->url(), 'formaEnvio=J');
        });
    }

    public function test_expedition_actions_hit_expected_endpoints(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://tiny.test/api/*' => Http::response(['retorno' => ['status' => 'OK']]),
        ]);

        $this->tiny()->sendInvoiceToExpedition('1,2', 'notafiscal');
        $this->tiny()->includeGroupingInvoices(9);
        $this->tiny()->completeGroupingInvoices(9);
        $this->tiny()->printCarrierLabels(9);
        $this->tiny()->issueInvoice([
            'nf_id' => 1,
            'nf_number' => '100',
            'nf_serie' => '1',
        ]);
        $this->tiny()->generateDanfe('1');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'expedicao.liberar.objetos.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'expedicao.incluir.agrupamento.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'expedicao.concluir.agrupamento.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'expedicao.obter.etiquetas.impressao.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'nota.fiscal.emitir.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'nota.fiscal.obter.link.php'));
    }

    public function test_http_failure_returns_json_error_from_facade(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://tiny.test/api/*' => Http::response('erro', 500),
        ]);

        $result = $this->tiny()->searchProducts();

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertSame(500, $result->getStatusCode());
        $this->assertFalse($result->getData(true)['success']);
    }

    public function test_make_order_uses_reseller_data_when_user_is_not_dropshipping(): void
    {
        $reseller = Reseller::factory()->create([
            'name' => 'Revenda Teste',
            'tiny_code' => 'REV-1',
            'person_type' => 'J',
            'cnpj' => '12345678000199',
        ]);
        $user = User::factory()->for($reseller)->create(['is_dropshipping' => 0]);

        $json = $this->tiny()->makeOrder([
            'id' => 22,
            'user_id' => $user->id,
            'created_at' => now()->toDateTimeString(),
            'delivery_time' => 2,
            'payment_method' => 'pix',
            'total_amount' => 150,
            'selected_carrier_name' => 'Jadlog - Package',
            'selected_carrier_price' => 10,
        ]);

        $payload = json_decode($json, true);

        $this->assertSame('Revenda Teste', $payload['pedido']['cliente']['nome']);
        $this->assertSame('REV-1', $payload['pedido']['cliente']['codigo']);
        $this->assertSame('J', $payload['pedido']['forma_envio']);
    }

    public function test_tiny_client_service_paginates_contacts(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://tiny.test/api/contatos.pesquisa.php*' => Http::sequence()
                ->push(['retorno' => [
                    'status' => 'OK',
                    'numero_paginas' => 2,
                    'contatos' => [['contato' => ['id' => 1, 'nome' => 'A']]],
                ]])
                ->push(['retorno' => [
                    'status' => 'OK',
                    'numero_paginas' => 2,
                    'contatos' => [['contato' => ['id' => 2, 'nome' => 'B']]],
                ]]),
        ]);

        $contacts = $this->app->make(TinyClientService::class)->getAllContacts();

        $this->assertCount(2, $contacts);
        $this->assertSame('A', $contacts[0]['nome']);
        $this->assertSame('B', $contacts[1]['nome']);
    }

    private function tiny(): TinyErpService
    {
        return $this->app->make(TinyErpService::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function dropshippingOrder(): array
    {
        $user = User::factory()->create(['is_dropshipping' => 1]);

        return [
            'id' => 15,
            'user_id' => $user->id,
            'created_at' => now()->toDateTimeString(),
            'delivery_time' => 3,
            'payment_method' => 'pix',
            'total_amount' => 100,
            'selected_carrier_name' => 'Jadlog - Package',
            'selected_carrier_price' => 20,
            'quantity_volumes' => 2,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dropshippingCustomer(): array
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
