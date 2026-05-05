import { calculateStripHeight } from './useBudgetCalculations';

/**
 * Composable para funções de formatação
 */

/**
 * Formata a altura do strip
 */
export function formatStripHeight(wall) {
  const height = calculateStripHeight(wall);
  return height ? height.toFixed(2) : 'N/D';
}

/**
 * Formata CEP
 */
export function formatCEP(value) {
  let formatted = value.replace(/\D/g, '');
  if (formatted.length > 5) {
    formatted = formatted.substring(0, 5) + '-' + formatted.substring(5, 8);
  }
  return formatted;
}

/**
 * Composable para formatação
 */
export function useBudgetFormatters() {
  return {
    formatStripHeight,
    formatCEP,
  };
}
