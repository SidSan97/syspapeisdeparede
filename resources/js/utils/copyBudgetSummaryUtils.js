function fallbackCopy(content) {
    const textarea = document.createElement('textarea');
    textarea.value = content;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    textarea.style.top = '-9999px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch (e) {
        copied = false;
    }

    document.body.removeChild(textarea);
    return copied;
}

export function buildBudgetSummaryText({
    totalWalls,
    totalArea,
    totalVista,
    totalPrazo,
    stripSummary,
}) {
    const resumo = stripSummary || '-';
    return [
        'Orçamento Papel de Parede Sob Medida:',
        '',
        `Total: ${totalWalls} faixas • ${totalArea} m`,
        `À vista: ${totalVista}`,
        `A prazo: ${totalPrazo}`,
        '',
        `Resumo: ${resumo}`,
        '',
        '*Não estão inclusos os valores de frete e de artes.*',
    ].join('\n');
}

export async function copyBudgetSummaryText(text) {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return true;
        }
        return fallbackCopy(text);
    } catch (error) {
        return fallbackCopy(text);
    }
}
