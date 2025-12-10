<?php

namespace App\Http\Controllers;

use App\Http\Resources\TinyErpProductResource;
use App\Services\TinyErpService;
use Illuminate\Http\Request;

class TinyErpController extends Controller
{
    protected $tinyErpService;

    public function __construct(TinyErpService $tinyErpService)
    {
        $this->tinyErpService = $tinyErpService;
    }

    public function all()
    {
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

        $listPriceExceptions = $this->tinyErpService->getListPriceExceptions($pricePaymentId);
        $listPriceExceptionsInstallment = $this->tinyErpService->getListPriceExceptions($priceInstallmentId);

        $productId = intval($products['produtos'][0]['produto']['id']);

        $product = $this->tinyErpService->getProduct($productId);

        $data = [
            'products' => $products,
            'product' => $product,
            'pricePayment' => $pricePaymentId,
            'priceInstallment' => $priceInstallmentId,
            'pricePaymentExceptions' => $listPriceExceptions,
            'priceInstallmentExceptions' => $listPriceExceptionsInstallment,
        ];

        $a = new TinyErpProductResource($data);
        dd($a);
    }
}
