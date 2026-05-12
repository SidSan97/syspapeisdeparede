import {
  calculateWallWithContinuations,
  calculateWallsSequence,
} from '@/utils/calculateStripsUtils.js';

function formatNumberBR(value, decimals = 2) {
  const n = Number(value);
  if (!Number.isFinite(n)) {
    return '0,00';
  }
  return n.toLocaleString('pt-BR', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });
}

function formatGroups(groups) {
  if (!Array.isArray(groups) || !groups.length) {
    return '';
  }
  return groups
    .filter((g) => g && g.q > 0 && Number.isFinite(g.h) && g.h > 0)
    .map((g) => `${g.q}F de ${formatNumberBR(g.h, 2)}m`)
    .join(' + ');
}

/**
 * Resumo construído pela sequência de paredes (carry entre paredes colapsadas).
 * Cada parede é representada por 1 segmento (parede + continuações colapsadas).
 *
 * Mantido para compatibilidade com fluxos legados.
 */
export function buildStripSummaryFromRooms(rooms = []) {
  if (!Array.isArray(rooms) || rooms.length === 0) {
    return '';
  }

  const parts = [];

  rooms.forEach((room) => {
    if (!Array.isArray(room?.walls) || room.walls.length === 0) {
      return;
    }

    const seq = calculateWallsSequence(room.walls);
    (seq.perWall || []).forEach((metric) => {
      const q = Number(metric?.strips ?? 0);
      const h = Number(metric?.stripHeight ?? 0);
      if (q > 0 && Number.isFinite(h) && h > 0) {
        parts.push({ q, h });
      }
    });
  });

  return formatGroups(parts);
}

/**
 * Concatena o resumo de todas as paredes do orçamento.
 *
 * - Paredes diferentes são processadas de forma INDEPENDENTE.
 * - Dentro de cada parede, a parede principal + continuações seguem o algoritmo
 *   da Calculadora do Revendedor (carry, merge consecutivo, paridade).
 *
 * Ex.: parede com 5F de 1,70m e continuação que vira 2F de 1,20m →
 *   "5F de 1,70m + 2F de 1,20m".
 */
export function buildStripSummaryFromParts(rooms = []) {
  if (!Array.isArray(rooms) || rooms.length === 0) {
    return '';
  }

  const segments = [];

  rooms.forEach((room) => {
    if (!Array.isArray(room?.walls) || room.walls.length === 0) {
      return;
    }

    room.walls.forEach((wall) => {
      const sequence = calculateWallWithContinuations(wall);
      sequence.groups.forEach((g) => {
        segments.push({ q: g.q, h: g.h });
      });
    });
  });

  return formatGroups(segments);
}

/**
 * Resumo construído a partir das mesmas funções de cálculo usadas pelo template
 * de cada view (quantidade e altura por parede).
 */
export function buildStripSummaryFromMetrics(rooms = [], getMetrics) {
  if (!Array.isArray(rooms) || rooms.length === 0 || typeof getMetrics !== 'function') {
    return '';
  }

  const groups = [];

  rooms.forEach((room) => {
    if (!Array.isArray(room?.walls) || room.walls.length === 0) {
      return;
    }

    room.walls.forEach((wall) => {
      const metric = getMetrics(wall) || {};
      const q = Number(metric.strips ?? 0);
      const h = Number(metric.stripHeight ?? 0);
      if (q > 0 && Number.isFinite(h) && h > 0) {
        groups.push({ q, h });
      }
    });
  });

  return formatGroups(groups);
}
