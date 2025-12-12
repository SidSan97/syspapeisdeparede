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

    /**
     * Retorna o código da transportadora baseado no nome da origem
     * 
     * @param string $origem Nome da origem/transportadora
     * @return string|null Código da transportadora ou null se não encontrado
     * @reference link: https://tiny.com.br/api-docs/api2-pedidos-incluir
     */
    public function getShippingCodeByOrigin(string $origem): ?string
    {
        $origem = trim($origem);
        
        // Extrair apenas o nome antes do hífen (ex: "Correios - PAC" -> "Correios")
        if (strpos($origem, ' - ') !== false) {
            $origem = trim(explode(' - ', $origem)[0]);
        }
        
        $origemToCode = [
            'Correios' => 'C',
            'Transportadora' => 'T',
            'Mercado Envios' => 'M',
            'Correios E-fulfillment' => 'E',
            'B2W Entrega' => 'B',
            'Customizada' => 'X',
            'Conectalá Etiquetas' => 'D',
            'Jadlog' => 'J',
            'Sem Frete' => 'S',
            'Total Express' => 'TOTALEXPRESS',
            'Gateway logistico' => 'GATEWAY',
            'Magalu Entregas' => 'MAGALU_ENTREGAS',
            'Magalu Fulfillment' => 'MAGALU_FULFILLMENT',
            'Shopee Envios' => 'SHOPEE_ENVIOS',
            'Netshoes Entregas' => 'NS_ENTREGAS',
            'Via Varejo Envvias' => 'VIAVAREJO_ENVVIAS',
            'AliExpress Envios' => 'ALI_ENVIOS',
            'Madeira Envios' => 'MADEIRA_ENVIOS',
            'Loggi' => 'LOGGI',
            'Amazon DBA' => 'AMAZON_DBA',
            'Magalu Entregas por Netshoes' => 'NS_MAGALU_ENTREGAS',
            'Olist' => 'OLIST',
        ];

        // Buscar o código correspondente (case-insensitive)
        foreach ($origemToCode as $nomeOrigem => $codigo) {
            if (strcasecmp($origem, $nomeOrigem) === 0) {
                return $codigo;
            }
        }

        return null;
    }
}
