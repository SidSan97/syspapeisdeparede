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
 * Resumo por ambiente/parede para cópia (WhatsApp).
 *
 * Ex.: "Quarto (Parede 1) - 5F de 1,50m + 4F de 1,00m"
 *
 * @param {Array<{ name?: string, walls?: Array<{ name?: string }> }>} rooms
 * @returns {{ lines: string[], totalStrips: number }}
 */
export function buildStripSummaryByRoom(rooms = []) {
  const lines = [];
  let totalStrips = 0;

  if (!Array.isArray(rooms) || !rooms.length) {
    return { lines, totalStrips };
  }

  rooms.forEach((room, roomIndex) => {
    const roomName = String(room?.name ?? '').trim() || `Ambiente ${roomIndex + 1}`;

    if (!Array.isArray(room?.walls) || !room.walls.length) {
      return;
    }

    room.walls.forEach((wall, wallIndex) => {
      const wallName = String(wall?.name ?? '').trim() || `Parede ${wallIndex + 1}`;
      const sequence = calculateWallWithContinuations(wall);
      const summary = formatGroups(sequence.groups);

      if (!summary) {
        return;
      }

      totalStrips += sequence.groups.reduce((sum, group) => sum + Number(group.q ?? 0), 0);
      lines.push(`${roomName} (${wallName}) - ${summary}`);
    });
  });

  return { lines, totalStrips };
}

/**
 * Soma quantidades de faixas a partir de um resumo no formato "5F de 1,50m + ...".
 *
 * @param {string} summary
 * @returns {number}
 */
export function countTotalStripsFromSummary(summary = '') {
  if (!summary) {
    return 0;
  }

  let total = 0;
  const matches = String(summary).matchAll(/(\d+)F/g);

  for (const match of matches) {
    total += Number(match[1] ?? 0);
  }

  return total;
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
