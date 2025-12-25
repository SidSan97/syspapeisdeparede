<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento #{{ $budget->id }}</title>
    <style>
        @page {
            margin: 20mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #000;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Cabeçalho */
        .pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #000;
        }

        .pdf-logo {
            flex: 0 0 150px;
        }

        .logo-placeholder {
            width: 120px;
            height: 80px;
            border: 2px solid #000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #000;
            font-size: 12px;
            background: #fff;
        }

        .pdf-header-right {
            flex: 1;
            text-align: right;
        }

        .pdf-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 5px 0;
            color: #000;
        }

        .pdf-subtitle {
            font-size: 14px;
            color: #000;
            margin: 0;
        }

        /* Seção de Informações */
        .pdf-info-section {
            width: 100%;
            margin-bottom: 30px;
        }

        .pdf-info-section-wrapper {
            width: 100%;
            display: table;
            table-layout: fixed;
        }

        .pdf-info-table-wrapper {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding-right: 15px;
        }

        .pdf-info-table-wrapper:last-child {
            padding-right: 0;
            padding-left: 15px;
        }

        .pdf-info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            font-size: 12px;
        }

        .pdf-info-table td {
            padding: 9px 0 0 5px;
            border: 1px solid #000;
            vertical-align: top;
        }

        .pdf-info-label {
            background: #f0f0f0;
            font-weight: bold;
            color: #000;
        }

        .pdf-info-value {
            background: #ffffff;
            color: #000;
        }

        .pdf-info-value div {
            margin: 0;
            line-height: 1.4;
        }

        /* Tabela de Itens */
        .pdf-items-section {
            margin-bottom: 30px;
        }

        .pdf-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            color: #232222;
        }

        .pdf-items-table thead {
            background: #ffffff;
        }

        .pdf-items-table th {
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #000;
            color: #000;
            font-weight: bold;
            font-size: 11px;
        }

        .pdf-items-table td {
            padding: 8px;
            border: 1px solid #000;
            vertical-align: top;
            font-size: 12px;
        }

        .wall-details {
            font-size: 12px;
            color: #000;
            margin-top: 5px;
        }

        /* Resumo */
        .pdf-summary-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #000;
            color: #000;
        }

        .pdf-summary-left p {
            margin: 8px 0;
            font-size: 13px;
            color: #000;
        }

        .pdf-summary-left p strong {
            color: #000;
        }

        .pdf-total-cash {
            font-size: 15px !important;
            font-weight: bold;
            margin-top: 12px !important;
            padding-top: 12px;
            border-top: 2px solid #000;
            color: #000;
        }

        .pdf-total-installment {
            font-size: 15px !important;
            font-weight: bold;
            margin-top: 8px !important;
            color: #000;
        }

        .pdf-mockup-info {
            font-size: 13px !important;
            margin-top: 8px !important;
            color: #000;
        }

        /* Entrega */
        .pdf-shipping-section {
            width: 100%;
            margin-bottom: 30px;
        }

        .pdf-shipping-section-wrapper {
            width: 100%;
            display: table;
            table-layout: fixed;
        }

        .pdf-shipping-group-wrapper {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }

        .pdf-shipping-group-wrapper:last-child {
            padding-right: 0;
            padding-left: 20px;
        }

        .pdf-shipping-section .pdf-info-group {
            color: #1b1b1b;
        }

        .pdf-info-group strong {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .pdf-info-group p {
            margin: 0;
            font-size: 12px;
        }

        /* Observações */
        .pdf-observations-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #000;
        }

        .pdf-observations-section strong {
            display: block;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .pdf-observations-section p {
            font-size: 11px;
            color: #000;
            line-height: 1.5;
            margin: 0;
        }
    </style>
</head>
@php
    $dropshippingData = $budget->dropshippingData;
    
    // Função para formatar telefone
    function formatPhone($phone) {
        if (!$phone) return '—';
        $cleaned = preg_replace('/\D/', '', $phone);
        if (strlen($cleaned) === 10) {
            return '(' . substr($cleaned, 0, 2) . ') ' . substr($cleaned, 2, 4) . '-' . substr($cleaned, 6);
        }
        if (strlen($cleaned) === 11) {
            return '(' . substr($cleaned, 0, 2) . ') ' . substr($cleaned, 2, 5) . '-' . substr($cleaned, 7);
        }
        return $phone;
    }
    
    // Função para formatar endereço linha 1
    function formatAddressLine1($dropshipping) {
        if (!$dropshipping) return '—';
        $parts = [];
        if ($dropshipping->public_space) {
            $parts[] = $dropshipping->public_space;
        }
        if ($dropshipping->number) {
            $parts[] = 'Nº ' . $dropshipping->number;
        }
        if ($dropshipping->complement) {
            $parts[] = $dropshipping->complement;
        }
        if ($dropshipping->neighborhood) {
            $parts[] = 'Bairro: ' . $dropshipping->neighborhood;
        }
        return count($parts) > 0 ? implode('. ', $parts) : '—';
    }
    
    // Função para formatar endereço linha 2
    function formatAddressLine2($dropshipping) {
        if (!$dropshipping) return '';
        $parts = [];
        if ($dropshipping->cep) {
            $cep = preg_replace('/\D/', '', $dropshipping->cep);
            $formattedCep = strlen($cep) === 8 ? substr($cep, 0, 5) . '-' . substr($cep, 5) : $dropshipping->cep;
            $parts[] = $formattedCep;
        }
        if ($dropshipping->city && $dropshipping->uf) {
            $parts[] = $dropshipping->city . ', ' . $dropshipping->uf;
        }
        return count($parts) > 0 ? implode(' - ', $parts) : '';
    }
    
    // Função para formatar data
    function formatDate($date) {
        if (!$date) return '—';
        try {
            return \Carbon\Carbon::parse($date)->format('d/m/Y');
        } catch (\Exception $e) {
            return '—';
        }
    }
    
    // Função para formatar data prevista
    function formatEstimatedDate($deliveryTime) {
        if (!$deliveryTime) return '—';
        try {
            $estimated = \Carbon\Carbon::now()->addDays((int)$deliveryTime);
            return $estimated->format('d/m/Y');
        } catch (\Exception $e) {
            return '—';
        }
    }
    
    // Função para formatar tempo de entrega
    function formatDeliveryTime($deliveryTime) {
        if (!$deliveryTime) return 'Não informado';
        $days = (int)$deliveryTime;
        return $days . ' ' . ($days === 1 ? 'dia' : 'dias');
    }
    
    // Função para formatar moeda
    function formatCurrency($value) {
        if ($value === null) {
            return 'R$ 0,00';
        }
        $numValue = (float)$value;
        if (is_nan($numValue)) {
            return 'R$ 0,00';
        }
        return 'R$ ' . number_format($numValue, 2, ',', '.');
    }
    
    // Função para obter nome da transportadora
    function getCarrierName($carrierName) {
        if (!$carrierName) return null;
        if (strpos($carrierName, ' - ') !== false) {
            return trim(explode(' - ', $carrierName)[0]);
        }
        return trim($carrierName);
    }
    
    // Função para formatar detalhes da parede
    function formatWallDetails($wall) {
        $parts = [];
        if ($wall->name) {
            $parts[] = $wall->name;
        }
        if ($wall->width && $wall->height) {
            $parts[] = number_format($wall->width, 2, ',', '.') . 'm x ' . number_format($wall->height, 2, ',', '.') . 'm';
        }
        return count($parts) > 0 ? implode(' | ', $parts) : 'Parede sem detalhes';
    }
    
    // Calcular totais
    $totalRooms = $budget->rooms ? $budget->rooms->count() : 0;
    $totalItems = 0;
    $totalMeters = 0;
    
    if ($budget->rooms) {
        foreach ($budget->rooms as $room) {
            if ($room->walls) {
                $totalItems += $room->walls->count();
                foreach ($room->walls as $wall) {
                    if ($wall->total_area) {
                        $totalMeters += (float)$wall->total_area;
                    } elseif ($wall->width && $wall->height) {
                        $totalMeters += (float)$wall->width * (float)$wall->height;
                    }
                }
            }
        }
    }
@endphp
<body>
    <!-- Cabeçalho -->
    <div class="pdf-header">
        <div class="pdf-logo">
            <div class="logo-placeholder">
                Logo
            </div>
        </div>
        <div class="pdf-header-right">
            <h1 class="pdf-title">Orçamento de venda Nº {{ $budget->id ?? '—' }}</h1>
            <p class="pdf-subtitle">{{ $budget->name ?? '—' }}</p>
        </div>
    </div>

    <!-- Informações do Orçamento e Cliente -->
    <div class="pdf-info-section">
        <div class="pdf-info-section-wrapper">
            <div class="pdf-info-table-wrapper">
                <table class="pdf-info-table">
                    <tbody>
                        <tr>
                            <td class="pdf-info-label">Cliente</td>
                            <td class="pdf-info-value">{{ $dropshippingData ? $dropshippingData->name : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-info-label">Endereço</td>
                            <td class="pdf-info-value">
                                <div>{{ formatAddressLine1($dropshippingData) }}</div>
                                <div>{{ formatAddressLine2($dropshippingData) }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="pdf-info-label">Contato</td>
                            <td class="pdf-info-value">
                                @if($dropshippingData && $dropshippingData->phone)
                                    <div>Fone: {{ formatPhone($dropshippingData->phone) }}</div>
                                @endif
                                @if($dropshippingData && $dropshippingData->email)
                                    <div>{{ $dropshippingData->email }}</div>
                                @endif
                                @if(!$dropshippingData || (!$dropshippingData->phone && !$dropshippingData->email))
                                    <div>—</div>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pdf-info-table-wrapper">
                <table class="pdf-info-table">
                    <tbody>
                        <tr>
                            <td class="pdf-info-label">Número do pedido</td>
                            <td class="pdf-info-value">{{ $budget->id ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-info-label">Data</td>
                            <td class="pdf-info-value">{{ formatDate($budget->created_at) }}</td>
                        </tr>
                        <tr>
                            <td class="pdf-info-label">Data prevista</td>
                            <td class="pdf-info-value">{{ formatEstimatedDate($budget->delivery_time) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tabela de Itens -->
    <div class="pdf-items-section">
        <table class="pdf-items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Modelo</th>
                    <th>Quantidade de Paredes</th>
                    <th>Metros</th>
                </tr>
            </thead>
            <tbody>
                @forelse($budget->rooms ?? [] as $room)
                    <tr>
                        <td>
                            <strong>{{ $room->name ?? 'Ambiente ' . $loop->iteration }}</strong>
                            <div class="wall-details">
                                @foreach($room->walls ?? [] as $wallIndex => $wall)
                                    <div style="{{ $wallIndex > 0 ? 'margin-top: 5px;' : '' }}">
                                        {{ formatWallDetails($wall) }}
                                    </div>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            @foreach($room->walls ?? [] as $wallIndex => $wall)
                                <div style="{{ $wallIndex > 0 ? 'margin-top: 5px;' : '' }}">
                                    {{ $wall->collectionModel->name ?? $wall->collection_model_name ?? '—' }}
                                </div>
                            @endforeach
                        </td>
                        <td>{{ $room->walls ? $room->walls->count() : 0 }}</td>
                        <td>
                            @php
                                $roomMeters = 0;
                                if ($room->walls) {
                                    foreach ($room->walls as $wall) {
                                        if ($wall->total_area) {
                                            $roomMeters += (float)$wall->total_area;
                                        } elseif ($wall->width && $wall->height) {
                                            $roomMeters += (float)$wall->width * (float)$wall->height;
                                        }
                                    }
                                }
                            @endphp
                            {{ number_format($roomMeters, 2, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 16px;">
                            Nenhum ambiente cadastrado para este orçamento.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Resumo do Pedido -->
    <div class="pdf-summary-section">
        <div class="pdf-summary-left">
            <p><strong>Total de Ambientes:</strong> {{ $totalRooms }}</p>
            <p><strong>Total de Paredes:</strong> {{ $totalItems }}</p>
            <p><strong>Metros:</strong> {{ number_format($totalMeters, 2, ',', '.') }}</p>
            <p><strong>Frete:</strong> {{ formatCurrency($budget->selected_carrier_price ?? 0) }}</p>
            <p><strong>Previsão de entrega:</strong> {{ formatDeliveryTime($budget->delivery_time) }}</p>
            <p class="pdf-total-cash"><strong>Total à Vista:</strong> {{ $financial['cash_total_formatted'] }}</p>
            @if($financial['installment_total_formatted'] !== null)
                <p class="pdf-total-installment"><strong>Total a Prazo:</strong> {{ $financial['installment_total_formatted'] }}</p>
            @endif
            @if(isset($financial['mockup_percentage']) && $financial['mockup_percentage'] > 0)
                <p class="pdf-mockup-info">
                    <strong>Mockup:</strong> {{ number_format($financial['mockup_percentage'], 2, ',', '.') }}%
                </p>
            @endif
        </div>
    </div>

    <!-- Informações de Entrega -->
    <div class="pdf-shipping-section">
        <div class="pdf-shipping-section-wrapper">
            <div class="pdf-shipping-group-wrapper">
                <div class="pdf-info-group">
                    <strong>Transportadora</strong>
                    <p class="mb-0">{{ getCarrierName($budget->selected_carrier_name) ?? '—' }}</p>
                </div>
            </div>
            <div class="pdf-shipping-group-wrapper">
                <div class="pdf-info-group">
                    <strong>Modalidade de frete</strong>
                    <p class="mb-0">Contratação do Frete por conta do Destinatário (FOB)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Observações -->
    @if($budget->comment_referring_model)
        <div class="pdf-observations-section">
            <strong>Observações</strong>
            <p class="mb-0">{{ $budget->comment_referring_model }}</p>
        </div>
    @endif
</body>
</html>
