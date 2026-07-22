<table class="grid">
    <thead>
        <tr>
            <th>Item</th>
            <th>Modelo</th>
            <th style="text-align: right; width: 6%;">Metros</th>
            <th style="text-align: right; width: 15%;">Preço à Vista</th>
            <th style="text-align: right; width: 15%;">Preço a Prazo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items ?? [] as $item)
            <tr>
                <td>
                    <b>{{ $item['title'] }}</b>
                    <p>{!! $item['description'] !!}</p>
                </td>
                <td>{{ $item['model_name'] }}</td>
                <td style="text-align: right;">{{ $item['meters'] }}</td>
                <td style="text-align: right;">{{ $item['total'] }}</td>
                <td style="text-align: right;">{{ $item['installment_total'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    Nenhum ambiente cadastrado para este orçamento.
                </td>
            </tr>
        @endforelse
        <tr>
            <td style="border-right: none;">
                <p><b>Total de ambientes:</b> {{ $totals['rooms'] }}</p>
                <p><b>Total de paredes:</b> {{ $totals['walls'] }}</p>
                <p><b>Metros:</b> {{ $totals['meters'] }}</p>
            </td>
            <td colspan="2"
                style="text-align: right; font-weight: 700;">
                <br><br>
                <p>Total dos itens</p>
            </td>
            <td style="text-align: right;">
                <br><br>
                {{ money_view($totals['total_in_cash']) }}
            </td>
            <td style="text-align: right;">
                <br><br>
                {{ money_view($totals['total_in_installments']) }}
            </td>
        </tr>
    </tbody>
</table>
