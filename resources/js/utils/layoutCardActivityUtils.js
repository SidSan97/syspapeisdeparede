/**
 * Utilitários para o Power-Up Activity (cronômetro por cartão).
 *
 * O backend persiste por OrderBudget:
 *  - activity_running_since: ISO datetime ou null. Marca o início da sessão atual em execução.
 *  - activity_elapsed_seconds: total acumulado em segundos das sessões fechadas.
 *  - activity_sessions: lista de sessões fechadas (cada pausa ou conclusão com timer ativo).
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
/**
 * Formata duração de uma sessão no estilo M:SS (ex.: 5:22).
 * @param {number} totalSeconds
 * @returns {string}
 */
export function formatActivitySessionDuration(totalSeconds) {
  const safe = Math.max(0, Math.floor(Number(totalSeconds) || 0));
  const minutes = Math.floor(safe / 60);
  const seconds = safe % 60;

  return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

/**
 * Formata a data de encerramento da sessão (dd/mm/yy).
 * @param {string} isoDate
 * @returns {string}
 */
export function formatActivitySessionDate(isoDate) {
  const date = new Date(isoDate);
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return date.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: '2-digit',
  });
}

/**
 * Soma a duração de todas as sessões fechadas registradas.
 * @param {Array<{ duration_seconds?: number }>|null|undefined} sessions
 * @returns {number}
 */
export function sumActivitySessionsSeconds(sessions) {
  if (!Array.isArray(sessions)) {
    return 0;
  }

  return sessions.reduce((total, session) => {
    return total + Math.max(0, Number(session?.duration_seconds ?? 0));
  }, 0);
}

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
 * Indica se o card já foi marcado como concluído.
 * @param {{ completed_at?: string|null, is_completed?: boolean }|null|undefined} card
 * @returns {boolean}
 */
export function isCardCompleted(card) {
  if (!card) return false;
  if (typeof card.is_completed === 'boolean') {
    return card.is_completed;
  }
  return Boolean(card.completed_at);
}

/**
 * Mescla os dados retornados pelo backend (start/pause/complete) sobre o card.
 * Garante que ambos os campos persistidos e os derivados fiquem coerentes.
 * @param {object} card
 * @param {{
 *   activity_running_since?: string|null,
 *   activity_elapsed_seconds?: number|null,
 *   activity_total_seconds?: number|null,
 *   activity_is_running?: boolean|null,
 *   completed_at?: string|null,
 *   is_completed?: boolean|null,
 *   activity_sessions?: Array<{ id: number, started_at: string, ended_at: string, duration_seconds: number }>|null
 * }} payload
 * @returns {object} novo card com os campos atualizados
 */
export function mergeActivityPayload(card, payload) {
  if (!card || !payload) return card;

  const completedAt =
    payload.completed_at !== undefined ? payload.completed_at : card.completed_at ?? null;

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
    completed_at: completedAt,
    is_completed:
      typeof payload.is_completed === 'boolean' ? payload.is_completed : Boolean(completedAt),
    activity_sessions:
      payload.activity_sessions !== undefined
        ? payload.activity_sessions
        : card.activity_sessions ?? [],
  };
}
