<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etiqueta de Separação</title>
    <style>
        @page {
            margin: 12mm;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #000;
            background: #fff;
            font-size: 12px;
            line-height: 1.35;
        }

        .label-container {
            width: 100%;
            max-width: 14cm;
            border: 2px solid #000;
            padding: 16px 18px;
            background: #fff;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 12px;
            line-height: 1.2;
        }

        .section {
            margin-top: 10px;
        }

        .section-label {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .section-value {
            font-size: 13px;
            word-wrap: break-word;
        }

        .observation-value {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .strips-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 12px;
        }

        .strips-table th,
        .strips-table td {
            border: 1px solid #cfcfcf;
            padding: 6px 8px;
            text-align: right;
        }

        .strips-table thead th {
            background: #ececec;
            font-weight: bold;
        }

        .strips-table tbody tr:nth-child(even) td {
            background: #f7f7f7;
        }

        .status {
            margin-top: 14px;
            font-size: 15px;
            text-align: center;
            text-transform: uppercase;
        }

        .status-bold {
            font-weight: bold;
        }

        .layout-quantity {
            margin-top: 8px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="label-container">
        <div class="title">{{ $title }}</div>

        @if(!empty($card_name))
            <div class="section">
                <div class="section-value">{{ $card_name }}</div>
            </div>
        @endif

        @if(!empty($strip_groups))
            <div class="section">
                <table class="strips-table">
                    <thead>
                        <tr>
                            <th>Qtd. Faixas</th>
                            <th>Alt. Faixas (m)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($strip_groups as $group)
                            <tr>
                                <td>{{ $group['q'] }}</td>
                                <td>{{ number_format((float) $group['h'], 2, '.', '') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!empty($model_name))
            <div class="section">
                <div class="section-label">Modelo escolhido</div>
                <div class="section-value">{{ $model_name }}</div>
            </div>
        @endif

        @if(!empty($observation))
            <div class="section">
                <div class="section-label">Observações</div>
                <div class="section-value observation-value">{{ $observation }}</div>
            </div>
        @endif

        @if(!empty($carrier_name))
            <div class="section">
                <div class="section-label">Transportadora</div>
                <div class="section-value">{{ $carrier_name }}</div>
            </div>
        @endif

        @if(!empty($packer))
            <div class="section">
                <div class="section-label">Embalador</div>
                <div class="section-value">{{ $packer }}</div>
            </div>
        @endif

        <div class="status">
            @php
                $statusParts = explode(' ', $status);
            @endphp
            @if(count($statusParts) > 1)
                {{ strtoupper($statusParts[0]) }} <span class="status-bold">{{ strtoupper(implode(' ', array_slice($statusParts, 1))) }}</span>
            @else
                {{ strtoupper($status) }}
            @endif
        </div>

        @if(!empty($layout_quantity))
            <div class="layout-quantity">{{ $layout_quantity }}</div>
        @endif
    </div>
</body>
</html>
