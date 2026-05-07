import { calculateWallsSequence } from '@/utils/calculateStripsUtils.js';

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

function mergeConsecutiveGroups(groups) {
  const out = [];
  groups.forEach((group) => {
    if (!group || group.q <= 0 || !Number.isFinite(group.h) || group.h <= 0) {
      return;
    }
    const last = out[out.length - 1];
    if (last && Math.abs(last.h - group.h) < 1e-9) {
      last.q += group.q;
      return;
    }
    out.push({ q: group.q, h: group.h });
  });
  return out;
}

function formatStripGroups(groups) {
  const merged = mergeConsecutiveGroups(groups);
  if (!merged.length) {
    return '';
  }
  return merged.map((g) => `${g.q}F de ${formatNumberBR(g.h, 2)}m`).join(' + ');
}

/**
 * Resumo construído pela sequência de paredes (carry entre paredes).
 * Use quando o cálculo individual também usa `calculateWallsSequence`.
 *
 * Formato: "1F de 2,50m + 4F de 3,00m + 2F de 1,50m"
 */
export function buildStripSummaryFromRooms(rooms = []) {
  if (!Array.isArray(rooms) || rooms.length === 0) {
    return '';
  }

  const groups = [];

  rooms.forEach((room) => {
    if (!Array.isArray(room?.walls) || room.walls.length === 0) {
      return;
    }

    const seq = calculateWallsSequence(room.walls);
    (seq.perWall || []).forEach((metric) => {
      const q = Number(metric?.strips ?? 0);
      const h = Number(metric?.stripHeight ?? 0);
      if (q > 0 && Number.isFinite(h) && h > 0) {
        groups.push({ q, h });
      }
    });
  });

  return formatStripGroups(groups);
}

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

  return formatStripGroups(groups);
}
