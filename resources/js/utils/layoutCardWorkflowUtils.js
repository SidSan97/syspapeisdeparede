import { differenceInCalendarDays } from 'date-fns';

/**
 * Formata ISO/datetime para exibição compacta no card (dia/mês + hora).
 * @param {string|Date|null|undefined} iso
 * @returns {string}
 */
export function formatWorkflowShort(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

/**
 * Monta a linha resumida de início/fim/duração para cards de layout (order_budget).
 * @param {{ started_at?: string|null, finished_at?: string|null }|null|undefined} card
 * @returns {string}
 */
export function buildWorkflowSummaryLine(card) {
  const c = card;
  if (!c?.started_at) return '';

  const start = formatWorkflowShort(c.started_at);
  if (!start) return '';

  if (c.finished_at) {
    const end = formatWorkflowShort(c.finished_at);
    if (!end) return `Início ${start}`;
    const a = new Date(c.started_at);
    const b = new Date(c.finished_at);
    const days =
      !Number.isNaN(a.getTime()) && !Number.isNaN(b.getTime())
        ? differenceInCalendarDays(b, a)
        : null;
    const dur =
      typeof days === 'number' && days >= 0 ? ` · ${days} dia${days === 1 ? '' : 's'}` : '';
    return `Início ${start} · Fim ${end}${dur}`;
  }

  return `Início ${start}`;
}
