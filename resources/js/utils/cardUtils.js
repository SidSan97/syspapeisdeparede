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

