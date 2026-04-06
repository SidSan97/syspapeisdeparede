/**
 * Formata o ID do card com 5 dígitos
 * @param {number|string} id - ID do card
 * @returns {string} ID formatado com 5 dígitos (ex: 1 -> "00001", 100 -> "00100")
 */
export function formatCardId(id) {
  if (!id) {
    return '00000';
  }
  return String(id).padStart(5, '0');
}

/**
 * Retorna o nome de exibição do card
 * Se o card tiver membros, formata como: "00001 - JOÃO SILVA"
 * Caso contrário, retorna o nome normal do card
 * @param {Object} card - Objeto do card
 * @param {string} card.name - Nome do card
 * @param {number} card.id - ID do card
 * @param {Array} card.members - Array de membros do card
 * @param {string} defaultName - Nome padrão caso não tenha nome nem membros
 * @returns {string} Nome formatado para exibição
 */
export function getCardDisplayName(card, defaultName = 'aaa') {
  if (!card) {
    return defaultName;
  }

  // Se o card tiver membros, formatar como: 00001 - JOÃO SILVA
  if (card.members && Array.isArray(card.members) && card.members.length > 0) {
    const formattedId = formatCardId(card.id);
    const membersNames = card.members
      .map(m => m.name?.toUpperCase() || '')
      .filter(Boolean)
      .join(', ');
    return `${formattedId} - ${membersNames}`;
  }

  // Caso contrário, retornar o nome normal
  return card.name || defaultName;
}

/**
 * Parse uma string de data para um objeto Date
 * Suporta formatos ISO e DD/MM/YYYY
 * @param {string} dateString - String da data
 * @returns {Date|null} Objeto Date ou null se inválido
 */
export function parseDate(dateString) {
  if (!dateString) {
    return null;
  }

  // Tentar diferentes formatos de data
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) {
    // Tentar formato DD/MM/YYYY
    const parts = dateString.split('/');
    if (parts.length === 3) {
      const day = parseInt(parts[0], 10);
      const month = parseInt(parts[1], 10) - 1;
      const year = parseInt(parts[2], 10);
      return new Date(year, month, day);
    }
    return null;
  }
  return date;
}

/**
 * Data de calendário local a partir de YYYY-MM-DD ou fallback para parseDate.
 * Evita interpretar "2026-04-28" como meia-noite UTC (desloca o dia em timezones BR).
 * @param {string|null|undefined} value
 * @returns {Date|null}
 */
export function parseCalendarDateLocal(value) {
  if (value == null || value === '') {
    return null;
  }
  const s = String(value).trim();
  const iso = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (iso) {
    const y = Number(iso[1]);
    const mo = Number(iso[2]) - 1;
    const d = Number(iso[3]);
    return new Date(y, mo, d);
  }
  return parseDate(s);
}

/**
 * Dias corridos entre o dia de "hoje" e o dia do prazo (ambos no fuso local).
 * @param {Date} deadlineDate
 * @param {Date} now
 * @returns {number} positivo = dias restantes, 0 = vence hoje, negativo = atraso em dias
 */
export function calendarDaysUntil(deadlineDate, now) {
  const n = now instanceof Date ? now : new Date(now);
  const startToday = new Date(n.getFullYear(), n.getMonth(), n.getDate());
  const endDay = new Date(
    deadlineDate.getFullYear(),
    deadlineDate.getMonth(),
    deadlineDate.getDate(),
  );
  return Math.round((endDay.getTime() - startToday.getTime()) / 86400000);
}

/**
 * Retorna o texto do timer baseado na data de entrega do card
 * @param {Object} card - Objeto do card
 * @param {string} card.delivery_date_end - Data final de entrega
 * @param {Date} currentTime - Data/hora atual
 * @returns {string|null} Texto do timer ou null se não houver data
 */
export function getTimerText(card, currentTime) {
  if (!card || !card.delivery_date_end) {
    return null;
  }

  const endDate = parseDate(card.delivery_date_end);
  if (!endDate) {
    return null;
  }

  const now = currentTime instanceof Date ? currentTime : new Date(currentTime);
  const diffMs = endDate.getTime() - now.getTime();
  const diffSeconds = Math.floor(diffMs / 1000);
  const diffMinutes = Math.floor(diffSeconds / 60);
  const diffHours = Math.floor(diffMinutes / 60);
  const diffDays = Math.floor(diffHours / 24);

  // Se já passou da data
  if (diffMs < 0) {
    const absDays = Math.abs(diffDays);
    const absHours = Math.abs(Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)));
    if (absDays > 0) {
      return `${absDays} ${absDays === 1 ? 'dia' : 'dias'} atrasado`;
    } else if (absHours > 0) {
      return `${absHours} ${absHours === 1 ? 'hora' : 'horas'} atrasado`;
    } else {
      return 'Atrasado';
    }
  }

  // Se ainda não chegou na data
  if (diffDays > 0) {
    return `${diffDays} ${diffDays === 1 ? 'dia' : 'dias'} restante${diffDays > 1 ? 's' : ''}`;
  } else if (diffHours > 0) {
    return `${diffHours} ${diffHours === 1 ? 'hora' : 'horas'} restante${diffHours > 1 ? 's' : ''}`;
  } else if (diffMinutes > 0) {
    return `${diffMinutes} ${diffMinutes === 1 ? 'minuto' : 'minutos'} restante${diffMinutes > 1 ? 's' : ''}`;
  } else {
    return 'Menos de 1 minuto';
  }
}

/**
 * Retorna a classe CSS do timer baseado no status (normal, urgente, atrasado)
 * @param {Object} card - Objeto do card
 * @param {string} card.delivery_date_end - Data final de entrega
 * @param {Date} currentTime - Data/hora atual
 * @param {string} prefix - Prefixo da classe CSS (ex: 'trello-card-timer' ou 'production-card-timer')
 * @returns {string} Classe CSS do timer
 */
export function getTimerClass(card, currentTime, prefix = 'trello-card-timer') {
  if (!card || !card.delivery_date_end) {
    return '';
  }

  const endDate = parseDate(card.delivery_date_end);
  if (!endDate) {
    return '';
  }

  const now = currentTime instanceof Date ? currentTime : new Date(currentTime);
  const diffMs = endDate.getTime() - now.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  // Se já passou da data
  if (diffMs < 0) {
    return `${prefix}-overdue`;
  }

  // Se está próximo do prazo (menos de 3 dias)
  if (diffDays <= 3) {
    return `${prefix}-urgent`;
  }

  return `${prefix}-normal`;
}

/**
 * Texto da tarja de prazo (produção): dias corridos até delivery_date_end_full vs hoje.
 * @param {Object} card
 * @param {string} card.delivery_date_end_full
 * @param {Date} currentTime
 * @returns {string|null}
 */
export function getProductionTimerText(card, currentTime) {
  if (!card || !card.delivery_date_end_full) {
    return null;
  }

  const deliveryDate = parseCalendarDateLocal(card.delivery_date_end_full);
  if (!deliveryDate) {
    return null;
  }

  const now = currentTime instanceof Date ? currentTime : new Date(currentTime);
  const days = calendarDaysUntil(deliveryDate, now);

  if (days < 0) {
    const a = Math.abs(days);
    return a === 1 ? '1 dia de atraso' : `${a} dias de atraso`;
  }
  if (days === 0) {
    return 'Vence hoje';
  }
  if (days === 1) {
    return '1 dia restante';
  }
  return `${days} dias restantes`;
}

/**
 * Modificador de cor da tarja de prazo: verde (≥6 dias), laranja (&lt;6 dias e não atrasado), vermelho (atrasado).
 * @param {Object} card
 * @param {string} card.delivery_date_end_full
 * @param {Date} currentTime
 * @param {string} _prefix legado, ignorado
 * @returns {string} modificador (`production-deadline-badge--success|warning|danger`) ou vazio
 */
export function getProductionTimerClass(card, currentTime, _prefix = 'production-card-timer') {
  if (!card || !card.delivery_date_end_full) {
    return '';
  }

  const deliveryDate = parseCalendarDateLocal(card.delivery_date_end_full);
  if (!deliveryDate) {
    return '';
  }

  const now = currentTime instanceof Date ? currentTime : new Date(currentTime);
  const days = calendarDaysUntil(deliveryDate, now);

  if (days < 0) {
    return 'production-deadline-badge--danger';
  }
  if (days < 6) {
    return 'production-deadline-badge--warning';
  }
  return 'production-deadline-badge--success';
}

/**
 * Classe Bootstrap para badge do status do order budget (layout de produção).
 * @param {string|null|undefined} status
 * @returns {string}
 */
export function getOrderBudgetStatusBadgeClass(status) {
  if (!status) {
    return 'bg-secondary';
  }
  const s = String(status).toLowerCase();
  if (s.includes('aprovar layout')) {
    return 'bg-warning text-dark';
  }
  if (s.includes('pendente')) {
    return 'bg-info';
  }
  if (s.includes('aprovad') || s.includes('conclu')) {
    return 'bg-success';
  }
  return 'bg-secondary';
}

