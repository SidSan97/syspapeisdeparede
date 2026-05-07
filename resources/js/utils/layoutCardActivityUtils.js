/**
 * Utilitários para o Power-Up Activity (cronômetro por cartão).
 *
 * O backend persiste apenas dois campos por OrderBudget:
 *  - activity_running_since: ISO datetime ou null. Marca o início da sessão atual em execução.
 *  - activity_elapsed_seconds: total acumulado em segundos das sessões anteriores e ajustes manuais.
 *
 * O total exibido em qualquer momento é a soma de `elapsed_seconds` com a duração
 * desde `running_since` até o instante atual (caso o timer esteja rodando).
 */

/**
 * Calcula o total de segundos do cronômetro do cartão, considerando a sessão em execução.
 * @param {{ activity_running_since?: string|null, activity_elapsed_seconds?: number|null }|null|undefined} card
 * @param {Date} [now=new Date()]
 * @returns {number}
 */
export function getCardTotalSeconds(card, now = new Date()) {
  if (!card) return 0;

  const elapsed = Math.max(0, Number(card.activity_elapsed_seconds ?? 0));

  if (!card.activity_running_since) {
    return elapsed;
  }

  const startedAt = new Date(card.activity_running_since);
  if (Number.isNaN(startedAt.getTime())) {
    return elapsed;
  }

  const sessionSeconds = Math.max(0, Math.floor((now.getTime() - startedAt.getTime()) / 1000));

  return elapsed + sessionSeconds;
}

/**
 * Indica se o cronômetro do cartão está em execução.
 * @param {{ activity_running_since?: string|null, activity_is_running?: boolean }|null|undefined} card
 * @returns {boolean}
 */
export function isCardActivityRunning(card) {
  if (!card) return false;
  if (typeof card.activity_is_running === 'boolean') {
    return card.activity_is_running;
  }
  return Boolean(card.activity_running_since);
}

/**
 * Formata uma quantidade total de segundos no formato HH:MM:SS.
 * @param {number} totalSeconds
 * @returns {string}
 */
export function formatActivityClock(totalSeconds) {
  const safe = Math.max(0, Math.floor(Number(totalSeconds) || 0));
  const hours = Math.floor(safe / 3600);
  const minutes = Math.floor((safe % 3600) / 60);
  const seconds = safe % 60;
  return [hours, minutes, seconds].map((v) => String(v).padStart(2, '0')).join(':');
}

/**
 * Formata uma quantidade total de segundos em rótulo curto: "Xh Ym" / "Mm" / "Ss".
 * @param {number} totalSeconds
 * @returns {string}
 */
export function formatActivityCompact(totalSeconds) {
  const safe = Math.max(0, Math.floor(Number(totalSeconds) || 0));
  if (safe < 60) {
    return `${safe}s`;
  }
  const hours = Math.floor(safe / 3600);
  const minutes = Math.floor((safe % 3600) / 60);
  if (hours > 0) {
    return minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`;
  }
  return `${minutes}m`;
}

/**
 * Mescla os dados retornados pelo backend (start/pause/reset/advance) sobre o card.
 * Garante que ambos os campos persistidos e os derivados fiquem coerentes.
 * @param {object} card
 * @param {{ activity_running_since?: string|null, activity_elapsed_seconds?: number|null, activity_total_seconds?: number|null, activity_is_running?: boolean|null }} payload
 * @returns {object} novo card com os campos atualizados
 */
export function mergeActivityPayload(card, payload) {
  if (!card || !payload) return card;
  return {
    ...card,
    activity_running_since: payload.activity_running_since ?? null,
    activity_elapsed_seconds:
      typeof payload.activity_elapsed_seconds === 'number'
        ? payload.activity_elapsed_seconds
        : card.activity_elapsed_seconds ?? 0,
    activity_total_seconds:
      typeof payload.activity_total_seconds === 'number'
        ? payload.activity_total_seconds
        : undefined,
    activity_is_running:
      typeof payload.activity_is_running === 'boolean'
        ? payload.activity_is_running
        : Boolean(payload.activity_running_since),
  };
}
