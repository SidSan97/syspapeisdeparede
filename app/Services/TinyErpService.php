<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class TinyErpService
{
    protected $client;
    protected $token;
    protected $apiUrl;

    public function __construct()
    {
        $this->client = new Client([
            'cookies' => true,
            'timeout' => 30,
        ]);
        $this->token = env('TINY_ERP_TOKEN');
        $this->apiUrl = env('TINY_ERP_API_URL');
    }

    public function searchProducts(): JsonResponse|array
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
            ];

            $gtin = env('TINY_ERP_PRODUCT_GTIN');
            if (!empty($gtin)) {
                $params['gtin'] = $gtin;
            }

            // Remove valores nulos e vazios antes de construir a query string
            $params = array_filter($params, function($value) {
                return $value !== null && $value !== '';
            });

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/produtos.pesquisa.php?' . $queryString;

            $response = $this->client->get($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];

        } catch (\Exception $e) {
            Log::error('Erro inesperado ao buscar produtos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao buscar produtos: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getProduct(int $productId): JsonResponse|array
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'id' => $productId,
            ];

            $params = array_filter($params, function($value) {
                return $value !== null && $value !== '';
            });

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/produto.obter.php?' . $queryString;

            $response = $this->client->get($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao buscar produto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao buscar produto: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getListPrice(): JsonResponse|array
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
            ];

            $params = array_filter($params, function($value) {
                return $value !== null && $value !== '';
            });

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/listas.precos.pesquisa.php?' . $queryString;

            $response = $this->client->get($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao buscar lista de preços: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao buscar lista de preços: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getListPriceExceptions(int $listPriceId): JsonResponse|array
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'idListaPreco' => $listPriceId,
            ];

            $params = array_filter($params, function($value) {
                return $value !== null && $value !== '';
            });

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/listas.precos.excecoes.php?' . $queryString;

            $response = $this->client->get($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao buscar lista de preços: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao buscar lista de preços: ' . $e->getMessage(),
            ], 500);
        }
    }
}
