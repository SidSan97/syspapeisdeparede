<?php

namespace App\Http\Controllers;

use App\Services\TinyErpService;
use Illuminate\Support\Facades\Cache;

class TinyErpCarrierController extends Controller
{
    public function __construct(
        protected TinyErpService $tinyErpService
    ) {}

    public function index()
    {
        try {
            $cachedData = Cache::get('tiny_erp_carriers_types');

            if ($cachedData !== null) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedData,
                    'message' => 'Tipos de transportadores',
                ], 200);
            }

            $carriersTypes = $this->tinyErpService->getCarriersTypes();

            if ($carriersTypes['status'] == 'Erro') {
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
}
