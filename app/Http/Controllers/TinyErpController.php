<?php

namespace App\Http\Controllers;

use App\Http\Resources\TinyErpProductResource;
use App\Services\TinyErpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TinyErpController extends Controller
{
    protected $tinyErpService;

    public function __construct(TinyErpService $tinyErpService)
    {
        $this->tinyErpService = $tinyErpService;
    }

    public function all(): array
    {
        $cacheKey = 'tiny_erp_all_data';
        $cachedData = Cache::get($cacheKey);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $products = $this->tinyErpService->searchProducts();

        if($products['status'] == "Erro") {
            return $products;
        }

        $listPrices = $this->tinyErpService->getListPrice();

        if($listPrices['status'] == "Erro") {
            return $listPrices;
        }

        $pricePaymentId = intval($listPrices['registros'][0]['registro']['id']);
        $priceInstallmentId = intval($listPrices['registros'][1]['registro']['id']);

        $listPriceExceptionsInstallment = $this->tinyErpService->getListPriceExceptions($pricePaymentId);
        $listPriceExceptions = $this->tinyErpService->getListPriceExceptions($priceInstallmentId);

        $productId = intval($products['produtos'][0]['produto']['id']);

        $product = $this->tinyErpService->getProduct($productId);

        if($product['status'] == "Erro") {
            return $product;
        }

        $data = [
            'products' => $products,
            'product' => $product,
            'pricePayment' => $pricePaymentId,
            'priceInstallment' => $priceInstallmentId,
            'pricePaymentExceptions' => $listPriceExceptions,
            'priceInstallmentExceptions' => $listPriceExceptionsInstallment,
        ];

        $response = TinyErpProductResource::makeTinyErpData($data);

        Cache::put($cacheKey, $response, now()->addHours(24));

        return $response;
    }
}
