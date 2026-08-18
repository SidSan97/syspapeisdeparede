<?php

namespace Tests\Unit;

use App\Services\Tiny\TinyCarrierMapper;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TinyCarrierMapperTest extends TestCase
{
    public function test_parse_splits_carrier_name_and_service(): void
    {
        $mapper = new TinyCarrierMapper;

        $this->assertSame(
            ['name' => 'Jadlog', 'service' => 'Package'],
            $mapper->parse('Jadlog - Package')
        );
    }

    public function test_parse_keeps_name_when_service_is_missing(): void
    {
        $mapper = new TinyCarrierMapper;

        $this->assertSame(
            ['name' => 'Correios', 'service' => ''],
            $mapper->parse('Correios')
        );
    }

    public function test_shipping_code_by_origin_maps_known_carriers(): void
    {
        $mapper = new TinyCarrierMapper;

        $this->assertSame('J', $mapper->shippingCodeByOrigin('Jadlog - Package'));
        $this->assertSame('C', $mapper->shippingCodeByOrigin('Correios'));
        $this->assertNull($mapper->shippingCodeByOrigin('Transportadora desconhecida'));
    }

    public function test_carrier_id_reads_cached_tiny_types(): void
    {
        Cache::put('tiny_erp_carriers_types', [
            ['descricao' => 'Jadlog', 'id' => 99],
        ]);

        $mapper = new TinyCarrierMapper;

        $this->assertSame('99', $mapper->carrierId('Jadlog'));
        $this->assertNull($mapper->carrierId('Correios'));
    }
}
