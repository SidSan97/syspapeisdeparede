<?php

namespace App\Services\Tiny;

class TinyProductService
{
    public function __construct(
        protected TinyHttpClient $http,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function searchProducts(): array
    {
        $params = [];

        $gtin = config('app.tiny_erp_settings.tiny_erp_gtin')
            ?? config('app.tiny_erp_settings.gtin');

        if (! empty($gtin)) {
            $params['gtin'] = $gtin;
        }

        return $this->http->get('produtos.pesquisa.php', $params);
    }

    /**
     * @return array<string, mixed>
     */
    public function getProduct(int $productId): array
    {
        return $this->http->get('produto.obter.php', ['id' => $productId]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getListPrice(): array
    {
        return $this->http->get('listas.precos.pesquisa.php');
    }

    /**
     * @return array<string, mixed>
     */
    public function getListPriceExceptions(int $listPriceId): array
    {
        return $this->http->get('listas.precos.excecoes.php', [
            'idListaPreco' => $listPriceId,
        ]);
    }
}
