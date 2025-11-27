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
 * Retorna o texto do timer baseado na data de entrega do card
 * Se a data passou e production_percentage < 100, mostra "Atrasado"
 * @param {Object} card - Objeto do card
 * @param {string} card.delivery_date_end_full - Data final de entrega completa
 * @param {number} card.production_percentage - Percentual de produção (0-100)
 * @param {Date} currentTime - Data/hora atual
 * @returns {string|null} Texto do timer ou null se não houver data
 */
export function getProductionTimerText(card, currentTime) {
  if (!card || !card.delivery_date_end_full) {
    return null;
  }

  const deliveryDate = parseDate(card.delivery_date_end_full);
  if (!deliveryDate) {
    return null;
  }

  const now = currentTime instanceof Date ? currentTime : new Date(currentTime);
  const diffMs = deliveryDate.getTime() - now.getTime();
  const diffSeconds = Math.floor(diffMs / 1000);
  const diffMinutes = Math.floor(diffSeconds / 60);
  const diffHours = Math.floor(diffMinutes / 60);
  const diffDays = Math.floor(diffHours / 24);

  // Se já passou da data E production_percentage < 100, mostrar "Atrasado"
  if (diffMs < 0) {
    const productionPercentage = Number(card.production_percentage) || 0;
    if (productionPercentage < 100) {
      return 'Atrasado';
    }
    // Se já está 100%, não mostrar timer
    return null;
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
 * Retorna a classe CSS do timer de produção baseado no status
 * @param {Object} card - Objeto do card
 * @param {string} card.delivery_date_end_full - Data final de entrega completa
 * @param {number} card.production_percentage - Percentual de produção (0-100)
 * @param {Date} currentTime - Data/hora atual
 * @param {string} prefix - Prefixo da classe CSS (ex: 'production-card-timer')
 * @returns {string} Classe CSS do timer
 */
export function getProductionTimerClass(card, currentTime, prefix = 'production-card-timer') {
  if (!card || !card.delivery_date_end_full) {
    return '';
  }

  const deliveryDate = parseDate(card.delivery_date_end_full);
  if (!deliveryDate) {
    return '';
  }

  const now = currentTime instanceof Date ? currentTime : new Date(currentTime);
  const diffMs = deliveryDate.getTime() - now.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  // Se já passou da data E production_percentage < 100, mostrar como atrasado
  if (diffMs < 0) {
    const productionPercentage = Number(card.production_percentage) || 0;
    if (productionPercentage < 100) {
      return `${prefix}-overdue`;
    }
    return '';
  }

  // Se está próximo do prazo (menos de 3 dias)
  if (diffDays <= 3) {
    return `${prefix}-urgent`;
  }

  return `${prefix}-normal`;
}

