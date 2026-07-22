@php($dealer = $viewModel->dealer())

<table width="100%">
    <tbody>
        <tr>
            <td align="left" width="130">
                @if ($dealer && $dealer['logo_base64'])
                    <img src="{{ $dealer['logo_base64'] }}" width="112" height="112" />
                @endif
            </td>
            <td align="right">
                @if ($dealer)
                    <b>{{ $dealer['name'] }}</b><br>
                    @if ($dealer['document'])
                        {{ $dealer['document'] }}<br>
                    @endif
                    @if ($dealer['address_line_1'])
                        {{ $dealer['address_line_1'] }}<br>
                    @endif
                    @if ($dealer['address_line_2'])
                        {{ $dealer['address_line_2'] }}<br>
                    @endif
                    @if ($dealer['phone'])
                        Fone: {{ $dealer['phone'] }}<br>
                    @endif
                    @if ($dealer['email'])
                        {{ $dealer['email'] }}<br>
                    @endif
                @else
                    <b>—</b>
                @endif
            </td>
        </tr>
    </tbody>
</table>

<h2 style="text-align: center; margin-top: 0.83em; margin-bottom: 0.83em;">
    Proposta Comercial Nº {{ $viewModel->budget->id }}
</h2>
