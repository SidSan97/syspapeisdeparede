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
 * Converte uma data no formato DD/MM/YYYY para YYYY-MM-DD
 * @param {string} dateString - String da data no formato DD/MM/YYYY
 * @returns {string|null} Data no formato YYYY-MM-DD ou null se inválida
 */
export function convertDateMaskToIso(dateString) {
  if (!dateString || dateString.length !== 10) {
    return null;
  }

  const parts = dateString.split('/');
  if (parts.length !== 3) {
    return null;
  }

  const day = parts[0];
  const month = parts[1];
  const year = parts[2];

  if (Number.isNaN(parseInt(day, 10)) || Number.isNaN(parseInt(month, 10)) || Number.isNaN(parseInt(year, 10))) {
    return null;
  }

  return `${year}-${month}-${day}`;
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

  let date;
  
  // Se for uma string no formato YYYY-MM-DD, parsear manualmente para evitar problemas de fuso horário
  if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
    const parts = value.split('T')[0].split('-');
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1; // Month is 0-indexed
    const day = parseInt(parts[2], 10);
    date = new Date(year, month, day);
  } else {
    date = new Date(value);
  }

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat('pt-BR').format(date);
}
