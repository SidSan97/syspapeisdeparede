<?php

namespace App\Http\Controllers;

use App\Http\Requests\TinyErp\TinyErpSettingsRequest;
use App\Http\Resources\TinyErpProductResource;
use App\Models\Setting;
use App\Services\TinyErpService;
use Illuminate\Support\Facades\Cache;

class TinyErpSettingsController extends Controller
{
    public function __construct(
        protected TinyErpService $tinyErpService
    ) {}

    public function index()
    {
        $gtin = Setting::get('tiny_erp_gtin', '');
        $cep = Setting::get('tiny_erp_cep', '');
        $ncm = Setting::get('tiny_erp_ncm', '');

        return response()->json([
            'data' => [
                'gtin' => $gtin,
                'cep' => zip_code_view($cep),
                'ncm' => $ncm,
            ],
        ]);
    }

    public function store(TinyErpSettingsRequest $request)
    {
        $sanitize = fn (?string $value, callable $callback) => $value ? $callback($value) : null;

        $cep = $sanitize($request->input('cep'), fn ($v) => zip_code_db($v));
        $ncm = $sanitize($request->input('ncm'), fn ($v) => preg_replace('/\D/', '', $v));
        $gtin = $request->input('gtin', '');

        Setting::set('tiny_erp_gtin', $gtin, 'string');
        Setting::set('tiny_erp_cep', $cep, 'string');
        Setting::set('tiny_erp_ncm', $ncm, 'string');

        Cache::forget('tiny_erp_settings');
        Cache::forget('tiny_erp_all_data');

        return response()->json([
            'data' => [
                'gtin' => $gtin,
                'cep' => zip_code_view($cep),
                'ncm' => $ncm,
            ],
        ]);
    }

    public function show(string $key)
    {
        return Setting::get($key);
    }

    public function all(): array
    {
        $cacheKey = 'tiny_erp_all_data';
        $cachedData = Cache::get($cacheKey);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $products = $this->tinyErpService->searchProducts();

        if ($products['status'] == 'Erro') {
            return $products;
        }

        $listPrices = $this->tinyErpService->getListPrice();

        if ($listPrices['status'] == 'Erro') {
            return $listPrices;
        }

        $pricePaymentId = intval($listPrices['registros'][1]['registro']['id']);
        $priceInstallmentId = intval($listPrices['registros'][0]['registro']['id']);

        $listPriceExceptionsInstallment = $this->tinyErpService->getListPriceExceptions($pricePaymentId);
        $listPriceExceptions = $this->tinyErpService->getListPriceExceptions($priceInstallmentId);

        $productId = intval($products['produtos'][0]['produto']['id']);

        $product = $this->tinyErpService->getProduct($productId);

        if ($product['status'] == 'Erro') {
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
}
