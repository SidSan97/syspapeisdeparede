/**
 * Utilitários para manipulação de datas com máscara DD/MM/YYYY
 */

/**
 * Converte uma string de data no formato DD/MM/YYYY para um objeto Date
 * @param {string} dateString - String da data no formato DD/MM/YYYY
 * @returns {Date|null} Objeto Date ou null se a data for inválida
 */
export function parseDateFromMask(dateString) {
  if (!dateString || dateString.length !== 10) {
    return null;
  }

  const parts = dateString.split('/');
  if (parts.length !== 3) {
    return null;
  }

  const day = parseInt(parts[0], 10);
  const month = parseInt(parts[1], 10) - 1; // Month is 0-indexed
  const year = parseInt(parts[2], 10);

  if (Number.isNaN(day) || Number.isNaN(month) || Number.isNaN(year)) {
    return null;
  }

  return new Date(year, month, day);
}

/**
 * Formata uma data para o formato brasileiro DD/MM/YYYY
 * @param {string|Date} value - Data a ser formatada (string ISO ou objeto Date)
 * @returns {string} Data formatada ou '—' se inválida
 */
export function formatDate(value) {
  if (!value) {
    return '—';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat('pt-BR').format(date);
}

