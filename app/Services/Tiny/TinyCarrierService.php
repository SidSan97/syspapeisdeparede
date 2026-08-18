<?php

namespace App\Services\Tiny;

class TinyCarrierService
{
    public function __construct(
        protected TinyHttpClient $http,
        protected TinyCarrierMapper $mapper,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getCarriersTypes(): array
    {
        return $this->http->get('formas.envio.pesquisa.php');
    }

    public function shippingCodeByOrigin(string $origem): ?string
    {
        return $this->mapper->shippingCodeByOrigin($origem);
    }

    public function carrierId(string $carrier): ?string
    {
        return $this->mapper->carrierId($carrier);
    }
}
