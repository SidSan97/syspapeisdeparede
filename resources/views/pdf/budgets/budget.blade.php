<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento #{{ $budget->id }}</title>
    <style>
        @page {
            margin: 32px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1f2933;
            background: #ffffff;
        }

        h1, h2, h3 {
            margin: 0;
            font-weight: 600;
            color: #111827;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 2px solid #2563eb;
            margin-bottom: 24px;
        }

        .header .branding {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: .5px;
            color: #2563eb;
        }

        .metadata {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 24px;
            margin-bottom: 24px;
        }

        .metadata .label {
            font-weight: 600;
            color: #111827;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            background: #f9fafb;
        }

        .totals {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .total-item {
            padding: 12px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #d1d5db;
        }

        .total-item .label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .total-item .value {
            margin-top: 6px;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead th {
            background: #2563eb;
            color: #ffffff;
            text-align: left;
            padding: 10px;
            font-size: 12px;
        }

        table tbody td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        table tbody tr:nth-child(odd) {
            background: #f3f4f6;
        }

        ul {
            margin: 0;
            padding-left: 16px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            background: #d1fae5;
            color: #047857;
        }

        .status-badge.cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .notes {
            font-size: 11px;
            color: #4b5563;
            line-height: 1.4;
        }

        .footer {
            text-align: center;
            margin-top: 36px;
            font-size: 10px;
            color: #9ca3af;
        }
    </style>
</head>
@php
    $formattedDeliveryTime = $budget->delivery_time
        ? $budget->delivery_time . ' ' . ($budget->delivery_time === 1 ? 'dia' : 'dias')
        : 'Não informado';
    $status = $budget->status ?? 'Em aberto';
    $statusClass = strtolower($status) === 'cancelado' ? 'cancelled' : '';
@endphp
<body>
    <header class="header">
        <div>
            <div class="branding">{{ config('app.name', 'Wallpaper') }}</div>
            <div>Relatório de Orçamento</div>
        </div>
        <div style="text-align: right;">
            <div style="font-weight:600; font-size:11px; text-transform:uppercase; color:#111827; letter-spacing:.8px;">
                Data de emissão
            </div>
            <div>{{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </header>

    <section style="margin-bottom: 24px;">
        <h2>Resumo do Orçamento</h2>
        <div class="metadata">
            <div>
                <div class="label">Orçamento</div>
                <div>#{{ $budget->id }}</div>
            </div>
            <div>
                <div class="label">Identificação</div>
                <div>{{ $budget->name ?? 'Não informado' }}</div>
            </div>
            <div>
                <div class="label">Status</div>
                <div class="status-badge {{ $statusClass }}">{{ ucfirst($status) }}</div>
            </div>
            <div>
                <div class="label">Prazo estimado</div>
                <div>{{ $formattedDeliveryTime }}</div>
            </div>
        </div>
    </section>

    <section class="card">
        <h3>Detalhes financeiros</h3>
        <div class="totals" style="margin-top: 16px;">
            <div class="total-item">
                <div class="label">Valor original</div>
                <div class="value">{{ $financial['original_formatted'] }}</div>
            </div>
            <div class="total-item">
                <div class="label">Acréscimo aplicado</div>
                <div class="value">{{ $financial['increase_percentage_display'] }} ({{ $financial['increase_formatted'] }})</div>
            </div>
            <div class="total-item" style="grid-column: span 2;">
                <div class="label">Valor total com acréscimo</div>
                <div class="value">{{ $financial['total_formatted'] }}</div>
            </div>
        </div>
    </section>

    <section style="margin-bottom: 24px;">
        <h3>Ambientes e paredes</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Ambiente</th>
                    <th style="width: 70%;">Detalhes das paredes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($budget->rooms ?? [] as $room)
                    <tr>
                        <td>
                            <strong>{{ $room->name ?? 'Ambiente ' . $loop->iteration }}</strong>
                        </td>
                        <td>
                            @if(($room->walls ?? collect())->count())
                                <ul>
                                    @foreach($room->walls as $wall)
                                        <li>
                                            {{ $wall->name ?? 'Parede ' . $loop->iteration }}
                                            @if(!is_null($wall->width) && !is_null($wall->height))
                                                — {{ number_format($wall->width, 2, ',', '.') }}m x {{ number_format($wall->height, 2, ',', '.') }}m
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="notes">Nenhuma parede cadastrada para este ambiente.</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center; padding: 16px;">
                            Nenhum ambiente cadastrado para este orçamento.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section>
        <h3>Observações</h3>
        <p class="notes">
            O valor apresentado considera o acréscimo informado no momento da geração deste documento e pode variar conforme alterações futuras.
            Este orçamento é válido por 30 dias a partir da data de emissão, salvo ajustes negociados entre as partes.
        </p>
    </section>

    <footer class="footer">
        Documento gerado automaticamente pelo sistema {{ config('app.name', 'Wallpaper') }}.
    </footer>
</body>
</html>

