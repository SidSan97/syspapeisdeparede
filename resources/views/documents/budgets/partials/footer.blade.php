@if (filled($notes))
    <table style="border: 1px solid #000;"
           width="100%">
        <tbody>
            <tr>
                <td style="padding: 8px;">
                    <b>Observações</b>
                    <p>{!! nl2br(e($notes)) !!}</p>
                </td>
            </tr>
        </tbody>
    </table>
@endif
