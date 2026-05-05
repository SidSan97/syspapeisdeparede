<?php

namespace App\Http\Controllers;

use App\Services\TinyErpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class TinyErpInvoiceController extends Controller
{
    public function __construct(
        protected TinyErpService $tinyErpService
    ) {}

    public function show(int $order)
    {
        $cacheKey = 'tiny_erp_invoice_by_order_id_'.$order;
        $cachedData = Cache::get($cacheKey);

        if ($cachedData !== null) {
            return response()->json([
                'success' => true,
                'data' => $cachedData,
            ]);
        }
        $invoice = $this->tinyErpService->searchInvoices($order);

        if ($invoice instanceof JsonResponse) {
            return $invoice;
        }

        Cache::put($cacheKey, $invoice, now()->addHours(24));

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }
}
