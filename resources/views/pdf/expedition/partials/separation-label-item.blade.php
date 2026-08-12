<div class="label-container">
    <div class="title">{{ $label['title'] ?? '' }}</div>

    @if(!empty($label['card_name']))
        <div class="section">
            <div class="section-value">{{ $label['card_name'] }}</div>
        </div>
    @endif

    @if(!empty($label['strip_groups']))
        <div class="section">
            <table class="strips-table">
                <thead>
                    <tr>
                        <th>Qtd. Faixas</th>
                        <th>Alt. Faixas (m)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($label['strip_groups'] as $group)
                        <tr>
                            <td>{{ $group['q'] }}</td>
                            <td>{{ number_format((float) $group['h'], 2, '.', '') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($label['model_name']))
        <div class="section">
            <div class="section-label">Modelo escolhido</div>
            <div class="section-value">{{ $label['model_name'] }}</div>
        </div>
    @endif

    @if(!empty($label['observation']))
        <div class="section">
            <div class="section-label">Observações</div>
            <div class="section-value observation-value">{{ $label['observation'] }}</div>
        </div>
    @endif

    @if(!empty($label['carrier_name']))
        <div class="section">
            <div class="section-label">Transportadora</div>
            <div class="section-value">{{ $label['carrier_name'] }}</div>
        </div>
    @endif

    @if(!empty($label['packer']))
        <div class="section">
            <div class="section-label">Embalador</div>
            <div class="section-value">{{ $label['packer'] }}</div>
        </div>
    @endif

    <div class="status">
        @php
            $status = strtoupper((string) ($label['status'] ?? ''));
            $statusParts = explode(' ', $status);
        @endphp
        @if(count($statusParts) > 1)
            {{ $statusParts[0] }} <span class="status-bold">{{ implode(' ', array_slice($statusParts, 1)) }}</span>
        @else
            {{ $status }}
        @endif
    </div>

    @if(!empty($label['layout_quantity']))
        <div class="layout-quantity">{{ $label['layout_quantity'] }}</div>
    @endif
</div>
