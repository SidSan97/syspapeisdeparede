<table class="grid-h">
    <tbody>
        <tr>
            <th style="width: 20%;">Frete</th>
            <td>{{ $totals['shipping'] }}</td>
        </tr>
        <tr>
            <th style="width: 20%;">Previsão de entrega</th>
            <td>{{ $viewModel->estimatedDelivery() }}</td>
        </tr>

        @if (filled($carrier))
            <tr>
                <th style="width: 20%;">Transportadora</th>
                <td>{{ $carrier }}</td>
            </tr>
        @endif
    </tbody>
</table>
