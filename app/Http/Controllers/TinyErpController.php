<?php

namespace App\Http\Controllers;

use App\Http\Resources\TinyErpProductResource;
use App\Http\Requests\TinyErp\TinyErpSettingsRequest;
use App\Services\TinyErpService;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
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

        $pricePaymentId = intval($listPrices['registros'][1]['registro']['id']);
        $priceInstallmentId = intval($listPrices['registros'][0]['registro']['id']);

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

        Setting::set('tiny_erp_price_payment', $response['precoPromocionalVista'], 'float');
        Setting::set('tiny_erp_price_installment', $response['precoPromocionalPrazo'], 'float');

        Cache::put($cacheKey, $response, now()->addHours(24));

        return $response;
    }

    public function loadSettings()
    {
        $gtin = Setting::get('tiny_erp_gtin', '');
        $cep = Setting::get('tiny_erp_cep', '');
        $ncm = Setting::get('tiny_erp_ncm', '');

        return response()->json([
            'success' => true,
            'data' => [
                'gtin' => $gtin,
                'cep' => $cep,
                'ncm' => $ncm,
            ],
            'message' => 'Configurações do Tiny ERP',
        ], 200);

    }

    public function store(TinyErpSettingsRequest $request)
    {
        $cep = $request->input('cep');
        if ($cep) {
            $cep = preg_replace('/\D/', '', $cep);
        }

        Setting::set('tiny_erp_gtin', $request->input('gtin', ''), 'string');
        Setting::set('tiny_erp_cep', $cep, 'string');
        Setting::set('tiny_erp_ncm', $request->input('ncm', ''), 'string');

        Cache::forget('tiny_erp_settings');
        Cache::forget('tiny_erp_all_data');

        return response()->json([
            'success' => true,
            'data' => [
                'gtin' => $request->input('gtin', ''),
                'cep' => $cep,
                'ncm' => $request->input('ncm', ''),
            ],
            'message' => 'Configurações salvas com sucesso',
        ], 200);
    }

    public function loadCarriersTypes()
    {
        try {
            $cachedData = Cache::get('tiny_erp_carriers_types');

            if($cachedData !== null) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedData,
                    'message' => 'Tipos de transportadores',
                ], 200);
            }

            $carriersTypes = $this->tinyErpService->getCarriersTypes();

            if($carriersTypes['status'] == "Erro") {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao carregar tipos de transportadores',
                    'error' => $carriersTypes['erros'],
                ], 500);
            }

            Cache::put('tiny_erp_carriers_types', $carriersTypes['registros'], now()->addHours(24));

            return response()->json([
                'success' => true,
                'data' => $carriersTypes['registros'],
                'message' => 'Tipos de transportadores',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar tipos de transportadores',
            ], 500);
        }
    }

    public function getInvoiceByOrderId(int $orderId)
    {
        $invoice = $this->tinyErpService->searchInvoices($orderId);

        if ($invoice instanceof JsonResponse) {
            return $invoice;
        }

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ], 200);
    }
}
