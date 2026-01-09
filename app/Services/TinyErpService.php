<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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

            $gtin = config('app.tiny_erp_settings.gtin');
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

    public function getCarriersTypes()
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/formas.envio.pesquisa.php?' . $queryString;

            $response = $this->client->get($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao buscar tipos de transportadores: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao buscar tipos de transportadores: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sendOrder(array $order, array  $dropshipping)
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'pedido' => $this->makeOrder($order, $dropshipping),
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/pedido.incluir.php?' . $queryString;

            $response = $this->client->post($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao enviar pedido: ' . $e->getMessage());
        }
    }

    public function sendAccountPayable(array $order, array $dropshipping)
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'conta' => $this->makeAccountPayable($order, $dropshipping),
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/conta.pagar.incluir.php?' . $queryString;

            $response = $this->client->post($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao enviar conta a pagar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao enviar conta a pagar: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function searchInvoices(): JsonResponse|array
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'situacao' => 6, // 6 = Emitida
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/notas.fiscais.pesquisa.php?' . $queryString;

            $response = $this->client->get($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao buscar notas fiscais: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao buscar notas fiscais: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function searchGroupings(string $carrier)
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'formaEnvio' => $this->getShippingCodeByOrigin($carrier),
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/expedicao.pesquisar.agrupamentos.php?' . $queryString;

            $response = $this->client->get($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao buscar agrupamentos de notas fiscais: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao buscar agrupamentos de notas fiscais: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sendInvoice(array $order, array $dropshipping)
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'nota' => $this->makeInvoiceData($order, $dropshipping),
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/nota.fiscal.incluir.php?' . $queryString;

            $response = $this->client->post($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao enviar nota fiscal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao enviar nota fiscal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function issueInvoice(array $invoiceData)
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'id' => $invoiceData['nf_id'],
                'numero' => $invoiceData['nf_number'],
                'serie' => $invoiceData['nf_serie'],
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/nota.fiscal.emitir.php?' . $queryString;

            $response = $this->client->get($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao emitir nota fiscal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao emitir nota fiscal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateDanfe(string $id)
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'id' => $id,
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/nota.fiscal.obter.link.php?' . $queryString;

            $response = $this->client->get($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao obter link da DANFE: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao obter link da DANFE: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sendInvoiceToExpedition(string $nfIds, string $typeObjects)
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'idObjetos' => $nfIds,
                'tipoObjetos' => $typeObjects,
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/expedicao.liberar.objetos.php?' . $queryString;

            $response = $this->client->post($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao enviar objeto a expedição: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao enviar objeto a expedição: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function includeGroupingInvoices(int|string $invoicesIds)
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'idsExpedicao' => $invoicesIds
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/expedicao.incluir.agrupamento.php?' . $queryString;

            $response = $this->client->post($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao incluir agrupamento de notas fiscais: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao incluir agrupamento de notas fiscais: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function changeExpedition(string $expeditionId, string $carrier)
    {
        $tinyErpData = Cache::get('tiny_erp_all_data');

        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'expedicao' => $this->makeExpeditionData($carrier, $tinyErpData, $expeditionId),
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/expedicao.alterar.php?' . $queryString;

            $response = $this->client->post($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao alterar expedição: ' . $e->getMessage());
        }
        catch (RequestException $e) {
            Log::error('Erro ao alterar expedição: ' . $e->getMessage());
        }
        catch (GuzzleException $e) {
            Log::error('Erro ao alterar expedição: ' . $e->getMessage());
        }
    }

    public function completeGroupingInvoices(int|string $groupingId)
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'idAgrupamento' => $groupingId,
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/expedicao.concluir.agrupamento.php?' . $queryString;

            $response = $this->client->post($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao concluir agrupamento de notas fiscais: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao concluir agrupamento de notas fiscais: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function printCarrierLabels(string|int $groupingId)
    {
        try {
            $params = [
                'token' => $this->token,
                'formato' => 'json',
                'idAgrupamento' => $groupingId,
            ];

            $queryString = http_build_query($params);
            $url = $this->apiUrl . '/expedicao.obter.etiquetas.impressao.php?' . $queryString;

            $response = $this->client->post($url);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            return $data['retorno'];
        }
        catch (\Exception $e) {
            Log::error('Erro inesperado ao gerar etiquetas de impressão dos agrupamentos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao gerar etiquetas de impressão dos agrupamentos: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function makeOrder(array $order, array  $dropshipping): string
    {
        $dataPedido = Carbon::parse($order['created_at']);
        $dataPrevista = $dataPedido->copy()->addDays($order['delivery_time']);

        $orderData = [
            'pedido' => [
               'data_pedido' => $dataPedido->format('d/m/Y'),
               'data_prevista' => $dataPrevista->format('d/m/Y'),
               'cliente' => $this->makeClientData($dropshipping),
               'itens' => [
                    [
                        'item' => [
                            'codigo' => $order['id'],
                            'descricao' => $order['comment_referring_model'] ?? 'Orçamento para papel de parede',
                            'unidade' => 'UN',
                            'quantidade' => 1,
                            'valor_unitario' => $order['payment_method'] === 'pix' ? $order['total_amount'] : $order['total_amount_installments'],
                        ]
                    ]
                ],
                'nome_transportador' => trim(explode(' - ', $order['selected_carrier_name'])[0]),
                'forma_pagamento' => $order['payment_method'] === 'pix' ? 'pix' : 'credito',
                'frete_por_conta' => 'D',
                'valor_frete' => $order['selected_carrier_price'],
                'numero_ordem_compra' => '',
                'ecommerce' => 'Papel de parede',
                'situacao' => 'aprovado',
                'obs' => trim(explode(' - ', $order['selected_carrier_name'])[1]),
                'forma_envio' => $this->getShippingCodeByOrigin($order['selected_carrier_name']),
                'intermediador' => [
                    'nome' => 'Papel de parede',
                    'cnpj' => '13.023.181/0001-13',
                ]
            ],
        ];

        return json_encode($orderData);
    }

    public function makeAccountPayable(array $order, array $dropshipping)
    {
        $currentDate = Carbon::now();
        $conta = [
            'conta' => [
                'cliente' => $this->makeClientData($dropshipping),
                "data" => $currentDate->format('d/m/Y'),
                "vencimento" => $currentDate->copy()->addDays(3)->format('d/m/Y'),
                "valor" => $order['payment_method'] === 'pix' ? $order['total_amount'] : $order['total_amount_installments'],
                "nro_documento" => "",
                "categoria" => "",
                "ocorrencia" => $order['payment_method'] === 'pix' ? 'U' : 'P',
                "numero_parcelas" => $order['payment_method'] === 'pix' ? 0 : $order['installments'],
            ],
        ];

        return json_encode($conta);
    }

    public function makeInvoiceData(array $order, array $dropshipping)
    {
        $data = [
            'nota_fiscal' => [
                //'data_emissao' => Carbon::now()->format('d/m/Y'),
                "natureza_operacao" => "Venda de Mercadorias",
                //"hora_entrada_saida" => "15:30",
                //"data_entrada_saida" => Carbon::now()->format('d/m/Y'),
                "tipo" => "S",
                'cliente' => $this->makeClientData($dropshipping),
                'endereco_entrega' => $this->makeAddressData($dropshipping),
                "itens" => [
                    [
                        "item" => [
                            "descricao" => $order['comment_referring_model'] ?? 'Orçamento para papel de parede',
                            "valor_unitario" => $order['payment_method'] === 'pix' ? $order['total_amount'] : $order['total_amount_installments'],
                            "gtin_ean" => config('app.tiny_erp_settings.gtin') ?? '',
                            "quantidade" => 1,
                            "unidade" => "UN",
                            "ncm" => config('app.tiny_erp_settings.ncm') ?? "4814.20.00",
                            "tipo" => "P",
                            "origem" => "0"
                        ]
                    ]
                ],
                "transportador" => [
                    "nome" => trim(explode(' - ', $order['selected_carrier_name'])[0])
                ],
                "frete_por_conta" => "D",
                "forma_pagamento" => $order['payment_method'] === 'pix' ? 'pix' : 'multiplas',
                "forma_envio" => $this->getShippingCodeByOrigin($order['selected_carrier_name']),
                "valor_frete" => $order['selected_carrier_price'],
                "finalidade" => "3",
                "obs" => "NF emitida pelo sistema Papel de parede",
                "ecommerce" => "Sistema de Papel de parede",
                "numero_pedido_ecommerce" => $order['id'],
            ],
        ];

        return $data;
    }

    public function makeExpeditionData(string $carrier, array $tinyErpData, string $expeditionId)
    {
        $expeditionData = [
            'expedicao' => [
                'id' => $expeditionId,
                'formaEnvio' => $this->getShippingCodeByOrigin($carrier),
                'qtdVolumes' => 1,
                'pesoBruto' => $tinyErpData['peso_bruto'],
                'possuiValorDeclarado' => 'N',
                'valorDeclarado' => 0,
                'possuiAR' => 'N',
                'embalagem' => [
                    'tipo' => '2',
                    'altura' => $tinyErpData['alturaEmbalagem'],
                    'largura' => $tinyErpData['larguraEmbalagem'],
                    'comprimento' => $tinyErpData['comprimentoEmbalagem'],
                    'diametro' => $tinyErpData['diametroEmbalagem']
                ],
                /*'formaFrete' => [
                    'id' => $this->getCarrierId(trim(explode(' - ', $carrier)[0])),
                    'descricao' => $carrier
                ],*/
                'transportadora' => [
                    'nome' => trim(explode(' - ', $carrier)[0])
                ]
            ],
        ];

        return json_encode($expeditionData);
    }

    public function makeClientData(array $dropshipping): array
    {
        return [
            'nome' => $dropshipping['name'],
            'tipo_pessoa' => $dropshipping['person_type'] === 'PF' ? 'F' : 'J',
            'email' => $dropshipping['email'],
            'cpf_cnpj' => $dropshipping['cpf_cnpj'],
            'ie' => $dropshipping['IE'] ?? '',
            'rg' => $dropshipping['rg'] ?? '',
            'endereco' => $dropshipping['public_space'],
            'numero' => $dropshipping['number'] ?? '',
            'complemento' => $dropshipping['complement'] ?? '',
            'bairro' => $dropshipping['neighborhood'],
            'cep' => $dropshipping['cep'],
            'cidade' => $dropshipping['city'],
            'uf' => $dropshipping['uf'],
            'fone' => $dropshipping['phone'] ?? '',
            'atualizar_cliente' => 'N'
        ];
    }

    public function makeAddressData(array $dropshipping): array
    {
        return [
            'nome_destinatario' => $dropshipping['name'],
            'tipo_pessoa' => $dropshipping['person_type'] === 'PF' ? 'F' : 'J',
            'cpf_cnpj' => $dropshipping['cpf_cnpj'],
            'ie' => $dropshipping['IE'] ?? '',
            'endereco' => $dropshipping['public_space'],
            'numero' => $dropshipping['number'] ?? '',
            'complemento' => $dropshipping['complement'] ?? '',
            'bairro' => $dropshipping['neighborhood'],
            'cep' => $dropshipping['cep'],
            'cidade' => $dropshipping['city'],
            'uf' => $dropshipping['uf'],
            'fone' => $dropshipping['phone'] ?? ''
        ];
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

    public function getCarrierId(string $carrier): ?string
    {
        $carriersTypes = Cache::get('tiny_erp_carriers_types');

        foreach ($carriersTypes as $carrierType) {
            if ($carrierType['descricao'] === $carrier) {
                return $carrierType['id'];
            }
        }

        return null;
    }
}
