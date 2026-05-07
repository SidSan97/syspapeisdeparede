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

function isNonEmpty(value) {
  return value !== null && value !== undefined && String(value).trim() !== '';
}

export function buildBudgetSummaryText({
  totalWalls,
  totalArea,
  totalVista,
  totalPrazo,
  stripSummary,
  freight,
  artsTotal,
}) {
  const resumo = stripSummary || '-';
  const lines = [
    'Orçamento Papel de Parede Sob Medida:',
    '',
    `Total: ${totalWalls} faixas • ${totalArea} m`,
  ];

  if (isNonEmpty(freight)) {
    lines.push(`Frete: ${freight}`);
  }
  if (isNonEmpty(artsTotal)) {
    lines.push(`Artes: ${artsTotal}`);
  }

  lines.push(`À vista: ${totalVista}`, `A prazo: ${totalPrazo}`, '', `Resumo: ${resumo}`);

  const missing = [];
  if (!isNonEmpty(freight)) missing.push('frete');
  if (!isNonEmpty(artsTotal)) missing.push('artes');
  if (missing.length) {
    lines.push('', `*Não estão inclusos os valores de ${missing.join(' e de ')}.*`);
  }

  return lines.join('\n');
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

const DEFAULT_COPY_MESSAGES = {
  success: 'Resumo copiado para a área de transferência',
  error: 'Não foi possível copiar o resumo.',
};

/**
 * Constrói o texto do resumo, copia para a área de transferência e dispara toasts.
 *
 * @param {Parameters<typeof buildBudgetSummaryText>[0]} summaryParams
 * @param {{ success?: (msg: string) => void, error?: (msg: string) => void }} [toast]
 * @param {{ success?: string, error?: string }} [messages]
 * @returns {Promise<boolean>}
 */
export async function copyBudgetSummary(summaryParams, toast, messages = {}) {
  const text = buildBudgetSummaryText(summaryParams);
  const ok = await copyBudgetSummaryText(text);

  const successMessage = messages.success ?? DEFAULT_COPY_MESSAGES.success;
  const errorMessage = messages.error ?? DEFAULT_COPY_MESSAGES.error;

  if (ok) {
    toast?.success?.(successMessage);
  } else {
    toast?.error?.(errorMessage);
  }

  return ok;
}
