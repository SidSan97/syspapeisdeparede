<div>
    <div>
        <p><strong>Total de Ambientes:</strong> {{ $totals['rooms'] }}</p>
        <p><strong>Total de Paredes:</strong> {{ $totals['walls'] }}</p>
        <p><strong>Metros:</strong> {{ $totals['meters'] }}</p>
        <p><strong>Frete:</strong> {{ money_view($totals['shipping']) }}</p>
        <p><strong>Previsão de entrega:</strong> {{ $viewModel->estimatedDelivery() }}</p>
        <hr>
        <p class="pdf-total-cash">
            <strong>Total à Vista:</strong> {{ money_view($totals['total_in_cash']) }}
        </p>
        @if (filled($totals['total_in_installments']))
            <p class="pdf-total-installment">
                <strong>Total a Prazo:</strong> {{ money_view($totals['total_in_installments']) }}
            </p>
        @endif
    </div>
</div>
