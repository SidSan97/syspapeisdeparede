<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Produção #{{ $report->id }}</title>
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

        .pdf-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #000;
        }

        .pdf-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #000;
        }

        .pdf-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ccc;
            color: #000;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            padding: 5px 0;
        }

        .info-label {
            font-weight: bold;
            width: 200px;
            color: #333;
        }

        .info-value {
            flex: 1;
            color: #000;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-box {
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .info-box-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .info-box-value {
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }

        .additional-data {
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin-top: 15px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #28a745;
            color: #fff;
        }

        .badge-info {
            background-color: #17a2b8;
            color: #fff;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="pdf-header">
        <div class="pdf-title">Relatório de Produção</div>
        <div class="pdf-subtitle">ID do Relatório: #{{ $report->id }} | Data: {{ $report->action_date->format('d/m/Y H:i') }}</div>
    </div>

    <!-- Informações Gerais -->
    <div class="section">
        <div class="section-title">Informações Gerais</div>
        <div class="info-row">
            <div class="info-label">Tipo de Ação:</div>
            <div class="info-value">
                @if($report->action_type === 'mark_as_produced')
                    <span class="badge badge-success">Marcado como Produzido</span>
                @elseif($report->action_type === 'production_percentage_100')
                    <span class="badge badge-info">Produção 100%</span>
                @else
                    {{ ucfirst(str_replace('_', ' ', $report->action_type)) }}
                @endif
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Máquina:</div>
            <div class="info-value">{{ $report->column_name ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Data da ação:</div>
            <div class="info-value">{{ $report->action_date->format('d/m/Y H:i:s') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Usuário responsável:</div>
            <div class="info-value">{{ $report->user->name ?? 'N/A' }}</div>
        </div>
        @if($report->card_description)
        <div class="info-row">
            <div class="info-label">Descrição do Card:</div>
            <div class="info-value">{{ $report->card_description }}</div>
        </div>
        @endif
    </div>

    <!-- Detalhes da Parede -->
    @if($report->layout_summary)
    <div class="section">
        <div class="section-title">Detalhes da parede</div>
        <div class="info-grid">
            @if(isset($report->layout_summary['wall_name']))
            <div class="info-box">
                <div class="info-box-label">Nome da parede</div>
                <div class="info-box-value">{{ $report->layout_summary['wall_name'] }}</div>
            </div>
            @endif
            @if(isset($report->layout_summary['room_name']))
            <div class="info-box">
                <div class="info-box-label">Ambiente</div>
                <div class="info-box-value">{{ $report->layout_summary['room_name'] }}</div>
            </div>
            @endif
            @if(isset($report->layout_summary['width']))
            <div class="info-box">
                <div class="info-box-label">Largura</div>
                <div class="info-box-value">{{ number_format($report->layout_summary['width'], 2, ',', '.') }} m</div>
            </div>
            @endif
            @if(isset($report->layout_summary['height']))
            <div class="info-box">
                <div class="info-box-label">Altura</div>
                <div class="info-box-value">{{ number_format($report->layout_summary['height'], 2, ',', '.') }} m</div>
            </div>
            @endif
            @if(isset($report->layout_summary['total_area']))
            <div class="info-box">
                <div class="info-box-label">Área total</div>
                <div class="info-box-value">{{ number_format($report->layout_summary['total_area'], 2, ',', '.') }} m²</div>
            </div>
            @endif
            @if(isset($report->layout_summary['strip_height']))
            <div class="info-box">
                <div class="info-box-label">Tamanho da faixa</div>
                <div class="info-box-value">{{ number_format($report->layout_summary['strip_height'], 2, ',', '.') }} m</div>
            </div>
            @endif
            @if(isset($report->layout_summary['strip_count']))
            <div class="info-box">
                <div class="info-box-label">Quantidade de faixas</div>
                <div class="info-box-value">{{ $report->layout_summary['strip_count'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Informações do Modelo -->
    @if($report->model_name)
    <div class="section">
        <div class="section-title">Modelo selecionado</div>
        <div class="info-row">
            <div class="info-label">Nome do modelo:</div>
            <div class="info-value">{{ $report->model_name }}</div>
        </div>
    </div>
    @endif

    <!-- Dados Adicionais -->
    @if($report->additional_data && count($report->additional_data) > 0)
    <div class="section">
        <div class="section-title">Dados adicionais</div>
        <div class="additional-data">
            @foreach($report->additional_data as $key => $value)
            <div class="info-row">
                <div class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</div>
                <div class="info-value">
                    @if(is_array($value))
                        {{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                    @else
                        {{ $value }}
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Relatório gerado em {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Sistema de Papel de Parede</p>
    </div>
</body>
</html>

